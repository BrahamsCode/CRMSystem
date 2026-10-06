<?php

namespace App\Services\Promotions;

use App\Enums\DeliveryStatus;
use App\Enums\MailMagazine;
use App\Enums\MessageChannel;
use App\Jobs\SendMessageBatch;
use App\Mail\PromotionMail;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\TestMailAddress;
use App\Services\Customers\CustomerSearch;
use App\Services\Promotions\Push\PushSender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Envía los mensajes de promoción.
 *
 * 1. audience(): los clientes de los filtros que pueden recibir por ese canal.
 * 2. send(): crea una fila en customer_message por destinatario (y su cupón, si
 *    lleva) y reparte el envío en lotes que procesa la cola.
 * 3. SendMessageBatch personaliza y envía cada mensaje y anota el resultado.
 */
class MessageDispatcher
{
    public const BATCH_SIZE = 200;

    /** Tras este número de correos fallidos, la dirección pasa a «no entregable» (como el legacy) */
    public const BOUNCE_LIMIT = 3;

    public function __construct(
        private CustomerSearch $search,
        private CouponIssuer $coupons,
    ) {}

    /** Clientes de los filtros que pueden recibir por el canal */
    public function audience(array $filters, MessageChannel $channel, ?int $shopId = null): Builder
    {
        $query = $this->search->query($filters);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $this->reachable($query, $channel);
    }

    /** Restringe a quienes tienen dónde recibir y aceptan recibir */
    public function reachable(Builder $query, MessageChannel $channel): Builder
    {
        if ($channel->isEmail()) {
            return $query->whereNotNull('mail1')->where('mail1', '<>', '')
                ->where('mail_magazine', MailMagazine::Send);
        }

        return $query->whereHas('devices', fn (Builder $d) => $d->where('status', 1));
    }

    /**
     * Cuenta para la vista previa del destinatario: total de los filtros y cuántos
     * se alcanzan por cada canal.
     */
    public function preview(array $filters, ?int $shopId = null): array
    {
        $base = fn () => tap($this->search->query($filters), fn (Builder $q) => $shopId ? $q->where('shop_id', $shopId) : $q);

        return [
            'total' => $base()->count(),
            'email' => $this->reachable($base(), MessageChannel::TextEmail)->count(),
            'push' => $this->reachable($base(), MessageChannel::Push)->count(),
        ];
    }

    /** Envío inmediato de un mensaje preparado (borrador o programado) */
    public function send(Message $message): int
    {
        $query = $this->audience($message->filters ?? [], $message->channel, $message->shop_id);

        return $this->deliver($message, $query);
    }

    /**
     * Crea los destinatarios de $query y encola el envío. Devuelve cuántos son.
     * También lo usa RuleRunner, que construye la consulta por su cuenta.
     */
    public function deliver(Message $message, Builder $query): int
    {
        $message->update(['delivery_status' => DeliveryStatus::Sending, 'scheduled_at' => $message->scheduled_at ?? now()]);

        $total = 0;

        $query->select('customers.id')->orderBy('customers.id')->chunkById(1000, function ($customers) use ($message, &$total) {
            $now = now();
            $rows = [];

            foreach ($customers as $customer) {
                $rows[] = [
                    'customer_id' => $customer->id,
                    'message_id' => $message->id,
                    'uid' => \App\Helpers\Uid::getUid() . Str::lower(Str::random(4)),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Un mismo cliente no recibe dos veces el mismo mensaje
            DB::table('customer_message')->insertOrIgnore($rows);
            $total += count($rows);
        }, 'customers.id', 'id');

        $ids = CustomerMessage::where('message_id', $message->id)->whereNull('sent_at')->whereNull('failed_at')->pluck('id');
        $message->update(['recipient_count' => CustomerMessage::where('message_id', $message->id)->count()]);

        if ($ids->isEmpty()) {
            $this->finish($message);

            return 0;
        }

        foreach ($ids->chunk(self::BATCH_SIZE) as $batch) {
            SendMessageBatch::dispatch($message->id, $batch->values()->all());
        }

        return $total;
    }

    /** Procesa un lote: lo llama el job */
    public function sendBatch(Message $message, array $recipientIds): void
    {
        $renderer = app(MessageRenderer::class);
        $push = app(PushSender::class);
        $message->loadMissing(['coupon', 'survey', 'footerShop']);

        $recipients = CustomerMessage::with(['customer.shop', 'customer.devices'])
            ->whereIn('id', $recipientIds)
            ->whereNull('sent_at')->whereNull('failed_at')
            ->get();

        foreach ($recipients as $recipient) {
            $customer = $recipient->customer;

            try {
                $coupon = $message->coupon ? $this->coupons->issue($message->coupon, $customer, $message) : null;
                // Sin cupón nuevo (ya lo tenía): se enlaza el que ya tiene
                $coupon ??= $message->coupon
                    ? CouponCustomer::where('coupon_id', $message->coupon_id)->where('customer_id', $customer->id)->latest('issued_at')->first()
                    : null;

                $content = $renderer->render(
                    $message->subject, $message->body, $message->channel === MessageChannel::HtmlEmail,
                    $customer, $recipient, $coupon, $message->survey?->uid, $message->footerShop,
                );

                if ($message->channel->isEmail()) {
                    Mail::to($customer->mail1)->send(new PromotionMail($content['subject'], $content['text'], $content['html']));
                    $recipient->update(['recipient' => $customer->mail1, 'sent_at' => now()]);
                } else {
                    $tokens = $customer->devices->where('status', 1)->pluck('token')->all();
                    $ok = $push->send($tokens, $content['subject'], $content['text'], route('promotions.track.open-push', $recipient->uid));
                    $ok
                        ? $recipient->update(['recipient' => count($tokens) . ' dispositivos', 'sent_at' => now()])
                        : $recipient->update(['failed_at' => now(), 'error' => 'Sin dispositivos activos']);
                }
            } catch (\Throwable $e) {
                $recipient->update(['failed_at' => now(), 'error' => Str::limit($e->getMessage(), 500)]);

                if ($message->channel->isEmail()) {
                    $this->registerBounce($customer);
                }

                report($e);
            }
        }

        $this->finishIfDone($message);
    }

    /** Envío de prueba a las direcciones de prueba de la tienda, con un cliente de muestra */
    public function sendTest(Message $message, ?Customer $sample = null): int
    {
        $addresses = TestMailAddress::active()->where('shop_id', $message->shop_id ?? $message->footer_shop_id ?? 0)->pluck('mail');

        if ($addresses->isEmpty()) {
            $addresses = TestMailAddress::active()->pluck('mail')->unique();
        }

        $sample ??= Customer::first() ?? new Customer(['last_name' => 'Cliente', 'first_name' => 'De prueba', 'code' => '0000000']);
        $content = app(MessageRenderer::class)->render(
            '[PRUEBA] ' . $message->subject, $message->body, $message->channel === MessageChannel::HtmlEmail,
            $sample, null, null, $message->survey?->uid, $message->footerShop,
        );

        foreach ($addresses as $address) {
            Mail::to($address)->send(new PromotionMail($content['subject'], $content['text'], $content['html']));
        }

        return $addresses->count();
    }

    /**
     * Aviso puntual a un cliente (tarjeta de sellos completada, vencimientos…).
     * Se registra como un envío más para que aparezca en su historial.
     */
    public function notify(Customer $customer, string $subject, string $body, ?CouponCustomer $coupon = null): ?Message
    {
        $channel = $customer->devices()->where('status', 1)->exists() ? MessageChannel::Push : MessageChannel::TextEmail;

        if ($channel->isEmail() && (! $customer->mail1 || $customer->mail_magazine !== MailMagazine::Send)) {
            return null;
        }

        // El cupón ya está entregado: se escribe en el texto en vez de adjuntarlo,
        // porque adjuntarlo haría que el envío entregara otro
        if ($coupon) {
            $body = strtr($body, [
                '{cupon}' => (string) $coupon->coupon?->name,
                '{enlace_cupon}' => route('promotions.public.coupon', $coupon->uid),
            ]);
        }

        $message = Message::create([
            'shop_id' => $customer->shop_id,
            'footer_shop_id' => $customer->shop_id,
            'channel' => $channel,
            'subject' => $subject,
            'body' => $body,
            'filters' => ['codes' => [(string) $customer->code], 'status' => 'all'],
            'note' => 'Aviso automático',
            'delivery_status' => DeliveryStatus::Draft,
        ]);

        $this->deliver($message, Customer::whereKey($customer->id));

        return $message;
    }

    /** Correo fallido: suma al contador y, al llegar al límite, marca la dirección como no entregable */
    public function registerBounce(Customer $customer): void
    {
        $customer->increment('bounce_count');

        if ($customer->bounce_count >= self::BOUNCE_LIMIT && $customer->mail_magazine === MailMagazine::Send) {
            $customer->forceFill(['mail_magazine' => MailMagazine::Undeliverable])->saveQuietly();
        }
    }

    private function finishIfDone(Message $message): void
    {
        $pending = CustomerMessage::where('message_id', $message->id)->whereNull('sent_at')->whereNull('failed_at')->exists();

        if (! $pending) {
            $this->finish($message);
        }
    }

    private function finish(Message $message): void
    {
        $message->update(['delivery_status' => DeliveryStatus::Sent, 'sent_at' => now()]);
    }
}

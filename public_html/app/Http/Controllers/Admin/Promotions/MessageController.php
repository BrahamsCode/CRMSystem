<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\DeliveryStatus;
use App\Enums\MessageChannel;
use App\Enums\Occupation;
use App\Enums\Sex;
use App\Models\Coupon;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Survey;
use App\Services\Customers\CustomerSearch;
use App\Services\Customers\FilterOptions;
use App\Services\Promotions\MessageDispatcher;
use App\Services\Promotions\MessageRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Envíos (お知らせ配信 / デコメール / プッシュ通知).
 *
 * El legacy tiene tres pantallas casi iguales, una por canal, más listas de
 * «programados» y «pasados» para cada una. Aquí es un solo flujo: elegir canal,
 * destinatarios (los mismos filtros de Buscar clientes), contenido y cuándo.
 */
class MessageController extends ModuleController
{
    private const TABS = [
        'sent' => 'Enviados',
        'scheduled' => 'Programados',
        'drafts' => 'Borradores',
        'auto' => 'Automáticos',
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'sent';

        $query = Message::with(['shop', 'coupon', 'rule'])
            ->withCount([
                'recipients as sent_count' => fn ($q) => $q->whereNotNull('sent_at'),
                'recipients as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
                'recipients as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
            ]);

        match ($tab) {
            'scheduled' => $query->whereIn('delivery_status', [DeliveryStatus::Scheduled, DeliveryStatus::Sending])->whereNull('message_rule_id')->orderBy('scheduled_at'),
            'drafts' => $query->whereIn('delivery_status', [DeliveryStatus::Draft, DeliveryStatus::Cancelled])->whereNull('message_rule_id')->latest('updated_at'),
            'auto' => $query->whereNotNull('message_rule_id')->latest('id'),
            default => $query->where('delivery_status', DeliveryStatus::Sent)->whereNull('message_rule_id')->latest('sent_at'),
        };

        if ($channel = MessageChannel::tryFrom($request->integer('channel'))) {
            $query->where('channel', $channel);
        }
        if ($term = trim((string) $request->input('q'))) {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(fn ($q) => $q->where('subject', 'ilike', $like)->orWhere('body', 'ilike', $like));
        }
        if ($request->filled('from')) {
            $query->whereDate($tab === 'scheduled' ? 'scheduled_at' : 'created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate($tab === 'scheduled' ? 'scheduled_at' : 'created_at', '<=', $request->date('to'));
        }

        $counts = [
            'sent' => Message::where('delivery_status', DeliveryStatus::Sent)->whereNull('message_rule_id')->count(),
            'scheduled' => Message::whereIn('delivery_status', [DeliveryStatus::Scheduled, DeliveryStatus::Sending])->whereNull('message_rule_id')->count(),
            'drafts' => Message::whereIn('delivery_status', [DeliveryStatus::Draft, DeliveryStatus::Cancelled])->whereNull('message_rule_id')->count(),
            'auto' => Message::whereNotNull('message_rule_id')->count(),
        ];

        return view('admin.promotions.messages.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'counts' => $counts,
            'messages' => $query->paginate(20)->withQueryString(),
            'search' => app(CustomerSearch::class),
        ]);
    }

    public function create(Request $request): View
    {
        // Desde «Buscar clientes» llegan los filtros por la URL
        $filters = $this->cleanFilters($request->query());
        $message = new Message([
            'channel' => MessageChannel::TextEmail,
            'shop_id' => null,
            'footer_shop_id' => $this->shop()?->id,
            'filters' => $filters,
        ]);

        if ($template = MessageTemplate::find($request->integer('template'))) {
            $message->fill(['subject' => $template->subject, 'body' => $template->body,
                'channel' => $template->html_flg ? MessageChannel::HtmlEmail : MessageChannel::TextEmail]);
        }
        if ($coupon = Coupon::find($request->integer('coupon'))) {
            $message->coupon_id = $coupon->id;
        }

        return view('admin.promotions.messages.form', $this->formData($message));
    }

    public function store(Request $request, MessageDispatcher $dispatcher): RedirectResponse
    {
        $data = $this->validated($request);
        $action = $request->input('action', 'draft');

        if ($action === 'test') {
            return $this->sendTest(new Message($data), $dispatcher);
        }

        $message = Message::create($data + ['delivery_status' => DeliveryStatus::Draft]);

        return $this->applyAction($message, $action, $data, $dispatcher);
    }

    public function show(Message $message): View
    {
        $message->load(['shop', 'footerShop', 'coupon', 'survey', 'rule']);
        $recipients = CustomerMessage::where('message_id', $message->id);

        $totals = [
            'recipients' => (clone $recipients)->count(),
            'sent' => (clone $recipients)->whereNotNull('sent_at')->count(),
            'failed' => (clone $recipients)->whereNotNull('failed_at')->count(),
            'opened' => (clone $recipients)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $recipients)->whereNotNull('clicked_at')->count(),
            'clicks' => (int) (clone $recipients)->sum('click_count'),
        ];

        return view('admin.promotions.messages.show', [
            'message' => $message,
            'totals' => $totals,
            'summary' => app(CustomerSearch::class)->summary($message->filters ?? []),
            'timing' => $this->openTiming($message),
            'breakdowns' => $this->breakdowns($message),
            'list' => CustomerMessage::with('customer')->where('message_id', $message->id)
                ->orderByRaw('clicked_at is null, opened_at is null, id')
                ->paginate(15),
            'couponsUsed' => $message->coupon_id
                ? DB::table('coupon_customer')->where('message_id', $message->id)->whereNotNull('used_at')->count()
                : null,
        ]);
    }

    public function edit(Message $message): View|RedirectResponse
    {
        if (! $message->delivery_status->isPending() && $message->delivery_status !== DeliveryStatus::Cancelled) {
            return redirect()->route('admin.promotions.messages.show', $message)
                ->with('status', 'Un envío hecho no se puede editar. Usa «Volver a enviar» para crear una copia.');
        }

        return view('admin.promotions.messages.form', $this->formData($message));
    }

    public function update(Request $request, Message $message, MessageDispatcher $dispatcher): RedirectResponse
    {
        abort_unless($message->delivery_status->isPending() || $message->delivery_status === DeliveryStatus::Cancelled, 403);

        $data = $this->validated($request);
        $action = $request->input('action', 'draft');

        if ($action === 'test') {
            return $this->sendTest((clone $message)->fill($data), $dispatcher);
        }

        $message->update($data);

        return $this->applyAction($message, $action, $data, $dispatcher);
    }

    /** «Volver a enviar» (再配信): copia como borrador con el mismo contenido y filtros */
    public function duplicate(Message $message): RedirectResponse
    {
        $copy = $message->replicate(['uid', 'delivery_status', 'scheduled_at', 'sent_at', 'recipient_count', 'message_rule_id']);
        $copy->delivery_status = DeliveryStatus::Draft;
        $copy->save();

        return redirect()->route('admin.promotions.messages.edit', $copy)
            ->with('status', 'Copia creada. Revisa los destinatarios y envíala.');
    }

    public function cancel(Message $message): RedirectResponse
    {
        abort_unless($message->delivery_status === DeliveryStatus::Scheduled, 403);

        $message->update(['delivery_status' => DeliveryStatus::Cancelled]);

        return back()->with('status', 'Envío programado cancelado. Queda en borradores.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        abort_if($message->delivery_status === DeliveryStatus::Sending, 403);

        $message->delete();

        return redirect()->route('admin.promotions.messages.index')->with('status', 'Envío eliminado.');
    }

    /** Recuento en vivo de destinatarios mientras se ajustan los filtros */
    public function audience(Request $request, MessageDispatcher $dispatcher): JsonResponse
    {
        $filters = $this->cleanFilters((array) $request->input('filters', []));
        $shopId = $request->integer('shop_id') ?: null;

        return response()->json($dispatcher->preview($filters, $shopId) + [
            'summary' => app(CustomerSearch::class)->summary($filters),
        ]);
    }

    // ---------------------------------------------------------------------

    private function formData(Message $message): array
    {
        return [
            'message' => $message,
            'channels' => MessageChannel::cases(),
            'templates' => MessageTemplate::active()->ordered()->get(['id', 'name', 'category', 'subject', 'body', 'html_flg']),
            'couponList' => Coupon::active()->orderByDesc('id')->get()->reject(fn (Coupon $c) => $c->isExpired())->values(),
            'surveys' => Survey::active()->latest()->get(['id', 'name']),
            'variables' => MessageRenderer::variables(),
            'preview' => app(MessageDispatcher::class)->preview($message->filters ?? [], $message->shop_id),
        ] + FilterOptions::for($this->shop()?->id);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'channel' => ['required', new Enum(MessageChannel::class)],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'footer_shop_id' => ['nullable', 'exists:shops,id'],
            'coupon_id' => ['nullable', Rule::exists('coupons', 'id')->whereNull('deleted_at')],
            'survey_id' => ['nullable', Rule::exists('surveys', 'id')->whereNull('deleted_at')],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', 'in:draft,schedule,now,test'],
            'scheduled_at' => ['nullable', 'required_if:action,schedule', 'date', 'after:now'],
            'filters' => ['nullable', 'array'],
        ], [
            'scheduled_at.required_if' => 'Indica la fecha y hora del envío programado.',
            'scheduled_at.after' => 'La fecha del envío programado tiene que ser futura.',
        ], [
            'subject' => 'título', 'body' => 'contenido', 'channel' => 'canal', 'scheduled_at' => 'fecha de envío',
        ]);

        $data['filters'] = $this->cleanFilters((array) $request->input('filters', []));
        unset($data['action']);

        if ($request->input('action') !== 'schedule') {
            $data['scheduled_at'] = null;
        }

        return $data;
    }

    /** Los filtros se validan con las mismas reglas de Buscar clientes; lo inválido se descarta */
    private function cleanFilters(array $filters): array
    {
        $validator = Validator::make($filters, CustomerSearch::rules());

        return CustomerSearch::clean(array_intersect_key($filters, $validator->valid()));
    }

    private function applyAction(Message $message, string $action, array $data, MessageDispatcher $dispatcher): RedirectResponse
    {
        if ($action === 'schedule') {
            $message->update(['delivery_status' => DeliveryStatus::Scheduled, 'scheduled_at' => $data['scheduled_at']]);

            return redirect()->route('admin.promotions.messages.index', ['tab' => 'scheduled'])
                ->with('status', 'Envío programado para el ' . $message->scheduled_at->format('d/m/Y H:i') . '.');
        }

        if ($action === 'now') {
            $count = $dispatcher->send($message);

            return redirect()->route('admin.promotions.messages.show', $message)
                ->with('status', $count
                    ? "Enviando a {$count} clientes. Las cifras se actualizan a medida que sale cada lote."
                    : 'Ningún cliente cumple los filtros y puede recibir por este canal: no se envió nada.');
        }

        $message->update(['delivery_status' => DeliveryStatus::Draft]);

        return redirect()->route('admin.promotions.messages.edit', $message)->with('status', 'Borrador guardado.');
    }

    private function sendTest(Message $message, MessageDispatcher $dispatcher): RedirectResponse
    {
        if (! $message->channel->isEmail()) {
            return back()->withInput()->with('status', 'Las notificaciones push no tienen envío de prueba: revisa la vista previa.');
        }

        $n = $dispatcher->sendTest($message);

        return back()->withInput()->with('status', $n
            ? "Prueba enviada a {$n} direcciones."
            : 'No hay emails de prueba. Añádelos en «Emails de prueba».');
    }

    /** Tiempo hasta la apertura: los tramos de la gráfica del legacy (時間別集計) */
    private function openTiming(Message $message): array
    {
        $rows = CustomerMessage::where('message_id', $message->id)->whereNotNull('sent_at')
            ->selectRaw('extract(epoch from (opened_at - sent_at)) / 3600 as horas')
            ->pluck('horas');

        $buckets = ['1 h' => 1, '3 h' => 3, '6 h' => 6, '12 h' => 12, '24 h' => 24, '1 semana' => 168];
        $out = array_fill_keys(array_keys($buckets), 0) + ['Más tarde' => 0, 'Sin abrir' => 0];

        foreach ($rows as $h) {
            if ($h === null) {
                $out['Sin abrir']++;

                continue;
            }
            $label = collect($buckets)->search(fn ($limit) => (float) $h <= $limit) ?: 'Más tarde';
            $out[$label]++;
        }

        return $out;
    }

    /** Clics por sexo, edad y ocupación (性別・年代別・職業別) */
    private function breakdowns(Message $message): array
    {
        $rows = CustomerMessage::query()
            ->join('customers', 'customers.id', '=', 'customer_message.customer_id')
            ->where('customer_message.message_id', $message->id)
            ->whereNotNull('customer_message.sent_at')
            ->get(['customers.sex', 'customers.birth_date', 'customers.occupation', 'customer_message.clicked_at']);

        $group = function (callable $key, array $labels) use ($rows) {
            $out = [];
            foreach ($labels as $k => $label) {
                $out[$k] = ['label' => $label, 'sent' => 0, 'clicked' => 0];
            }
            foreach ($rows as $row) {
                $k = $key($row);
                // Un código que no está en la lista (ej. sexo 9) cuenta en el último tramo, «sin especificar»
                $k = isset($out[$k]) ? $k : array_key_last($out);
                $out[$k]['sent']++;
                $out[$k]['clicked'] += $row->clicked_at ? 1 : 0;
            }

            return array_values($out);
        };

        $age = function ($row) {
            if (! $row->birth_date) {
                return 'none';
            }
            $years = \Illuminate\Support\Carbon::parse($row->birth_date)->age;

            return match (true) {
                $years < 20 => 'u20', $years < 30 => '20', $years < 40 => '30',
                $years < 50 => '40', $years < 60 => '50', default => '60',
            };
        };

        return [
            'Sexo' => $group(fn ($r) => (string) ($r->sex ?: 0), ['1' => Sex::Male->label(), '2' => Sex::Female->label(), '0' => 'Sin especificar']),
            'Edad' => $group($age, ['u20' => '19 o menos', '20' => '20–29', '30' => '30–39', '40' => '40–49', '50' => '50–59', '60' => '60 o más', 'none' => 'Sin fecha']),
            'Ocupación' => $group(fn ($r) => (string) ($r->occupation ?? 'none'),
                collect(Occupation::cases())->mapWithKeys(fn ($o) => [(string) $o->value => $o->label()])->put('none', 'Sin especificar')->all()),
        ];
    }
}

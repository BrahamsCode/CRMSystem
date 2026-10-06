<?php

namespace Database\Seeders;

use App\Enums\CouponDiscountType;
use App\Enums\CouponUsage;
use App\Enums\CouponValidity;
use App\Enums\CustomFieldType;
use App\Enums\DeliveryStatus;
use App\Enums\DevicePlatform;
use App\Enums\MessageChannel;
use App\Enums\PointMovement;
use App\Enums\RuleTrigger;
use App\Enums\StampMovement;
use App\Enums\TemplateCategory;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\CustomerLocation;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\MessageRule;
use App\Models\MessageTemplate;
use App\Models\PointSetting;
use App\Models\Shop;
use App\Models\StampRule;
use App\Models\StampSetting;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\TestMailAddress;
use App\Services\Promotions\CouponIssuer;
use App\Services\Promotions\LoyaltyService;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba del módulo de promociones: configuración, cupones,
 * automatizaciones, envíos ya hechos con aperturas y clics, una encuesta
 * respondida y movimientos de sellos y puntos.
 */
class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('name', 'shop')->firstOrFail();
        // Coordenadas de ejemplo (Umeda, Osaka) para el filtro de cercanía
        $shop->update(['latitude' => 34.702485, 'longitude' => 135.495951, 'tel' => '06-0000-0000', 'zip' => '530-0001', 'street_address' => 'Kita-ku Umeda 1-1']);

        StampSetting::forShop($shop)->fill([
            'card_size' => 10, 'signup_bonus' => 1, 'visit_stamp_flg' => 1, 'interval_seconds' => 3600,
            'design' => ['icon' => 'star', 'color' => '#c8343a'],
            'expiry_flg' => 1, 'expiry_days' => 365, 'expiry_hour' => 3, 'expiry_minute' => 0,
            'notice_flg' => 1, 'notice_days' => 7, 'notice_hour' => 10, 'notice_minute' => 0,
        ])->save();

        PointSetting::forShop($shop)->fill([
            'expiry_flg' => 1, 'expiry_days' => 365, 'expiry_hour' => 3, 'expiry_minute' => 0,
            'notices' => [['days' => 30, 'hour' => 10, 'minute' => 0], ['days' => 7, 'hour' => 10, 'minute' => 0]],
            'cash_rate' => 1, 'card_rate' => 0.5, 'use_limit' => 5000,
            'referral_points' => 300, 'referral_limit' => 10, 'referee_points' => 100, 'referral_comment' => 'Gracias por recomendarnos',
            'signup_points' => 100, 'signup_comment' => 'Bienvenida',
        ])->save();

        TestMailAddress::firstOrCreate(['shop_id' => $shop->id, 'mail' => 'pruebas@example.com'], ['name' => 'Equipo de marketing']);

        // --- Cupones ---
        $c = fn (array $a) => Coupon::firstOrCreate(['name' => $a['name']], $a + ['status' => 1, 'display_flg' => 1]);
        $cumple = $c(['name' => 'Regalo de cumpleaños', 'type' => CouponDiscountType::Percentage, 'value' => 15, 'usage_type' => CouponUsage::Newsletter,
            'description' => '15 % en tu compra durante el mes de tu cumpleaños.', 'validity_type' => CouponValidity::OneMonth,
            'notes' => ['Un uso por cliente', 'No acumulable con otras promociones']]);
        $vuelve = $c(['name' => 'Te echamos de menos', 'type' => CouponDiscountType::Fixed, 'value' => 500, 'usage_type' => CouponUsage::Newsletter,
            'description' => '¥500 de descuento en tu próxima visita.', 'validity_type' => CouponValidity::Days, 'validity_days' => 14]);
        $tarjeta = $c(['name' => 'Tarjeta completa', 'type' => CouponDiscountType::Percentage, 'value' => 20, 'usage_type' => CouponUsage::StampExchange,
            'cost' => 10, 'description' => '20 % al completar tu tarjeta de sellos.', 'validity_type' => CouponValidity::ThreeMonths]);
        $c(['name' => 'Bienvenida', 'type' => CouponDiscountType::Percentage, 'value' => 10, 'usage_type' => CouponUsage::Signup,
            'description' => '10 % en tu primera compra.', 'validity_type' => CouponValidity::OneMonth]);
        $c(['name' => 'Gracias por recomendarnos', 'type' => CouponDiscountType::Fixed, 'value' => 300, 'usage_type' => CouponUsage::Referral,
            'validity_type' => CouponValidity::OneMonth]);
        $encuesta = $c(['name' => 'Gracias por tu opinión', 'type' => CouponDiscountType::Fixed, 'value' => 200, 'usage_type' => CouponUsage::SurveyReward,
            'validity_type' => CouponValidity::OneMonth]);
        $c(['name' => 'Canje 1.000 puntos', 'type' => CouponDiscountType::Fixed, 'value' => 1000, 'usage_type' => CouponUsage::PointExchange,
            'cost' => 1000, 'validity_type' => CouponValidity::ThreeMonths]);

        StampRule::firstOrCreate(['shop_id' => $shop->id, 'stamp_count' => 10], ['coupon_id' => $tarjeta->id, 'notify_flg' => 1, 'reset_flg' => 1]);
        StampRule::firstOrCreate(['shop_id' => $shop->id, 'stamp_count' => 5], ['notify_flg' => 1, 'message' => '¡Vas por la mitad de tu tarjeta, {nombre_completo}!']);

        // --- Plantillas (la de cumpleaños es la del legacy) ---
        $cumpleTexto = "{nombre_completo}さまへ・・・☆彡\nお誕生日おめでとうございます♪♪\n\nUn regalo te espera en tu Mi página: {cupon}\n{enlace_cupon}";
        foreach ([
            [TemplateCategory::Birthday, 'Cumpleaños', '¡Feliz cumpleaños, {nombre}!', $cumpleTexto, 0],
            [TemplateCategory::AfterVisit, 'Gracias por tu visita', 'Gracias por venir, {nombre}', "Hola {nombre_completo}, gracias por visitarnos.\nYa llevas {sellos} sellos en tu tarjeta.", 0],
            [TemplateCategory::AfterSignup, 'Bienvenida', 'Bienvenido/a a {tienda}', "Hola {nombre_completo}, ya eres socio nº {num_socio}.\nTienes {puntos} puntos de regalo.", 0],
            [TemplateCategory::Newsletter, 'Novedades del mes', 'Novedades de {tienda}', '<h2>Novedades del mes</h2><p>Hola {nombre_completo}, mira lo que tenemos para ti: https://example.com/novedades</p>', 1],
            [TemplateCategory::Push, 'Oferta flash', '¡Solo hoy!', '20 % en toda la tienda hasta las 20:00.', 0],
        ] as $i => [$cat, $name, $subject, $body, $html]) {
            MessageTemplate::firstOrCreate(['name' => $name], ['category' => $cat, 'subject' => $subject, 'body' => $body, 'html_flg' => $html, 'sort' => $i + 1]);
        }

        // --- Automatizaciones ---
        MessageRule::firstOrCreate(['name' => 'Felicitación de cumpleaños'], [
            'trigger' => RuleTrigger::BeforeBirthday, 'days' => 3, 'run_hour' => 10, 'run_minute' => 0,
            'channel' => MessageChannel::TextEmail, 'subject' => '¡Feliz cumpleaños, {nombre}!', 'body' => $cumpleTexto, 'coupon_id' => $cumple->id,
        ]);
        MessageRule::firstOrCreate(['name' => 'Te echamos de menos (60 días)'], [
            'trigger' => RuleTrigger::AfterVisit, 'days' => 60, 'run_hour' => 11, 'run_minute' => 0,
            'channel' => MessageChannel::TextEmail, 'subject' => 'Hace tiempo que no te vemos', 'body' => "Hola {nombre_completo}, te regalamos {cupon} para tu próxima visita.\n{enlace_cupon}", 'coupon_id' => $vuelve->id,
        ]);
        MessageRule::firstOrCreate(['name' => 'Recordatorio de ciclo'], [
            'trigger' => RuleTrigger::VisitCycle, 'days' => 2, 'run_hour' => 9, 'run_minute' => 30,
            'channel' => MessageChannel::Push, 'subject' => '¿Te esperamos esta semana?', 'body' => 'Ya toca tu visita habitual, {nombre}.',
        ]);
        MessageRule::firstOrCreate(['name' => 'Novedades de los lunes'], [
            'trigger' => RuleTrigger::Weekdays, 'weekdays' => [1], 'run_hour' => 8, 'run_minute' => 0, 'status' => 0,
            'channel' => MessageChannel::HtmlEmail, 'subject' => 'Novedades de la semana', 'body' => '<p>Hola {nombre_completo}, esta semana…</p>',
        ]);

        // --- Encuesta ---
        $survey = Survey::firstOrCreate(['name' => 'Tu opinión sobre la tienda'], [
            'description' => 'Tres preguntas, menos de un minuto.', 'coupon_id' => $encuesta->id, 'status' => 1,
            'thanks_message' => '¡Gracias! Tu cupón ya está en Mi página.',
        ]);
        if ($survey->questions()->doesntExist()) {
            $q1 = $survey->questions()->create(['name' => '¿Cómo valoras la atención?', 'type' => CustomFieldType::Radio, 'options' => ['Excelente', 'Buena', 'Regular', 'Mala'], 'required_flg' => 1, 'sort' => 1]);
            $q2 = $survey->questions()->create(['name' => '¿Qué productos te interesan?', 'type' => CustomFieldType::Checkbox, 'options' => ['Polos', 'Pantalones', 'Accesorios'], 'sort' => 2]);
            $q3 = $survey->questions()->create(['name' => '¿Algo que podamos mejorar?', 'type' => CustomFieldType::Textarea, 'sort' => 3]);
        }

        $customers = Customer::where('shop_id', $shop->id)->orderBy('id')->take(40)->get();
        $issuer = app(CouponIssuer::class);
        $loyalty = app(LoyaltyService::class);

        // Dispositivos y ubicaciones de algunos clientes (app de Mi página)
        foreach ($customers->take(12) as $i => $customer) {
            CustomerDevice::firstOrCreate(['token' => 'demo-token-' . $customer->id], [
                'customer_id' => $customer->id, 'platform' => $i % 2 ? DevicePlatform::Android : DevicePlatform::Ios, 'last_seen_at' => now()->subHours($i),
            ]);
            CustomerLocation::create([
                'customer_id' => $customer->id, 'recorded_at' => now()->subHours($i * 2),
                'latitude' => 34.702485 + ($i - 6) * 0.004, 'longitude' => 135.495951 + ($i - 6) * 0.003,
            ]);
        }

        // --- Envíos ya hechos, con aperturas y clics ---
        if (Message::where('note', 'demo')->doesntExist()) {
            $envios = [
                [MessageChannel::HtmlEmail, 'Rebajas de otoño', '<h2>Rebajas de otoño</h2><p>Hasta 30 % en polos. https://example.com/rebajas</p>', 12, $cumple],
                [MessageChannel::TextEmail, 'Nueva colección de pantalones', "Hola {nombre_completo}, ya llegó la nueva colección.\nhttps://example.com/pantalones", 25, null],
                [MessageChannel::Push, 'Oferta flash', '20 % hasta las 20:00.', 4, null],
            ];

            foreach ($envios as $n => [$channel, $subject, $body, $daysAgo, $coupon]) {
                $sentAt = now()->subDays($daysAgo)->setTime(10, 0);
                $message = Message::create([
                    'shop_id' => $shop->id, 'footer_shop_id' => $shop->id, 'channel' => $channel, 'subject' => $subject, 'body' => $body,
                    'coupon_id' => $coupon?->id, 'filters' => $n === 1 ? ['sex' => '2'] : [], 'note' => 'demo',
                    'delivery_status' => DeliveryStatus::Sent, 'scheduled_at' => $sentAt, 'sent_at' => $sentAt,
                ]);

                $destinatarios = $channel === MessageChannel::Push ? $customers->take(12) : $customers->take(30 - $n * 6);
                foreach ($destinatarios as $j => $customer) {
                    $abre = $j % 3 !== 2;
                    $clic = $j % 4 === 0;
                    CustomerMessage::create([
                        'customer_id' => $customer->id, 'message_id' => $message->id, 'recipient' => $customer->mail1,
                        'sent_at' => $sentAt, 'opened_at' => $abre ? $sentAt->copy()->addMinutes(20 + $j * 37) : null,
                        'clicked_at' => $clic ? $sentAt->copy()->addMinutes(30 + $j * 41) : null, 'click_count' => $clic ? 1 + $j % 3 : 0,
                    ]);
                    if ($coupon && ($issued = $issuer->issue($coupon, $customer, $message)) && $j % 5 === 0) {
                        $issued->update(['used_at' => $sentAt->copy()->addDays(2 + $j % 6), 'used_shop_id' => $shop->id]);
                    }
                }
                $message->update(['recipient_count' => $destinatarios->count()]);
            }

            Message::create([
                'shop_id' => $shop->id, 'footer_shop_id' => $shop->id, 'channel' => MessageChannel::TextEmail,
                'subject' => 'Puertas abiertas del sábado', 'body' => "Hola {nombre_completo}, te esperamos este sábado.\nhttps://example.com/evento",
                'filters' => ['visit_period' => '90', 'visits_min' => '1'], 'note' => 'demo',
                'delivery_status' => DeliveryStatus::Scheduled, 'scheduled_at' => now()->addDays(3)->setTime(9, 0),
            ]);
            Message::create([
                'channel' => MessageChannel::HtmlEmail, 'subject' => 'Borrador: catálogo de invierno', 'body' => '<p>Próximamente…</p>',
                'filters' => [], 'note' => 'demo', 'delivery_status' => DeliveryStatus::Draft,
            ]);

            // Respuestas de la encuesta
            $q = $survey->questions;
            foreach ($customers->take(9) as $j => $customer) {
                SurveyResponse::firstOrCreate(['survey_id' => $survey->id, 'customer_id' => $customer->id], [
                    'answers' => [
                        (string) $q[0]->id => ['Excelente', 'Buena', 'Buena', 'Regular'][$j % 4],
                        (string) $q[1]->id => array_slice(['Polos', 'Pantalones', 'Accesorios'], 0, 1 + $j % 3),
                        (string) $q[2]->id => $j % 3 === 0 ? 'Más tallas grandes, por favor.' : null,
                    ],
                    'answered_at' => now()->subDays($j),
                ]);
            }

            // Sellos y puntos de las visitas que ya existían antes de configurar la tarjeta
            foreach ($customers as $customer) {
                foreach ($customer->visits()->where('visited_at', '>=', now()->subYear())->get() as $visit) {
                    // Con la fecha de la visita, para que el historial se vea como en producción
                    $loyalty->addStamps($customer, $shop, 1, StampMovement::Visit, null, $visit)
                        ->forceFill(['created_at' => $visit->visited_at, 'expires_at' => $visit->visited_at->copy()->addYear()])->save();
                    if ($visit->amount > 0) {
                        $loyalty->addPoints($customer, $shop, (int) floor($visit->amount / 100), PointMovement::Purchase, null, $visit)
                            ->forceFill(['created_at' => $visit->visited_at])->save();
                    }
                }
            }
        }

        $this->command?->info('PromotionSeeder: ' . Message::count() . ' envíos, ' . Coupon::count() . ' cupones, ' . MessageRule::count() . ' automatizaciones.');
    }
}

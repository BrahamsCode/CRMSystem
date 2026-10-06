<?php

use App\Enums\CouponDiscountType;
use App\Enums\CouponUsage;
use App\Enums\CouponValidity;
use App\Enums\CustomFieldType;
use App\Enums\DeliveryStatus;
use App\Enums\MailMagazine;
use App\Enums\MessageChannel;
use App\Enums\PointMovement;
use App\Enums\RuleTrigger;
use App\Enums\StampMovement;
use App\Mail\PromotionMail;
use App\Models\Coupon;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\CustomerMessage;
use App\Models\CustomerPoint;
use App\Models\Message;
use App\Models\MessageRule;
use App\Models\PointSetting;
use App\Models\Shop;
use App\Models\StampRule;
use App\Models\StampSetting;
use App\Models\Survey;
use App\Models\TestMailAddress;
use App\Models\Visit;
use App\Services\Customers\CustomerSearch;
use App\Services\Promotions\CouponIssuer;
use App\Services\Promotions\LoyaltyService;
use App\Services\Promotions\MessageDispatcher;
use App\Services\Promotions\RuleRunner;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->admin = seedBase();
    $this->shop = Shop::where('name', 'shop')->firstOrFail();
    $this->actingAs($this->admin, 'admin');
});

function coupon(array $attributes = []): Coupon
{
    return Coupon::create($attributes + [
        'name' => 'Cupón ' . random_int(1, 99999),
        'type' => CouponDiscountType::Percentage,
        'value' => 10,
        'usage_type' => CouponUsage::Newsletter,
        'validity_type' => CouponValidity::OneMonth,
    ]);
}

// ---------------------------------------------------------------------------
// Búsqueda de clientes
// ---------------------------------------------------------------------------

describe('CustomerSearch', function () {
    beforeEach(function () {
        $this->ana = makeCustomer(['last_name' => 'Tanaka', 'first_name' => 'Ana', 'sex' => 2, 'birth_date' => now()->subYears(30)->subDay(),
            'custom_data' => ['mascota' => ['tipo' => 'Perro']]]);
        $this->ken = makeCustomer(['last_name' => 'Sato', 'first_name' => 'Ken', 'sex' => 1, 'birth_date' => now()->subYears(45)]);
        $this->sin = makeCustomer(['last_name' => 'Suzuki', 'first_name' => 'Rin', 'sex' => null]);
        $this->baja = makeCustomer(['last_name' => 'Baja', 'status' => 0]);
        Visit::create(['customer_id' => $this->ken->id, 'shop_id' => $this->shop->id, 'visited_at' => now()->subDays(5), 'amount' => 3000]);
        Visit::create(['customer_id' => $this->ken->id, 'shop_id' => $this->shop->id, 'visited_at' => now()->subDays(2), 'amount' => 5000]);
        $this->search = app(CustomerSearch::class);
    });

    $ids = fn (array $filters) => app(CustomerSearch::class)->query($filters)->pluck('id')->sort()->values()->all();

    it('busca por nombre completo, sexo y edad', function () use ($ids) {
        expect($ids(['name' => 'Tanaka Ana']))->toBe([$this->ana->id])
            ->and($ids(['sex' => '1']))->toBe([$this->ken->id])
            ->and($ids(['sex' => '0']))->toBe([$this->sin->id])
            ->and($ids(['age_min' => '30', 'age_max' => '30']))->toBe([$this->ana->id]);
    });

    it('excluye las bajas salvo que se pidan', function () use ($ids) {
        expect($ids([]))->not->toContain($this->baja->id)
            ->and($ids(['status' => '0']))->toBe([$this->baja->id]);
    });

    it('filtra por visitas en el periodo y por ticket promedio', function () use ($ids) {
        expect($ids(['visit_period' => '30', 'visits_min' => '2']))->toBe([$this->ken->id])
            ->and($ids(['visit_period' => '30', 'visits_min' => '3']))->toBe([])
            ->and($ids(['avg_amount_min' => '4000']))->toBe([$this->ken->id])
            ->and($ids(['avg_amount_min' => '4001']))->toBe([]);
    });

    it('filtra por información adicional en jsonb', function () use ($ids) {
        expect($ids(['custom' => ['mascota' => ['tipo' => 'Perro']]]))->toBe([$this->ana->id]);
    });

    it('combina con O y niega el resultado', function () use ($ids) {
        $either = $ids(['logic' => 'or', 'sex' => '1', 'name' => 'Tanaka']);
        expect($either)->toBe(collect([$this->ana->id, $this->ken->id])->sort()->values()->all())
            ->and($ids(['negate' => '1', 'sex' => '1']))->toBe(collect([$this->ana->id, $this->sin->id])->sort()->values()->all());
    });

    it('encuentra a quien estuvo cerca de la tienda', function () use ($ids) {
        $this->shop->update(['latitude' => 34.7, 'longitude' => 135.5]);
        CustomerLocation::create(['customer_id' => $this->ana->id, 'latitude' => 34.703, 'longitude' => 135.5, 'recorded_at' => now()->subHour()]);
        CustomerLocation::create(['customer_id' => $this->ken->id, 'latitude' => 34.9, 'longitude' => 135.5, 'recorded_at' => now()->subHour()]);

        expect($ids(['near_shop_id' => (string) $this->shop->id, 'near_km' => '1', 'near_hours' => '24']))->toBe([$this->ana->id]);
    });

    it('resume los filtros en texto', function () {
        $summary = collect($this->search->summary(['sex' => '2', 'visit_period' => '90']))->pluck(1, 0);

        expect($summary['Sexo'])->toBe('Mujer')->and($summary['Periodo de visitas'])->toBe('Últimos 90 días');
    });

    it('filtra la pantalla Buscar clientes y exporta el CSV', function () {
        $this->get(route('admin.customers.search', ['name' => 'Sato']))->assertOk()->assertSee('Sato Ken')->assertDontSee('Tanaka Ana');

        $csv = $this->get(route('admin.customers.export', ['name' => 'Sato']))->assertOk()->streamedContent();
        expect($csv)->toContain('Nº de cliente')->toContain($this->ken->code)->not->toContain($this->ana->code);
    });
});

// ---------------------------------------------------------------------------
// Envíos
// ---------------------------------------------------------------------------

describe('Envíos', function () {
    beforeEach(function () {
        Mail::fake();
        $this->yes = makeCustomer(['last_name' => 'Recibe', 'mail_magazine' => MailMagazine::Send]);
        $this->no = makeCustomer(['last_name' => 'NoQuiere', 'mail_magazine' => MailMagazine::DoNotSend]);
    });

    $payload = fn (array $extra = []) => $extra + [
        'channel' => MessageChannel::TextEmail->value,
        'subject' => 'Hola {nombre}',
        'body' => "Hola {nombre_completo}\nhttps://example.com/oferta",
        'action' => 'now',
        'filters' => [],
    ];

    it('envía ahora solo a quien acepta newsletter, personaliza y entrega el cupón', function () use ($payload) {
        $coupon = coupon();

        $this->post(route('admin.promotions.messages.store'), $payload(['coupon_id' => $coupon->id]))->assertRedirect();

        $message = Message::latest('id')->firstOrFail();
        expect($message->delivery_status)->toBe(DeliveryStatus::Sent)
            ->and($message->recipients()->pluck('customer_id')->all())->toBe([$this->yes->id])
            ->and(CouponCustomer::where('customer_id', $this->yes->id)->where('coupon_id', $coupon->id)->exists())->toBeTrue();

        Mail::assertSent(PromotionMail::class, fn ($mail) => $mail->hasTo($this->yes->mail1)
            && $mail->subjectLine === 'Hola Cliente'
            && str_contains($mail->textBody, 'Recibe Cliente')
            && str_contains($mail->textBody, '/p/c/'));
        Mail::assertSentCount(1);
    });

    it('programa el envío y el comando lo manda cuando llega la hora', function () use ($payload) {
        $this->post(route('admin.promotions.messages.store'), $payload(['action' => 'schedule', 'scheduled_at' => now()->addHour()->format('Y-m-d\TH:i')]))
            ->assertRedirect();

        $message = Message::latest('id')->firstOrFail();
        expect($message->delivery_status)->toBe(DeliveryStatus::Scheduled);

        $this->artisan('promotions:send-scheduled');
        expect($message->fresh()->delivery_status)->toBe(DeliveryStatus::Scheduled);

        $this->travel(2)->hours();
        $this->artisan('promotions:send-scheduled');
        expect($message->fresh()->delivery_status)->toBe(DeliveryStatus::Sent);
        Mail::assertSentCount(1);
    });

    it('rechaza programar en el pasado', function () use ($payload) {
        $this->post(route('admin.promotions.messages.store'), $payload(['action' => 'schedule', 'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('scheduled_at');
    });

    it('manda la prueba a los emails de prueba sin guardar el envío', function () use ($payload) {
        TestMailAddress::create(['shop_id' => $this->shop->id, 'mail' => 'qa@example.com']);

        $this->post(route('admin.promotions.messages.store'), $payload(['action' => 'test', 'shop_id' => $this->shop->id]))->assertRedirect();

        Mail::assertSent(PromotionMail::class, fn ($m) => $m->hasTo('qa@example.com') && str_starts_with($m->subjectLine, '[PRUEBA]'));
        expect(Message::count())->toBe(0);
    });

    it('cuenta los destinatarios en vivo', function () {
        $this->postJson(route('admin.promotions.messages.audience'), ['filters' => []])
            ->assertOk()->assertJson(['total' => 2, 'email' => 1, 'push' => 0]);
    });

    it('vuelve a enviar como copia y cancela un programado', function () use ($payload) {
        $this->post(route('admin.promotions.messages.store'), $payload(['action' => 'schedule', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')]));
        $message = Message::latest('id')->firstOrFail();

        $this->post(route('admin.promotions.messages.cancel', $message))->assertRedirect();
        expect($message->fresh()->delivery_status)->toBe(DeliveryStatus::Cancelled);

        $this->post(route('admin.promotions.messages.duplicate', $message))->assertRedirect();
        expect(Message::count())->toBe(2)->and(Message::latest('id')->first()->delivery_status)->toBe(DeliveryStatus::Draft);
    });

    it('cuenta un correo fallido y a los tres marca la dirección como no entregable', function () {
        $dispatcher = app(MessageDispatcher::class);

        foreach (range(1, 3) as $n) {
            $dispatcher->registerBounce($this->yes->fresh());
        }

        expect($this->yes->fresh())->bounce_count->toBe(3)->mail_magazine->toBe(MailMagazine::Undeliverable);
    });
});

// ---------------------------------------------------------------------------
// Seguimiento de aperturas y clics
// ---------------------------------------------------------------------------

describe('Seguimiento', function () {
    beforeEach(function () {
        $customer = makeCustomer();
        $message = Message::create(['channel' => MessageChannel::HtmlEmail, 'subject' => 'x', 'body' => 'x', 'filters' => [], 'delivery_status' => DeliveryStatus::Sent]);
        $this->recipient = CustomerMessage::create(['customer_id' => $customer->id, 'message_id' => $message->id, 'sent_at' => now()]);
    });

    it('cuenta la apertura con el píxel', function () {
        $this->get(route('promotions.track.open', $this->recipient->uid))->assertOk()->assertHeader('Content-Type', 'image/gif');

        expect($this->recipient->fresh()->opened_at)->not->toBeNull();
    });

    it('cuenta el clic y redirige solo con la firma correcta', function () {
        $url = URL::signedRoute('promotions.track.click', ['recipient' => $this->recipient->uid, 'u' => 'https://example.com/oferta']);

        $this->get($url)->assertRedirect('https://example.com/oferta');
        $this->get($url)->assertRedirect();
        $this->get(route('promotions.track.click', ['recipient' => $this->recipient->uid, 'u' => 'https://malo.example']))->assertForbidden();

        expect($this->recipient->fresh())->click_count->toBe(2)->clicked_at->not->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// Automatizaciones
// ---------------------------------------------------------------------------

describe('Automatizaciones', function () {
    beforeEach(fn () => Mail::fake());

    it('felicita el cumpleaños N días antes, a su hora y una sola vez al día', function () {
        $this->travelTo(now()->setTime(9, 0));
        $cumple = makeCustomer(['last_name' => 'Cumple', 'birth_date' => now()->addDays(3)->subYears(30)]);
        makeCustomer(['last_name' => 'Otro', 'birth_date' => now()->addDays(10)->subYears(30)]);

        $rule = MessageRule::create(['name' => 'Cumple', 'trigger' => RuleTrigger::BeforeBirthday, 'days' => 3, 'run_hour' => 10, 'run_minute' => 0,
            'channel' => MessageChannel::TextEmail, 'subject' => 'Feliz cumple', 'body' => 'Hola {nombre_completo}', 'filters' => []]);

        expect(app(RuleRunner::class)->runDue())->toBe(0);

        $this->travelTo(now()->setTime(10, 1));
        expect(app(RuleRunner::class)->runDue())->toBe(1)
            ->and(app(RuleRunner::class)->runDue())->toBe(0);

        $message = $rule->messages()->firstOrFail();
        expect($message->recipients()->pluck('customer_id')->all())->toBe([$cumple->id]);
        Mail::assertSentCount(1);
    });

    it('sigue a quien no vuelve desde hace N días', function () {
        $c = makeCustomer();
        Visit::create(['customer_id' => $c->id, 'shop_id' => $this->shop->id, 'visited_at' => now()->subDays(30)]);

        $rule = MessageRule::create(['name' => 'Vuelve', 'trigger' => RuleTrigger::AfterVisit, 'days' => 30, 'run_hour' => 0, 'run_minute' => 0,
            'channel' => MessageChannel::TextEmail, 'subject' => 'Te extrañamos', 'body' => 'Vuelve', 'filters' => []]);

        expect(app(RuleRunner::class)->previewCount($rule))->toBe(1);
    });

    it('solo corre en los días de la semana elegidos', function () {
        $rule = new MessageRule(['trigger' => RuleTrigger::Weekdays, 'weekdays' => [1], 'run_hour' => 8, 'run_minute' => 0, 'status' => 1]);

        expect($rule->runsOn(now()->next('Monday')))->toBeTrue()
            ->and($rule->runsOn(now()->next('Tuesday')))->toBeFalse()
            ->and($rule->nextRunAt()->isMonday())->toBeTrue();
    });

    it('guarda una automatización desde el formulario', function () {
        $this->post(route('admin.promotions.rules.store'), [
            'name' => 'Fin de mes', 'trigger' => RuleTrigger::Dates->value, 'months' => [1, 6], 'month_days' => ['end'],
            'run_hour' => 9, 'run_minute' => 30, 'channel' => 1, 'subject' => 'Hola', 'body' => 'Contenido', 'status' => 1,
        ])->assertRedirect(route('admin.promotions.rules.index'));

        expect(MessageRule::first())->month_days->toBe(['end'])->months->toBe([1, 6])->days->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// Sellos, puntos y cupones
// ---------------------------------------------------------------------------

describe('Fidelización', function () {
    beforeEach(function () {
        Mail::fake();
        StampSetting::forShop($this->shop)->fill(['card_size' => 10, 'signup_bonus' => 1, 'interval_seconds' => 3600, 'visit_stamp_flg' => 1])->save();
        PointSetting::forShop($this->shop)->fill(['cash_rate' => 1, 'signup_points' => 100, 'referral_points' => 300, 'referee_points' => 50])->save();
    });

    it('da sellos y puntos de alta y un sello y puntos por visita respetando el intervalo', function () {
        $c = makeCustomer();
        expect($c->fresh())->stamp_balance->toBe(1)->point_balance->toBe(100);

        Visit::create(['customer_id' => $c->id, 'shop_id' => $this->shop->id, 'visited_at' => now(), 'amount' => 5000]);
        Visit::create(['customer_id' => $c->id, 'shop_id' => $this->shop->id, 'visited_at' => now(), 'amount' => 1000]);

        // Segunda visita dentro del intervalo: sin sello, pero sí puntos
        expect($c->fresh())->stamp_balance->toBe(2)->point_balance->toBe(100 + 50 + 10);
    });

    it('premia al que refiere y al referido', function () {
        $referrer = makeCustomer();
        $referee = makeCustomer(['referrer_id' => $referrer->id]);

        expect($referrer->fresh()->point_balance)->toBe(100 + 300)
            ->and($referee->fresh()->point_balance)->toBe(100 + 50);
    });

    it('al completar la tarjeta entrega el cupón y empieza otra', function () {
        $premio = coupon(['usage_type' => CouponUsage::StampExchange, 'cost' => 10]);
        StampRule::create(['shop_id' => $this->shop->id, 'stamp_count' => 10, 'coupon_id' => $premio->id, 'notify_flg' => 1, 'reset_flg' => 1]);
        $c = makeCustomer(['mail_magazine' => MailMagazine::Send]);

        app(LoyaltyService::class)->addStamps($c, $this->shop, 9, StampMovement::Adjustment);

        expect($c->fresh()->stamp_balance)->toBe(0)
            ->and(CouponCustomer::where('customer_id', $c->id)->where('coupon_id', $premio->id)->exists())->toBeTrue();
        Mail::assertSent(PromotionMail::class);
    });

    it('canjea un cupón por puntos y no deja gastar más del saldo', function () {
        $canje = coupon(['usage_type' => CouponUsage::PointExchange, 'cost' => 80, 'reissue_flg' => 1]);
        $c = makeCustomer();

        $this->post(route('admin.promotions.coupons.issue', $canje), ['code' => $c->code])->assertSessionHasNoErrors();
        expect($c->fresh()->point_balance)->toBe(20);

        $this->post(route('admin.promotions.coupons.issue', $canje), ['code' => $c->code])->assertSessionHasErrors('code');
    });

    it('marca un cupón como usado una sola vez', function () {
        $issued = app(CouponIssuer::class)->issue(coupon(), makeCustomer());
        expect($issued)->not->toBeNull();

        $this->post(route('admin.promotions.coupons.use', $issued))->assertRedirect();
        expect($issued->fresh()->used_at)->not->toBeNull()->and($issued->fresh()->isUsable())->toBeFalse();
    });

    it('no entrega dos veces un cupón que no es repetible', function () {
        $coupon = coupon();
        $c = makeCustomer();

        expect(app(CouponIssuer::class)->issue($coupon, $c))->not->toBeNull()
            ->and(app(CouponIssuer::class)->issue($coupon, $c))->toBeNull();
    });

    it('vence primero lo más antiguo y solo lo que no se gastó', function () {
        PointSetting::where('shop_id', $this->shop->id)->update(['expiry_flg' => 1, 'expiry_days' => 30, 'signup_points' => 0]);
        $c = makeCustomer();
        $loyalty = app(LoyaltyService::class);

        $loyalty->addPoints($c, $this->shop, 500, PointMovement::Adjustment);
        $loyalty->addPoints($c, $this->shop, -200, PointMovement::Use);

        $this->travel(31)->days();
        $loyalty->addPoints($c, $this->shop, 100, PointMovement::Adjustment);
        $loyalty->expire();
        $loyalty->expire();

        // 500 vencieron, pero 200 ya se habían usado: vencen 300 y quedan los 100 nuevos
        expect($c->fresh()->point_balance)->toBe(100)
            ->and(CustomerPoint::where('customer_id', $c->id)->where('type', PointMovement::Expiry)->sum('points'))->toEqual(-300);
    });

    it('guarda la tarjeta de sellos y los ajustes manuales', function () {
        $this->put(route('admin.promotions.stamps.update'), [
            'card_size' => 0, 'signup_bonus' => 2, 'visit_stamp_flg' => 1, 'interval_seconds' => 0, 'display_mode' => 2,
            'icon' => 'heart', 'color' => '#123456', 'expiry_flg' => 0, 'expiry_hour' => 3, 'expiry_minute' => 0,
            'notice_flg' => 0, 'notice_hour' => 10, 'notice_minute' => 0,
        ])->assertSessionHasNoErrors();

        expect(StampSetting::where('shop_id', $this->shop->id)->first())->card_size->toBeNull()->signup_bonus->toBe(2);

        $c = makeCustomer();
        $this->post(route('admin.promotions.stamps.adjust'), ['code' => $c->code, 'quantity' => 3])->assertSessionHasNoErrors();
        expect($c->fresh()->stamp_balance)->toBe(2 + 3);
    });
});

// ---------------------------------------------------------------------------
// Encuestas
// ---------------------------------------------------------------------------

describe('Encuestas', function () {
    it('crea la encuesta, la responde el cliente y recibe su premio una sola vez', function () {
        $premio = coupon(['usage_type' => CouponUsage::SurveyReward]);

        $this->post(route('admin.promotions.surveys.store'), [
            'name' => 'Opinión', 'status' => 1, 'coupon_id' => $premio->id,
            'questions' => [
                ['name' => '¿Qué tal?', 'type' => CustomFieldType::Radio->value, 'required_flg' => 1, 'options' => "Bien\nMal"],
                ['name' => 'Comentarios', 'type' => CustomFieldType::Textarea->value, 'required_flg' => 0],
            ],
        ])->assertRedirect();

        $survey = Survey::with('questions')->firstOrFail();
        [$q1, $q2] = $survey->questions;

        $customer = makeCustomer();
        $message = Message::create(['channel' => 1, 'subject' => 'x', 'body' => '{encuesta}', 'filters' => [], 'survey_id' => $survey->id]);
        $recipient = CustomerMessage::create(['customer_id' => $customer->id, 'message_id' => $message->id, 'sent_at' => now()]);
        auth('admin')->logout();

        $this->post(route('promotions.public.survey.answer', $survey), ['r' => $recipient->uid, 'q' => [$q2->id => 'Nada']])
            ->assertSessionHasErrors('q.' . $q1->id);

        $this->post(route('promotions.public.survey.answer', $survey), ['r' => $recipient->uid, 'q' => [$q1->id => 'Bien', $q2->id => 'Todo ok']])
            ->assertOk()->assertSee('Ver mi cupón');

        $this->post(route('promotions.public.survey.answer', $survey), ['r' => $recipient->uid, 'q' => [$q1->id => 'Mal']]);

        expect($survey->responses()->count())->toBe(1)
            ->and($survey->responses()->first()->answers[(string) $q1->id])->toBe('Bien')
            ->and(CouponCustomer::where('customer_id', $customer->id)->count())->toBe(1);
    });

    it('pide opciones en las preguntas de selección', function () {
        $this->post(route('admin.promotions.surveys.store'), [
            'name' => 'Sin opciones', 'questions' => [['name' => '¿Color?', 'type' => CustomFieldType::Select->value, 'options' => '']],
        ])->assertSessionHasErrors('questions.0.options');
    });
});

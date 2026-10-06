<?php

use App\Models\Coupon;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\MessageRule;
use App\Models\MessageTemplate;
use App\Models\Survey;

beforeEach(function () {
    $this->admin = seedBase();
    seedPromotions();
    $this->actingAs($this->admin, 'admin');
});

it('abre todas las pantallas del módulo de promociones', function (string $route) {
    $this->get(route($route))->assertOk();
})->with([
    'admin.promotions.index', 'admin.promotions.messages.index', 'admin.promotions.messages.create',
    'admin.promotions.rules.index', 'admin.promotions.rules.create', 'admin.promotions.templates.index',
    'admin.promotions.templates.create', 'admin.promotions.test-addresses', 'admin.promotions.coupons.index',
    'admin.promotions.coupons.create', 'admin.promotions.coupons.stats', 'admin.promotions.stamps',
    'admin.promotions.points', 'admin.promotions.surveys.index', 'admin.promotions.surveys.create',
    'admin.promotions.analytics', 'admin.promotions.visit-history', 'admin.promotions.registration',
]);

it('abre las pestañas de la lista de envíos', function (string $tab) {
    $this->get(route('admin.promotions.messages.index', ['tab' => $tab]))->assertOk();
})->with(['sent', 'scheduled', 'drafts', 'auto']);

it('abre el detalle y la edición de cada elemento', function () {
    $sent = Message::where('note', 'demo')->whereNotNull('sent_at')->firstOrFail();
    $draft = Message::where('delivery_status', 1)->firstOrFail();

    $this->get(route('admin.promotions.messages.show', $sent))->assertOk()->assertSee($sent->subject);
    $this->get(route('admin.promotions.messages.edit', $draft))->assertOk();
    $this->get(route('admin.promotions.rules.edit', MessageRule::first()))->assertOk();
    $this->get(route('admin.promotions.templates.edit', MessageTemplate::first()))->assertOk();
    $this->get(route('admin.promotions.coupons.show', Coupon::first()))->assertOk();
    $this->get(route('admin.promotions.coupons.edit', Coupon::first()))->assertOk();
    $this->get(route('admin.promotions.surveys.show', Survey::first()))->assertOk()->assertSee('Excelente');
    $this->get(route('admin.promotions.surveys.edit', Survey::first()))->assertOk();
    $this->get(route('admin.customers.show', Customer::where('stamp_balance', '>', 0)->first()))->assertOk()->assertSee('Saldo actual');
});

it('filtra el historial de visitas y las estadísticas de cupones por periodo', function () {
    foreach (['today', 'month', 'year', 'all'] as $period) {
        $this->get(route('admin.promotions.visit-history', ['period' => $period]))->assertOk();
    }
    foreach (['today', 'week', 'month', 'year'] as $period) {
        $this->get(route('admin.promotions.coupons.stats', ['period' => $period]))->assertOk();
    }
    $this->get(route('admin.promotions.analytics', ['days' => 365]))->assertOk();
});

it('abre las páginas públicas del cupón y la encuesta', function () {
    auth('admin')->logout();

    $issued = CouponCustomer::firstOrFail();
    $survey = Survey::firstOrFail();
    $recipient = CustomerMessage::firstOrFail();

    $this->get(route('promotions.public.coupon', $issued))->assertOk()->assertSee($issued->coupon->name);
    $this->get(route('promotions.public.survey', ['survey' => $survey, 'r' => $recipient->uid]))->assertOk();
});

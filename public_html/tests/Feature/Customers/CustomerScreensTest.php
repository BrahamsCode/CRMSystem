<?php

use App\Models\CustomCategory;
use App\Models\CustomField;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\Visit;

beforeEach(function () {
    $this->admin = seedBase();
    $this->actingAs($this->admin, 'admin');
});

it('abre todas las pantallas del módulo de clientes', function (string $route) {
    makeCustomer();

    $this->get(route($route))->assertOk();
})->with([
    'admin.customers.index', 'admin.customers.create', 'admin.customers.search',
    'admin.customers.visits', 'admin.customers.statistics', 'admin.customers.field-settings',
    'admin.customers.groups', 'admin.customers.visit-motives', 'admin.customers.ranks',
    'admin.customers.rank-schedules', 'admin.customers.visit-interval',
    'admin.customers.custom-categories', 'admin.customers.custom-categories.create',
    'admin.customers.custom-fields',
]);

it('abre la ficha y los formularios de edición', function () {
    $customer = makeCustomer();
    $category = CustomCategory::first();

    $this->get(route('admin.customers.show', $customer))->assertOk();
    $this->get(route('admin.customers.custom-categories.edit', $category))->assertOk();
    $this->get(route('admin.customers.custom-fields', ['category' => $category->id]))->assertOk();
    $this->get(route('admin.customers.custom-fields.create', ['category' => $category->id]))->assertOk();
});

it('muestra varios campos personalizados en la misma categoría', function () {
    $category = CustomCategory::has('fields', '>=', 2)->firstOrFail();

    $this->get(route('admin.customers.custom-fields', ['category' => $category->id]))
        ->assertOk()
        ->assertSee($category->fields->last()->name);
});

it('registra un cliente nuevo', function () {
    $this->post(route('admin.customers.store'), [
        'shop_id' => Shop::first()->id,
        'type' => 1,
        'last_name' => 'Tanaka',
        'first_name' => 'Yui',
        'mail1' => 'yui@example.com',
        'mail_magazine_flg' => 1,
        'reservation_reminder_flg' => 0,
    ])->assertRedirect();

    expect(Customer::where('mail1', 'yui@example.com')->first())
        ->not->toBeNull()
        ->reservation_reminder_flg->toBe(0);
});

it('registra una visita y respeta el intervalo de la tienda', function () {
    $customer = makeCustomer();

    $this->post(route('admin.customers.visits.store'), ['code' => $customer->code])->assertRedirect();
    $this->post(route('admin.customers.visits.store'), ['code' => $customer->code])->assertSessionHasErrors('code');

    expect(Visit::where('customer_id', $customer->id)->count())->toBe(1)
        ->and($customer->fresh()->visit_count)->toBe(1);
});

it('guarda el intervalo entre visitas', function () {
    $shop = Shop::first();

    $this->put(route('admin.customers.visit-interval.update'), ['intervals' => [$shop->id => 3600]])
        ->assertSessionHasNoErrors();

    expect($shop->fresh()->visit_interval_seconds)->toBe(3600);
});

it('guarda los interruptores y el orden de las categorías', function () {
    [$a, $b] = CustomCategory::ordered()->take(2)->get();

    $this->post(route('admin.customers.custom-categories.flags'), [
        'categories' => [$a->id => ['search_flg' => 0, 'display_flg' => 1]],
        'order' => [$b->id, $a->id],
    ])->assertSessionHasNoErrors();

    expect($a->fresh()->search_flg)->toBe(0)
        ->and($b->fresh()->sort)->toBe(1);
});

it('no permite dos categorías con el mismo nombre en la tienda', function () {
    $existing = CustomCategory::first();

    $this->post(route('admin.customers.custom-categories.store'), [
        'name' => $existing->name, 'columns' => 1, 'type' => 1,
    ])->assertSessionHasErrors('name');
});

it('crea un campo con opciones y lo vuelve a guardar sin chocar con el índice único', function () {
    $category = CustomCategory::first();
    $payload = [
        'custom_category_id' => $category->id, 'name' => 'Color favorito', 'type' => 7,
        'display_scope' => 1, 'required_flg' => 0, 'search_flg' => 1, 'mail_magazine_flg' => 0,
        'opciones' => [['label' => 'Rojo', 'value' => 'rojo'], ['label' => 'Azul', 'value' => 'azul']],
    ];

    $this->post(route('admin.customers.custom-fields.store'), $payload)
        ->assertRedirect(route('admin.customers.custom-fields', ['category' => $category->id]));

    $field = CustomField::where('name', 'Color favorito')->firstOrFail();

    $this->put(route('admin.customers.custom-fields.update', $field), $payload)->assertSessionHasNoErrors();

    expect($field->options()->count())->toBe(2);
});

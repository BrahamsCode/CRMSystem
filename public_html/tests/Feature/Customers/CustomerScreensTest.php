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

it('muestra los módulos en el inicio tras el login', function () {
    $this->get(route('admin.home'))->assertOk()
        ->assertSee(route('admin.customers.index'))
        ->assertSee(route('admin.promotions.index'))
        ->assertSee('Próximamente');
});

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
        'mail_magazine' => 1,
        'reservation_reminder_flg' => 0,
        'password' => '1234',
    ])->assertRedirect();

    expect(Customer::where('mail1', 'yui@example.com')->first())
        ->not->toBeNull()
        ->reservation_reminder_flg->toBe(0)
        ->sex->toBe(\App\Enums\Sex::Unknown);
});

// Mismos datos que se probaron en el formulario del legacy (顧客新規登録)
it('normaliza como el legacy: teléfonos y código postal a dígitos, lecturas a katakana', function () {
    $this->post(route('admin.customers.store'), [
        'shop_id' => Shop::first()->id, 'type' => 1, 'password' => 'pass1234',
        'last_name' => 'テスト', 'first_name' => '太郎', 'last_name_kana' => 'てすと', 'first_name_kana' => 'ﾀﾛｳ',
        'zip' => '530-0001', 'pref' => '大阪府', 'city' => '大阪市北区', 'street_address' => '梅田1-2-3', 'building' => '梅田ビル502',
        'tel1' => '06-1234-5678', 'tel2' => '０９０-１１１１-２２２２', 'tel3' => '06 1234 5679', 'sex' => 2, 'birth_date' => '1990-02-28',
        'company' => ['name' => '勤務先株式会社', 'name_kana' => 'きんむさき', 'industry' => 8, 'tel1' => '06-0000-1111', 'tel3' => '06-0000-2222',
            // Datos de empresa enviados por error en una persona: se descartan
            'capital' => 1000, 'department' => '営業部'],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $c = Customer::with('company')->where('last_name', 'テスト')->firstOrFail();
    expect($c)->zip->toBe('5300001')->tel1->toBe('0612345678')->tel2->toBe('09011112222')->tel3->toBe('0612345679')
        ->last_name_kana->toBe('テスト')->first_name_kana->toBe('タロウ')->building->toBe('梅田ビル502')
        ->and($c->company)->name->toBe('勤務先株式会社')->name_kana->toBe('キンムサキ')->tel1->toBe('0600001111')
        ->capital->toBeNull()->department->toBeNull();
});

it('registra una empresa con sus datos y sin datos personales', function () {
    $this->post(route('admin.customers.store'), [
        'shop_id' => Shop::first()->id, 'type' => 2, 'password' => 'pass1234', 'last_name' => '株式会社テスト',
        'birth_date' => '1990-01-01', 'tel2' => '09011112222', 'sex' => 1,
        'company' => ['founded_on' => '2001-04-01', 'capital' => '10000000', 'industry' => 7, 'department' => '営業部',
            'representative_last_name' => '代表', 'representative_first_name' => '花子', 'representative_last_name_kana' => 'ダイヒョウ',
            'representative_sex' => 2, 'contact_last_name' => '担当', 'contact_tel1' => '06-3333-4444', 'contact_mail' => 'tantou@example.com'],
    ])->assertSessionHasNoErrors();

    $c = Customer::with('company')->where('last_name', '株式会社テスト')->firstOrFail();
    expect($c)->sex->toBe(\App\Enums\Sex::NotApplicable)->birth_date->toBeNull()->tel2->toBeNull()
        ->and($c->company)->capital->toBe(10000000)->contact_tel1->toBe('0633334444')->representative_sex->toBe(\App\Enums\Sex::Female);

    $this->get(route('admin.customers.show', $c))->assertOk()->assertSee('Datos de empresa')->assertSee('営業部');
});

it('rechaza lo que el legacy rechaza y también las fechas imposibles que el legacy acepta', function () {
    $this->post(route('admin.customers.store'), [
        'shop_id' => Shop::first()->id, 'type' => 1, 'last_name' => 'Mal',
        'last_name_kana' => 'Taro', 'zip' => '53-ab', 'tel1' => 'abc-1234', 'mail1' => 'no-es-email',
        'birth_date' => '1990-02-29', 'wedding_date' => '2099-01-01',
    ])->assertSessionHasErrors(['last_name_kana', 'zip', 'tel1', 'mail1', 'birth_date', 'wedding_date', 'password']);
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

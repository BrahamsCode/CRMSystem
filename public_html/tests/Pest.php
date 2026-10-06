<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** Datos mínimos del sistema: admin, tienda y catálogos, sin los 120 clientes de prueba */
function seedBase(): \App\Models\Admin
{
    test()->seed([
        \Database\Seeders\AdminSeeder::class,
        \Database\Seeders\ShopSeeder::class,
        \Database\Seeders\CatalogSeeder::class,
        \Database\Seeders\CustomCategorySeeder::class,
        \Database\Seeders\FieldSettingSeeder::class,
    ]);

    return \App\Models\Admin::first();
}

/** Cliente de prueba en la primera tienda */
function makeCustomer(array $attributes = []): \App\Models\Customer
{
    return \App\Models\Customer::create($attributes + [
        'shop_id' => \App\Models\Shop::orderBy('id')->value('id'),
        'last_name' => 'Prueba',
        'first_name' => 'Cliente',
        'mail1' => 'cliente' . random_int(1000, 999999) . '@example.com',
    ]);
}

/** Unos pocos clientes con visitas y el módulo de promociones sembrado encima */
function seedPromotions(int $customers = 15): void
{
    $shop = \App\Models\Shop::where('name', 'shop')->firstOrFail();

    for ($i = 0; $i < $customers; $i++) {
        $customer = makeCustomer([
            'shop_id' => $shop->id,
            'last_name' => 'Cliente' . $i,
            'first_name' => 'Demo',
            'sex' => $i % 2 ? 2 : 1,
            'birth_date' => now()->subYears(20 + $i)->subDays($i),
            'occupation' => 1 + $i % 5,
            'mail_magazine' => 1,
        ]);

        for ($v = 0; $v < 1 + $i % 3; $v++) {
            \App\Models\Visit::create([
                'customer_id' => $customer->id,
                'shop_id' => $shop->id,
                'visited_at' => now()->subDays(10 + $v * 20 + $i),
                'amount' => 2000 + $v * 500,
            ]);
        }
    }

    test()->seed(\Database\Seeders\PromotionSeeder::class);
}

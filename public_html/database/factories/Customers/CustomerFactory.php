<?php

namespace Database\Factories\Customers;

use App\Models\Customers\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Socio de prueba sobre `tb_customer` (columnas del legacy). El `cd` lo pone `hatuban`.
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $faker = fake('ja_JP');
        $birth = $faker->dateTimeBetween('-70 years', '-18 years');

        return [
            'id' => (string) $faker->unique()->numberBetween(1100001, 1999999),
            'tb_shop_cd' => 1,
            'type' => '個人',
            'name' => $faker->lastName() . ' ' . $faker->firstName(),
            'name_kana' => $faker->lastKanaName() . ' ' . $faker->firstKanaName(),
            'zip' => preg_replace('/\D/', '', $faker->postcode()),
            'pref' => $faker->prefecture(),
            'add' => $faker->city(),
            'email1' => $faker->unique()->safeEmail(),
            'tel' => '0' . $faker->numerify('#########'),
            'kojin_mobile' => '090' . $faker->numerify('########'),
            'kojin_birth' => $birth->format('Y/m/d'),
            'kojin_sex' => $faker->randomElement(['男性', '女性']),
            'mailmaga_flg' => '1',
            'touroku_kubun' => Customer::MEMBER_KUBUN,
            'disp_flg' => '1',
            'login_pass' => $faker->bothify('####'),
        ];
    }

    public function configure(): static
    {
        // login_id = id al registrarse, como el legacy
        return $this->afterMaking(fn (Customer $c) => $c->login_id ??= $c->id);
    }
}

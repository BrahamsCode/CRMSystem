<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        // Las cuatro tiendas que aparecen en los mockups del módulo
        $shops = [
            ['name' => 'shop', 'name_kana' => 'ショップ', 'pref' => 'Osaka', 'city' => 'Osaka'],
            ['name' => 'Polos', 'name_kana' => 'ポロス', 'pref' => 'Osaka', 'city' => 'Osaka'],
            ['name' => 'Pantalones', 'name_kana' => 'パンタロネス', 'pref' => 'Osaka', 'city' => 'Sakai'],
            ['name' => 'テスト2', 'name_kana' => 'テストツー', 'pref' => 'Tokyo', 'city' => 'Shibuya'],
        ];

        foreach ($shops as $shop) {
            Shop::firstOrCreate(['name' => $shop['name']], $shop);
        }
    }
}

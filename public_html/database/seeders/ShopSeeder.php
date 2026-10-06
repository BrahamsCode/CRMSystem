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
            ['name' => 'shop', 'name_kana' => 'ショップ', 'pref' => '大阪府', 'city' => '大阪市北区'],
            ['name' => 'Polos', 'name_kana' => 'ポロス', 'pref' => '大阪府', 'city' => '大阪市北区'],
            ['name' => 'Pantalones', 'name_kana' => 'パンタロネス', 'pref' => '大阪府', 'city' => '堺市堺区'],
            ['name' => 'テスト2', 'name_kana' => 'テストツー', 'pref' => '東京都', 'city' => '渋谷区'],
        ];

        foreach ($shops as $shop) {
            Shop::firstOrCreate(['name' => $shop['name']], $shop);
        }
    }
}

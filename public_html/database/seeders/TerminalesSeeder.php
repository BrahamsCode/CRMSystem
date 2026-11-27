<?php

namespace Database\Seeders;

use App\Enums\Terminales\SignageContentMode;
use App\Enums\Terminales\SignageCouponUsage;
use App\Enums\Terminales\SignageFileType;
use App\Models\Coupon;
use App\Models\Shop;
use App\Models\SignageDisplay;
use App\Models\SignageMaterial;
use App\Models\SignageProgram;
use App\Models\SignageSystem;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba de 電子看板管理. En el entorno de desarrollo del legacy no hay
 * displays ni programas registrados: estos permiten probar el reproductor
 * (/digitalsignage/?shop_cd=&display_cd=) y el XML del programa.
 */
class TerminalesSeeder extends Seeder
{
    public function run(): void
    {
        if (SignageProgram::query()->exists()) {
            return;
        }

        $shop = Shop::query()->orderBy('id')->first();

        $materials = collect([
            ['title' => 'オープニング', 'duration_seconds' => 10, 'images' => [['flg' => '1', 'ext' => 'jpg', 'path' => null]]],
            ['title' => '本日のおすすめ', 'duration_seconds' => 15, 'images' => [['flg' => '1', 'ext' => 'jpg', 'path' => null], ['flg' => '1', 'ext' => 'png', 'path' => null]]],
            ['title' => 'クーポン案内', 'duration_seconds' => 8, 'images' => [['flg' => '1', 'ext' => 'png', 'path' => null]]],
        ])->map(fn (array $data, int $i) => SignageMaterial::create($data + [
            'shop_id' => $shop?->id,
            'file_type' => SignageFileType::Image,
            'sort' => $i,
        ]));

        $system = SignageSystem::create(['shop_id' => $shop?->id, 'name' => 'ルーレット', 'system_type' => 1]);

        // Programa con materiales (XML con tres <p>) y otro en modo システム内蔵
        $program = SignageProgram::create([
            'shop_id' => $shop?->id,
            'name' => '店頭プログラム',
            'content_mode' => SignageContentMode::Files,
            'terminal_types' => ['1'],
            'ticker_text' => 'いらっしゃいませ',
        ]);
        foreach ($materials as $position => $material) {
            $program->materials()->attach($material->id, ['position' => $position]);
        }

        $systemProgram = SignageProgram::create([
            'shop_id' => $shop?->id,
            'name' => 'ルーレットプログラム',
            'content_mode' => SignageContentMode::System,
            'terminal_types' => ['2'],
        ]);
        $systemProgram->systems()->attach($system->id, ['position' => 0]);

        $coupon = Coupon::query()->orderBy('id')->first();
        if ($coupon) {
            $program->coupons()->attach($coupon->id, ['usage' => SignageCouponUsage::General, 'position' => 0]);
        }

        SignageDisplay::create([
            'shop_id' => $shop?->id,
            'signage_program_id' => $program->id,
            'name' => '入口ディスプレイ（横）',
            'content_mode' => SignageContentMode::Files,
            'terminal_types' => ['1'],
        ]);
        $vertical = SignageDisplay::create([
            'shop_id' => $shop?->id,
            'signage_program_id' => $systemProgram->id,
            'name' => 'レジ横ディスプレイ（縦）',
            'content_mode' => SignageContentMode::System,
            'terminal_types' => ['2'],
        ]);
        $vertical->systems()->attach($system->id, ['position' => 0]);
    }
}

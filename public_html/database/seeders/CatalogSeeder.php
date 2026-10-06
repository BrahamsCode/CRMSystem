<?php

namespace Database\Seeders;

use App\Enums\RankType;
use App\Models\CustomerGroup;
use App\Models\Rank;
use App\Models\Shop;
use App\Models\VisitMotive;
use Illuminate\Database\Seeder;

/**
 * Catálogos que alimentan los desplegables del alta de clientes:
 * grupos, motivos de primera visita y rangos.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('name', 'shop')->firstOrFail();

        $grupos = [
            ['name' => 'General', 'default_flg' => 1, 'sort' => 1],
            ['name' => 'Clientes VIP', 'default_flg' => 0, 'sort' => 2],
            ['name' => 'Corporativo', 'default_flg' => 0, 'sort' => 3],
        ];

        foreach ($grupos as $grupo) {
            CustomerGroup::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $grupo['name']],
                $grupo + ['shop_id' => $shop->id],
            );
        }

        // «たまたまきた» (pasaba por aquí) es el único motivo que trae el legacy
        $motivos = ['たまたまきた', 'Recomendación de un amigo', 'Redes sociales', 'Publicidad'];

        foreach ($motivos as $i => $motivo) {
            VisitMotive::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $motivo],
                ['sort' => $i + 1],
            );
        }

        $rangos = [
            [RankType::Amount, 'Bronce', 0, 49999],
            [RankType::Amount, 'Plata', 50000, 199999],
            [RankType::Amount, 'Oro', 200000, null],
            [RankType::Visits, 'Ocasional', 0, 4],
            [RankType::Visits, 'Habitual', 5, 19],
            [RankType::Visits, 'Fiel', 20, null],
        ];

        foreach ($rangos as $i => [$type, $name, $min, $max]) {
            Rank::firstOrCreate(
                ['shop_id' => $shop->id, 'type' => $type, 'name' => $name],
                ['min_value' => $min, 'max_value' => $max, 'sort' => $i + 1],
            );
        }
    }
}

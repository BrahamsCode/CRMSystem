<?php

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\Industry;
use App\Enums\MailMagazine;
use App\Enums\Occupation;
use App\Enums\Sex;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Shop;
use App\Models\Visit;
use App\Models\VisitMotive;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /** Clientes generados además de los del mockup, para que las estadísticas tengan volumen */
    private const GENERADOS = 110;

    public function run(): void
    {
        $tiendas = Shop::orderBy('id')->get();
        $shop = $tiendas->firstWhere('name', 'shop');
        $grupo = CustomerGroup::where('shop_id', $shop->id)->where('default_flg', 1)->first();
        $motivos = VisitMotive::where('shop_id', $shop->id)->pluck('id');

        $this->delMockup($shop, $grupo, $motivos);
        $this->generados($tiendas, $grupo, $motivos);
        $this->conEmpresa($tiendas->first());

        $this->command?->info('CustomerSeeder: ' . Customer::count() . ' clientes en la base.');
    }

    /** Los mismos clientes que muestran los resultados de búsqueda del mockup */
    private function delMockup(Shop $shop, ?CustomerGroup $grupo, $motivos): void
    {
        // [apellido, nombre, apellido_kana, nombre_kana, sexo, visitas]
        $clientes = [
            ['十倉テスト', '十倉テスト', 'トクラテスト', 'トクラテスト', Sex::Male, 0],
            ['川本', '享永', 'カワモト', 'タカヒサ', Sex::Male, 0],
            ['岩本', 'テスト', 'イワモト', 'テスト', Sex::Female, 0],
            ['48002', 'テスト', 'ヨンハチゼロゼロニ', 'テスト', Sex::Male, 0],
            ['坂本', '綾花', 'サカモト', 'アヤカ', Sex::Female, 0],
            ['清森', 'てすと', 'キヨモリ', 'テスト', Sex::Female, 0],
            ['テスト', '高', 'テスト', 'タカシ', Sex::Male, 0],
            ['配信', '受け取り', 'ハイシン', 'ウケトリ', Sex::Male, 0],
            ['水谷', '友洋', 'ミズタニ', 'トモヒロ', Sex::Male, 2],
            ['ゲスト', '', 'ゲスト', '', null, 0],
        ];

        foreach ($clientes as $i => [$apellido, $nombre, $apellidoKana, $nombreKana, $sexo, $visitas]) {
            $cliente = Customer::firstOrCreate(
                ['shop_id' => $shop->id, 'last_name' => $apellido, 'first_name' => $nombre],
                [
                    'type' => CustomerType::Person,
                    'last_name_kana' => $apellidoKana,
                    'first_name_kana' => $nombreKana,
                    'sex' => $sexo,
                    'birth_date' => now()->subYears(25 + $i)->subDays($i * 37),
                    'tel1' => '06' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT) . '0000',
                    'mail1' => 'cliente' . ($i + 1) . '@example.com',
                    'zip' => '530000' . ($i % 10),
                    'pref' => '大阪府',
                    'city' => '大阪市北区',
                    'city_kana' => 'オオサカシキタク ウメダ',
                    'street_address' => '梅田' . ($i + 1) . '-' . ($i + 2),
                    'customer_group_id' => $grupo?->id,
                    'visit_motive_id' => $motivos->get($i % max($motivos->count(), 1)),
                    'mail_magazine' => MailMagazine::Send,
                    'address_type' => AddressType::cases()[$i % count(AddressType::cases())],
                    'occupation' => Occupation::cases()[$i % count(Occupation::cases())],
                    'status' => CustomerStatus::Registered,
                    // Un array PHP vacío se serializa como [] y no como {}, que es lo
                    // que esperan los operadores JSONB.
                    'custom_data' => $i % 3 === 0
                        ? ['mascota' => ['nombre' => 'Firulais', 'tipo' => 'Perro', 'peso' => 12 + $i]]
                        : new \stdClass,
                    // Repartidos por el año para que el gráfico de altas tenga forma
                    'created_at' => now()->subDays($i * 29),
                ],
            );

            $this->visitas($cliente, $visitas, 4500);
        }
    }

    /**
     * Crea visitas reales en vez de rellenar los contadores a mano: el
     * VisitObserver recalcula visit_count, importes y fechas a partir de ellas.
     * Si se falsean los contadores, la ficha muestra cifras que no casan con el
     * historial.
     */
    private function visitas(Customer $cliente, int $cuantas, int $importeBase): void
    {
        for ($v = 0; $v < $cuantas; $v++) {
            // En horario de tienda (11:00–20:00) y nunca en el futuro
            $fecha = $cliente->created_at->copy()->startOfDay()->addDays(18 * ($v + 1))
                ->setTime(11 + ($cliente->id + $v * 3) % 10, (($cliente->id * 7 + $v * 13) % 4) * 15);

            if ($fecha->isFuture()) {
                break;
            }

            Visit::create([
                'customer_id' => $cliente->id,
                'shop_id' => $cliente->shop_id,
                'visit_motive_id' => $cliente->visit_motive_id,
                'visited_at' => $fecha,
                'amount' => $importeBase + ($v * 600),
            ]);
        }
    }

    /**
     * Altas repartidas por los últimos 12 meses y entre las cuatro tiendas.
     * Sin esto, el gráfico de «Altas por mes» saldría con una sola barra.
     */
    private function generados($tiendas, ?CustomerGroup $grupo, $motivos): void
    {
        if (Customer::count() > 20) {
            return;
        }

        $apellidos = ['佐藤', '鈴木', '高橋', '田中', '渡辺', '伊藤', '山本', '中村', '小林', '加藤'];
        $nombres = ['翔太', '美咲', '健一', '由美', '大輔', '愛', '涼平', '千尋', '直樹', '結衣'];

        // Más altas en primavera, para que la curva no sea plana
        $pesoMes = [3, 4, 5, 9, 7, 6, 5, 6, 5, 8, 6, 5];

        foreach ($pesoMes as $mesAtras => $peso) {
            for ($n = 0; $n < $peso * 2; $n++) {
                if (Customer::count() >= self::GENERADOS + 10) {
                    return;
                }

                $tienda = $tiendas[($mesAtras + $n) % $tiendas->count()];
                $alta = now()->subMonths($mesAtras)->startOfMonth()->addDays(($n * 3) % 27);
                $visitas = ($mesAtras + $n) % 7;

                $cliente = Customer::create([
                    'shop_id' => $tienda->id,
                    'type' => CustomerType::Person,
                    'last_name' => $apellidos[($mesAtras + $n) % count($apellidos)],
                    'first_name' => $nombres[$n % count($nombres)],
                    'sex' => $n % 2 === 0 ? Sex::Male : Sex::Female,
                    'birth_date' => now()->subYears(20 + ($n % 45))->subDays($n * 11),
                    'mail1' => "gen{$mesAtras}_{$n}@example.com",
                    'tel1' => '06' . str_pad((string) (2000 + $n), 4, '0', STR_PAD_LEFT) . str_pad((string) $mesAtras, 4, '0', STR_PAD_LEFT),
                    'pref' => '大阪府',
                    'customer_group_id' => $tienda->id === $grupo?->shop_id ? $grupo->id : null,
                    'visit_motive_id' => $motivos->get($n % max($motivos->count(), 1)),
                    'mail_magazine' => MailMagazine::Send,
                    'address_type' => AddressType::cases()[$n % count(AddressType::cases())],
                    'occupation' => Occupation::cases()[$n % count(Occupation::cases())],
                    'status' => CustomerStatus::Registered,
                    'custom_data' => new \stdClass,
                    'created_at' => $alta,
                    'updated_at' => $alta,
                ]);

                $this->visitas($cliente, $visitas, 3000 + ($n % 5) * 800);
            }
        }
    }

    /**
     * Un cliente empresa y una persona con lugar de trabajo, con los mismos datos
     * que se probaron en el formulario del legacy (法人情報 / 勤務先).
     */
    private function conEmpresa(Shop $shop): void
    {
        $empresa = Customer::firstOrCreate(['shop_id' => $shop->id, 'last_name' => '株式会社テスト'], [
            'type' => CustomerType::Company, 'last_name_kana' => 'カブシキガイシャテスト',
            'zip' => '5300001', 'pref' => '大阪府', 'city' => '大阪市北区', 'city_kana' => 'オオサカシキタク ウメダ',
            'street_address' => '梅田1-2-3', 'building' => '梅田ビル502', 'building_kana' => 'ウメダビル',
            'tel1' => '0612345678', 'tel3' => '0612345679', 'mail1' => 'empresa@example.com',
            'mail_magazine' => MailMagazine::Send, 'status' => CustomerStatus::Registered,
        ]);
        $empresa->company()->firstOrCreate([], [
            'industry' => Industry::cases()[6], 'department' => '営業部', 'founded_on' => '2001-04-01', 'capital' => 10000000,
            'representative_last_name' => '代表', 'representative_first_name' => '花子',
            'representative_last_name_kana' => 'ダイヒョウ', 'representative_first_name_kana' => 'ハナコ',
            'representative_birth_date' => '1970-05-05', 'representative_sex' => Sex::Female,
            'contact_last_name' => '担当', 'contact_first_name' => '次郎', 'contact_last_name_kana' => 'タントウ', 'contact_first_name_kana' => 'ジロウ',
            'contact_tel1' => '0633334444', 'contact_tel3' => '0633335555', 'contact_mail' => 'tantou@example.com',
        ]);

        $persona = Customer::where('shop_id', $shop->id)->where('type', CustomerType::Person)->orderBy('id')->first();
        $persona?->company()->firstOrCreate([], [
            'name' => '勤務先株式会社', 'name_kana' => 'キンムサキカブシキガイシャ', 'industry' => Industry::cases()[7],
            'tel1' => '0600001111', 'tel3' => '0600002222',
        ]);
    }
}

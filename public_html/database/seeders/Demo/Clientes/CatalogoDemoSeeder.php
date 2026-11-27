<?php

namespace Database\Seeders\Demo\Clientes;

use App\Models\Customers\ServiceCategory;
use App\Models\Sales\Discount;
use App\Models\Sales\SalesItem;
use App\Models\Sales\ServiceMenu;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración (S2, parte D2): catálogo de un salón de belleza.
 *
 * - メニュー管理 (`tb_karute_menu`) y 割引管理 (`tb_karute_waribiki`) de cada tienda activa, con precios en yenes
 *   (impuesto incluido: el legacy no calcula impuestos en las ventas internas).
 * - 商品管理 (`sales_items`): productos de venta para todas las tiendas, algunos canjeables por puntos.
 * - 施術カテゴリー (`tb_sejutsu_cate`, 全店舗) que usa la カルテ; solo las que falten por nombre.
 *
 * Casos límite: 割引 全額サービス (100 %, ventas de 0円), menús その他 de importe alto (ventas de ~300.000円) y un
 * producto con nombre de 255 caracteres.
 *
 * Idempotente por partes: cada tienda se siembra si no tiene aún su menú marcador (ブライダルプラン), así que una
 * tienda creada después (NucleoDemoSeeder) recibe su catálogo al volver a ejecutar; los productos, si no existe el
 * producto con `display_title` = MARKER (si existe, solo se añaden las tiendas nuevas); 施術カテゴリー, por nombre.
 * Reglas comunes en CONVENCIONES.md §4 «Datos de demostración».
 */
class CatalogoDemoSeeder extends Seeder
{
    public const MARKER = 'CRMデモデータ';

    private const SEED = 20261202;

    /** メニュー: [nombre, precio, type (1 技術, 2 物販, 3 その他)] */
    public const MENUS = [
        ['カット', 4950, ServiceMenu::TYPE_SERVICE],
        ['前髪カット', 1100, ServiceMenu::TYPE_SERVICE],
        ['キッズカット', 3300, ServiceMenu::TYPE_SERVICE],
        ['カラー', 6600, ServiceMenu::TYPE_SERVICE],
        ['リタッチカラー', 5500, ServiceMenu::TYPE_SERVICE],
        ['パーマ', 8800, ServiceMenu::TYPE_SERVICE],
        ['デジタルパーマ', 13200, ServiceMenu::TYPE_SERVICE],
        ['縮毛矯正', 16500, ServiceMenu::TYPE_SERVICE],
        ['トリートメント', 3300, ServiceMenu::TYPE_SERVICE],
        ['ヘッドスパ', 4400, ServiceMenu::TYPE_SERVICE],
        ['ブロー', 2200, ServiceMenu::TYPE_SERVICE],
        ['シャンプー（ホームケア）', 3520, ServiceMenu::TYPE_RETAIL],
        ['トリートメント（ホームケア）', 3960, ServiceMenu::TYPE_RETAIL],
        ['ヘアオイル', 2860, ServiceMenu::TYPE_RETAIL],
        ['スタイリングワックス', 2200, ServiceMenu::TYPE_RETAIL],
        ['指名料', 550, ServiceMenu::TYPE_OTHER],
        // Importes altos (ventas de ~300.000円): その他, para que SociosDemoSeeder (solo type 1) no los elija al azar
        ['前撮りヘアメイク', 33000, ServiceMenu::TYPE_OTHER],
        ['ブライダルプラン', 110000, ServiceMenu::TYPE_OTHER],
    ];

    /** 割引: [nombre, valor, type (1 ％, 2 円), buai_flg (1 歩合に含む, 2 含まない)] */
    public const DISCOUNTS = [
        ['初回割引', 20, Discount::TYPE_PERCENT, '1'],
        ['学割', 500, Discount::TYPE_YEN, '1'],
        ['紹介割引', 1000, Discount::TYPE_YEN, '2'],
        ['誕生月割引', 10, Discount::TYPE_PERCENT, '2'],
        ['平日割引', 300, Discount::TYPE_YEN, '1'],
        ['全額サービス', 100, Discount::TYPE_PERCENT, '2'], // caso límite: ventas de 0円
    ];

    /** 施術カテゴリー (カルテ) */
    private const SERVICE_CATEGORIES = ['カット', 'カラー', 'パーマ', 'トリートメント', 'ヘッドスパ', '縮毛矯正'];

    /** 商品: [name, display_name, 価格の目安 (説明文用), ポイント交換 (0 = 不可)] */
    private const PRODUCTS = [
        ['モイストシャンプー 300ml', 'モイストシャンプー', 3520, 400],
        ['リペアトリートメント 250g', 'リペアトリートメント', 3960, 450],
        ['ヘアオイル 100ml', 'ヘアオイル', 2860, 300],
        ['スタイリングワックス 80g', 'ワックス', 2200, 0],
        ['ヘアスプレー 180g', 'ヘアスプレー', 1980, 0],
        ['スカルプローション 120ml', 'スカルプローション', 4180, 500],
        ['カラーケアシャンプー 300ml', 'カラーケアシャンプー', 3740, 0],
        ['ヘアブラシ', 'ヘアブラシ', 2750, 250],
        [null, 'サロン限定ギフトセット', 9900, 1000], // null = longProductName()
    ];

    /**
     * Caso límite: nombre largo. `sales_items.name` es `text` (sin máximo en la BD ni en StoreProductRequest):
     * se usan 255 caracteres, el máximo habitual de un campo de texto de formulario.
     */
    public const LONG_PRODUCT_NAME_LENGTH = 255;

    private const LONG_PRODUCT_TEXT = 'サロン限定ギフトセット（モイストシャンプー300ml・リペアトリートメント250g・ヘアオイル100ml・スカルプローション120ml・ヘアブラシ・オリジナルポーチ付き）'
        .'ご家族やご友人へのプレゼントにも最適な、当サロンのスタイリストが厳選したホームケアアイテムの詰め合わせです。'
        .'髪質やお悩みに合わせてお選びいただけるよう、店頭でカウンセリングも承っております。お気軽にスタッフまでお声がけください。'
        .'数量限定のため、なくなり次第終了となります。あらかじめご了承ください。季節限定のパッケージでお届けいたします。ラッピング無料。メッセージカードもご用意しております。';

    public function run(): void
    {
        $faker = FakerFactory::create('ja_JP');
        $faker->seed(self::SEED);
        $shopCds = self::activeShopCds();
        $productsSeeded = SalesItem::query()->withoutGlobalScopes()->where('display_title', self::MARKER)->exists();

        DB::transaction(function () use ($faker, $shopCds, $productsSeeded) {
            foreach ($shopCds as $shopCd) {
                if (! $this->shopSeeded($shopCd)) {
                    $this->seedShopMasters($shopCd);
                }
            }
            $this->seedServiceCategories();
            $productsSeeded ? $this->addShopsToProducts($shopCds) : $this->seedProducts($faker, $shopCds);
        });
    }

    /** Marcador por tienda: su menú ブライダルプラン (también si se borró después, para no volver a crearlo) */
    private function shopSeeded(int $shopCd): bool
    {
        [$name, $price] = self::MENUS[array_key_last(self::MENUS)];

        return ServiceMenu::query()->withoutGlobalScopes()->where('tb_shop_cd', $shopCd)
            ->where('name', $name)->where('kingaku', $price)->exists();
    }

    /** Tiendas activas (`disp_flg = '1'`); si no hay ninguna marcada, todas las no borradas */
    public static function activeShopCds(): array
    {
        $cds = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('cd');
        if ($cds->isEmpty()) {
            $cds = DB::table('tb_shop')->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('cd')->pluck('cd');
        }

        return $cds->map(fn ($cd) => (int) $cd)->all();
    }

    /** メニュー y 割引 de la tienda, al final de su orden (rank = máximo + 1, como KaruteMasterService) */
    private function seedShopMasters(int $shopCd): void
    {
        $common = ['tb_shop_cd' => $shopCd, 'disp_flg' => '1', 'hyouji_flg' => '1'];

        $rank = (int) ServiceMenu::query()->where('tb_shop_cd', $shopCd)->max('rank');
        foreach (self::MENUS as [$name, $price, $type]) {
            ServiceMenu::create($common + ['name' => $name, 'kingaku' => $price, 'type' => $type, 'rank' => ++$rank]);
        }

        $rank = (int) Discount::query()->where('tb_shop_cd', $shopCd)->max('rank');
        foreach (self::DISCOUNTS as [$name, $value, $type, $buai]) {
            Discount::create($common + ['name' => $name, 'value' => $value, 'type' => $type, 'buai_flg' => $buai, 'rank' => ++$rank]);
        }
    }

    /** 施術カテゴリー de todas las tiendas (tb_shop_cd nulo), sin repetir nombres activos */
    private function seedServiceCategories(): void
    {
        $existing = ServiceCategory::query()->where('disp_flg', '1')->pluck('name')->all();
        foreach (self::SERVICE_CATEGORIES as $name) {
            if (! in_array($name, $existing, true)) {
                ServiceCategory::create(['name' => $name, 'tb_shop_cd' => null, 'disp_flg' => '1']);
            }
        }
    }

    /** Tiendas nuevas (p. ej. creadas después por NucleoDemoSeeder) en available_shop_cds de los productos demo */
    private function addShopsToProducts(array $shopCds): void
    {
        $names = array_map(fn ($p) => $p[0] ?? self::longProductName(), self::PRODUCTS);
        foreach (SalesItem::query()->whereIn('name', $names)->get() as $item) {
            $cds = array_values(array_unique(array_merge($item->availableShopCds(), $shopCds)));
            if (count($cds) !== count($item->availableShopCds())) {
                $item->setAvailableShopCds($cds);
                $item->save();
            }
        }
    }

    /** Nombre del caso límite: exactamente LONG_PRODUCT_NAME_LENGTH caracteres */
    public static function longProductName(): string
    {
        return mb_substr(str_repeat(self::LONG_PRODUCT_TEXT, 2), 0, self::LONG_PRODUCT_NAME_LENGTH);
    }

    /** 商品管理: disponibles en todas las tiendas; el primero lleva el marcador en display_title */
    private function seedProducts(\Faker\Generator $faker, array $shopCds): void
    {
        foreach (self::PRODUCTS as $i => [$name, $displayName, $price, $points]) {
            $name ??= self::longProductName();
            $item = new SalesItem;
            $item->forceFill([
                'disp_flg' => 1,
                'hyouji_flg' => 1,
                'name' => $name,
                'display_name' => $displayName,
                'display_title' => $i === 0 ? self::MARKER : $displayName,
                'discription' => '<p>サロン専売品。'.$faker->realText(40).'</p>',
                'discription_detail' => '<p>店頭価格 '.number_format($price).'円（税込）</p>',
                'is_exchangeable_of_point' => $points > 0 ? 1 : 0,
                'number_of_points_exchanged' => $points > 0 ? $points : null,
            ]);
            $item->setAvailableShopCds($shopCds);
            $item->save();
        }
    }
}

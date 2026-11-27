<?php

namespace App\Services\Mypage;

use App\Models\AdminMaster;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la vista admin.mypage.app-images.edit (contrato documentado al inicio del Blade).
 * Reproduce lo que leen mobile_mycolle_edit*.php: contents_master (tb_c6_user_hp), tb_shop,
 * tb_area (Area::createAreaCheckUi) y los archivos de hp/uploads/c6/.
 */
class AppImageScreen
{
    public const SECTIONS = ['all', 'cover', 'banner', 'main', 'thumbnail'];

    /** Huecos de カバーフロー y バナー (mobile_mycolle_edit_coverflow.php / _banner.php) */
    public const SLOT_COUNT = 21;

    /** Imágenes de hp/img/banner/ de ﾃﾞﾌｫﾙﾄ選択 (lista fija de mobile_mycolle_edit_banner.php, con el tabelog repetido) */
    public const BANNER_CELL_LIST = [
        'mobile_banner_1.jpg', 'mobile_banner_2.jpg', 'mobile_banner_3.jpg',
        'mobile_banner_4.jpg', 'mobile_banner_5.jpg', 'mobile_banner_6.jpg',
        'beauty.png', 'beauty02.png', 'blog_bodywork.png', 'blog_eat_highGrade.png',
        'blog_eat_japan.png', 'blog_eat_public.png', 'blog_eat_western.png', 'blog_este.png',
        'blog_hair.png', 'blog_nail.png', 'company.png', 'company02.png', 'eat.png', 'eat02.png',
        'Facebook_1.jpg', 'Facebook_2.jpg', 'Facebook_3.jpg', 'gurunavi_1.jpg', 'gurunavi_2.jpg',
        'hot_papper_1.jpg', 'hot_papper_2.jpg', 'hot_papper_beauty_1.jpg', 'hot_papper_beauty_2.jpg',
        'Instagram_1.jpg', 'Instagram_2.jpg', 'LINE_1.jpg', 'LINE_2.jpg', 'LINE_3.jpg',
        'mypage.png', 'mypage02.png', 'recruit_beauty.png', 'recruit_beauty02.png',
        'recruit_eat.png', 'recruit_eat02.png', 'shop.png', 'shop02.png', 'tabelog_1.jpg',
        'twitter_1.jpg', 'twitter_2.jpg', 'twitter_3.jpg', 'tabelog_1.jpg',
    ];

    public function __construct(
        private readonly MypageConfig $config,
        private readonly AppImageFiles $files,
    ) {}

    public function data(string $section): array
    {
        $shops = $this->shops();
        $mypageConfig = $this->config->mypageConfig();

        $data = [
            'section' => $section,
            'applicationUseFlg' => (int) AdminMaster::current()->application_use_flg === 1,
            'shops' => $shops,
            'mypageConfig' => $mypageConfig,
            'slots' => [],
            'homeComments' => [],
            'customerGroupDisplay' => false,
            'bannerCellList' => self::BANNER_CELL_LIST,
            'areaTree' => [],
            'areaShops' => [],
            'areaDepthMax' => 1,
            'tempImageUrl' => $this->files->tempUrl(),
        ];

        switch ($section) {
            case 'cover':
            case 'banner':
                $data['slots'] = $this->listSlots($section, $shops, $mypageConfig);
                $data['areaTree'] = $this->areaTree();
                $data['areaShops'] = $this->areaShops();
                $data['areaDepthMax'] = max(1, $this->depth($data['areaTree']));
                break;
            case 'main':
            case 'thumbnail':
                $data['slots'] = $this->homeSlots($section, $shops, $mypageConfig);
                break;
            default:
                $data['homeComments'] = $this->config->homeComments();
                $data['customerGroupDisplay'] = $this->config->customerGroupDisplay();
        }

        return $data;
    }

    /** tb_shop con disp_flg = '1' ORDER BY cd (legacy $shop_data_ary) */
    public function shops(): Collection
    {
        return Shop::query()->withDeleted()->where('disp_flg', '1')->orderBy('cd')->get();
    }

    /** Huecos de カバーフロー ('cover') o バナー ('banner') */
    private function listSlots(string $section, Collection $shops, array $mypageConfig): array
    {
        $folder = $section === 'cover' ? AppImageFiles::COVER : AppImageFiles::BANNER;
        $files = $this->files->numbered($folder);
        $links = $this->config->links($folder);
        $flags = $mypageConfig[$section] ?? [];

        $slots = [];
        for ($i = 0; $i < self::SLOT_COUNT; $i++) {
            $slotFlags = is_array($flags[$i] ?? null) ? $flags[$i] : [];
            $slot = [
                'index' => $i,
                'imageUrl' => isset($files[$i]) ? $this->files->url($files[$i]) : null,
                'linkUrl' => (string) ($links[$i] ?? ''),
                'allShops' => isset($slotFlags[0]),
                'shopFlags' => $shops->mapWithKeys(fn (Shop $shop) => [$shop->cd => isset($slotFlags[$shop->cd])])->all(),
            ];
            if ($section === 'banner') {
                $slot['cellStatus'] = (int) ($mypageConfig['cell_status'][$i] ?? 1) === 2 ? 2 : 1;
                $slot['cellImg'] = $mypageConfig['cell_img'][$i] ?? null;
            }
            $slots[] = $slot;
        }

        return $slots;
    }

    /**
     * Huecos de メインイメージ / サムネイル: 0 = 全店舗用 y uno por tienda.
     * El archivo es `<shop_cd>.<ext>` (0 = 全店舗用), que es lo que lee la API de la app
     * (api/mpa_corresponding.php). El legacy mostraba `<índice>.<ext>` (bug, ver diseño).
     */
    private function homeSlots(string $section, Collection $shops, array $mypageConfig): array
    {
        $isMain = $section === 'main';
        $files = $this->files->numbered($isMain ? AppImageFiles::HOME_IMAGE : AppImageFiles::HOME_THUMBNAIL);
        $flags = $mypageConfig[$isMain ? 'home_image_flg' : 'home_thum_flg'] ?? [];

        $slots = [[
            'index' => 0,
            'shop' => null,
            'imageUrl' => isset($files[0]) ? $this->files->url($files[0]) : null,
            'useAllShopsImage' => false,
        ]];
        foreach ($shops->values() as $position => $shop) {
            $slots[] = [
                'index' => $position + 1,
                'shop' => $shop,
                'imageUrl' => isset($files[$shop->cd]) ? $this->files->url($files[$shop->cd]) : null,
                'useAllShopsImage' => (int) ($flags[$shop->cd] ?? 0) === 1,
            ];
        }

        return $slots;
    }

    /** Árbol de tb_area como Area::createAreaCheckUi (disp_flg <> '9', flg = '1', orden row_lis) */
    public function areaTree(): array
    {
        $areas = DB::table('tb_area')
            ->where('disp_flg', '<>', '9')
            ->where('flg', '1')
            ->orderBy('row_lis')
            ->get(['cd', 'pid', 'name']);

        return $this->branch($areas, null);
    }

    private function branch(Collection $areas, ?int $parent): array
    {
        return $areas
            ->filter(fn ($area) => $parent === null
                ? ($area->pid === null || (int) $area->pid < 1)
                : (int) $area->pid === $parent)
            ->map(fn ($area) => [
                'cd' => (int) $area->cd,
                'name' => (string) $area->name,
                'children' => $this->branch($areas, (int) $area->cd),
            ])
            ->values()
            ->all();
    }

    private function depth(array $tree): int
    {
        $max = 0;
        foreach ($tree as $node) {
            $max = max($max, 1 + $this->depth($node['children']));
        }

        return $max;
    }

    /**
     * Tiendas del árbol (Shop::getList(): disp_flg = '1' ORDER BY touroku_date DESC) y si van fuera
     * del árbol (.areaNonShopLabel): sin área, área 0, área borrada/desactivada o inexistente.
     */
    public function areaShops(): array
    {
        $existing = DB::table('tb_area')->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
        $fallen = DB::table('tb_area')
            ->where(fn ($query) => $query->where('disp_flg', '9')->orWhereRaw("coalesce(flg, '') <> '1'"))
            ->pluck('cd')->map(fn ($cd) => (int) $cd)->all();

        return Shop::query()->withDeleted()->where('disp_flg', '1')->orderByDesc('touroku_date')->get()
            ->map(fn (Shop $shop) => [
                'cd' => $shop->cd,
                'kaishamei' => (string) $shop->kaishamei,
                'areaCd' => $shop->area_cd,
                'isClosed' => (bool) $shop->is_closed,
                'withoutArea' => $shop->area_cd === null
                    || $shop->area_cd === 0
                    || in_array($shop->area_cd, $fallen, true)
                    || ! in_array($shop->area_cd, $existing, true),
            ])
            ->all();
    }
}

<?php

namespace App\Services\Mypage;

use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use App\Models\Shop;
use App\Services\Terminales\SignageFeedBuilder;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * 求人情報 (system/category6/recruit/recruit_*.php) sobre tb_recruit.
 *
 * - tb_shop_cd = -1 es 全店舗 (solo lo ofrece マスター).
 * - Imágenes en el disco public: hp/uploads/c6/recruit/<cd>/<n>.jpg y su miniatura <n>_t.jpg (100×100),
 *   n = 1 y 2 (pic_flg1, pic_flg2). Los temporales del AjaxUpload, en hp/uploads/c6/recruit/temp/.
 * - Al guardar o borrar se reescribe el floormap.xml de la señalización (lo leen los reproductores),
 *   como recruit_register.php con xml_info_up.php.
 */
class RecruitService
{
    public const FOLDER = 'hp/uploads/c6/recruit';

    public const ALL_SHOPS = -1;

    /** 機能cd de cKengen */
    public const KENGEN = 1023;

    public const DEFAULT_KENSU = 10;

    /** Campos de texto del formulario que van tal cual a tb_recruit */
    public const TEXT_FIELDS = [
        'boshu_headline', 'shigoto_naiyou', 'koyou_keitai', 'oubo_shikaku', 'kinmu_chi', 'kinmu_jikan',
        'kyuyo', 'kyuka', 'taigu', 'kaisha_name', 'add', 'tel', 'fax', 'jigyou_naiyou', 'oubo_houhou',
        'mensetsuchi', 'tantou',
    ];

    public function __construct(
        private readonly LegacyPageControl $pageControl,
        private readonly LegacyTempImages $tempImages,
        private readonly AppImageResizer $resizer,
        private readonly MypageShop $shop,
    ) {}

    /** recruit_list.php: disp_flg = '1' (y la tienda de la sesión si no es master) ORDER BY update_date DESC */
    public function paginate(Request $request): array
    {
        $query = Recruit::query()->where('disp_flg', '1')->orderByDesc('update_date');
        if (! $this->shop->isMaster()) {
            $query->where('tb_shop_cd', (int) $this->shop->current());
        }

        return $this->pageControl->paginate($query, $request, self::DEFAULT_KENSU);
    }

    public function find(mixed $cd): ?Recruit
    {
        return is_numeric($cd) ? Recruit::query()->withDeleted()->whereKey((int) $cd)->first() : null;
    }

    /** Tiendas del formulario: tb_shop disp_flg <> '9' ORDER BY cd; sin マスター, solo la de la sesión */
    public function shops(): Collection
    {
        return $this->shop->selectableShops();
    }

    /** 職種 de una tienda: disp_flg = '1' AND (tb_shop_cd IS NULL OR tb_shop_cd = <tienda>) ORDER BY cd */
    public function categoriesFor(mixed $shopCd): Collection
    {
        if (! is_numeric($shopCd)) {
            return collect();
        }

        return RecruitCategory::query()->where('disp_flg', '1')
            ->where(fn ($q) => $q->whereNull('tb_shop_cd')->orWhere('tb_shop_cd', (int) $shopCd))
            ->orderBy('cd')->get();
    }

    /** ¿Hay algún 職種 activo? (alerta 「募集職種が登録されていません」 de recruit_new.php) */
    public function hasCategories(mixed $shopCd): bool
    {
        if (filled($shopCd)) {
            return $this->categoriesFor($shopCd)->isNotEmpty();
        }

        return RecruitCategory::query()->where('disp_flg', '1')->exists();
    }

    /** recruit_register.php: alta ($recruit null) o modificación */
    public function save(array $data, ?Recruit $recruit): Recruit
    {
        $recruit ??= new Recruit;

        // recruit_register.php limpia con get_form() (LegacyText::form): texto plano, se imprime con {{ }}
        foreach (self::TEXT_FIELDS as $field) {
            $recruit->{$field} = LegacyText::form($data[$field] ?? '');
        }
        $recruit->boshu_shokushu = (int) ($data['boshu_shokushu'] ?? 0);
        $recruit->tb_shop_cd = (int) ($data['tb_shop_cd'] ?? 0);
        $recruit->disp_flg = '1';

        $temps = [];
        foreach ([1, 2] as $n) {
            $picName = (string) ($data['pic_name' . $n] ?? '');
            if ($picName === 'delete') {
                $recruit->{'pic_flg' . $n} = '';
            } elseif (($path = $this->tempImages->find(self::FOLDER, $picName)) !== null) {
                $recruit->{'pic_flg' . $n} = '1';
                $temps[$n] = $path;
            }
        }
        $recruit->save();

        foreach ($temps as $n => $path) {
            $this->storeImage($recruit->cd, $n, $path);
        }

        $this->refreshSignage();

        return $recruit;
    }

    /** recruit_delete.php: disp_flg = '9' */
    public function delete(mixed $cd): void
    {
        if (is_numeric($cd)) {
            Recruit::query()->withDeleted()->whereKey((int) $cd)->update(['disp_flg' => '9']);
            $this->refreshSignage();
        }
    }

    private function storeImage(int $cd, int $n, string $tempPath): void
    {
        $disk = Storage::disk('public');
        $contents = $disk->get($tempPath);
        $dir = self::FOLDER . '/' . $cd;

        $disk->put("{$dir}/{$n}.jpg", $contents);
        $disk->put("{$dir}/{$n}_t.jpg", $this->resizer->scale($contents, 'jpg', 100, 100, 'jpg') ?? $contents);
        $disk->delete($tempPath);
    }

    private function refreshSignage(): void
    {
        app(SignageFeedBuilder::class)->writeFloormap();
    }

    // --- Datos de las vistas ------------------------------------------------------------------

    public function imageUrl(int $cd, int $n): string
    {
        return asset(self::FOLDER . "/{$cd}/{$n}.jpg") . '?t=' . time();
    }

    public function tempImageUrl(string $picName): ?string
    {
        $path = $this->tempImages->find(self::FOLDER, $picName);

        return $path !== null ? asset($path) . '?t=' . time() : null;
    }

    /** 店舗 del detalle: -1 → 全店舗 (null aquí; el texto lo pone la vista), si no el kaishamei */
    public function shopName(Recruit $recruit): ?string
    {
        if ((int) $recruit->tb_shop_cd === self::ALL_SHOPS) {
            return null;
        }

        return (string) Shop::query()->withDeleted()->whereKey((int) $recruit->tb_shop_cd)->value('kaishamei');
    }

    /** 募集職種 del detalle (sin filtrar disp_flg, como el legacy) */
    public function categoryName(Recruit $recruit): string
    {
        return (string) RecruitCategory::query()->withDeleted()->whereKey((int) $recruit->boshu_shokushu)->value('shokushu');
    }
}

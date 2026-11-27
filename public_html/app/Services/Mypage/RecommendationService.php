<?php

namespace App\Services\Mypage;

use App\Models\Mypage\MobileNavi;
use App\Models\Mypage\Recommendation;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * オススメ管理 (system/category6/user_hp/recommend*.php) sobre tb_hp_recommend.
 * Imágenes en el disco public: hp/uploads/c6/user_hp/recommend/<cd>/<cd>_big.<ext> (la subida, 480 de
 * ancho) y <cd>.<ext> (150 de ancho, la que muestra la lista y la app); `img_type` = jpg|gif.
 */
class RecommendationService
{
    public const FOLDER = 'hp/uploads/c6/user_hp/recommend';

    /** cd de tb_mobile_navi de オススメ (recommend.php: "9番がオススメのcd") */
    public const NAVI_CD = 9;

    /** 機能cd de cKengen */
    public const KENGEN = 1016;

    public const DEFAULT_KENSU = 10;

    public function __construct(
        private readonly LegacyPageControl $pageControl,
        private readonly LegacyTempImages $tempImages,
        private readonly AppImageResizer $resizer,
        private readonly MypageShop $shop,
    ) {}

    /** Nombre del menú (tb_mobile_navi cd 9) con mb_convert_kana 'KV' como el legacy */
    public function menuName(): string
    {
        $name = (string) MobileNavi::query()->withDeleted()->whereKey(self::NAVI_CD)->value('name');

        return mb_convert_kana($name, 'KV');
    }

    /** オススメ de la lista: disp_flg = '1' ORDER BY disp_date DESC, update DESC */
    public function paginate(Request $request): array
    {
        $query = Recommendation::query()->where('disp_flg', '1')->latestFirst();

        return $this->pageControl->paginate($query, $request, self::DEFAULT_KENSU);
    }

    public function find(mixed $cd): ?Recommendation
    {
        if (! is_numeric($cd)) {
            return null;
        }

        return Recommendation::query()->where('disp_flg', '1')->whereKey((int) $cd)->first();
    }

    /** 表示情報を更新: marcado → hyouji_flg '', sin marcar → '9' */
    public function updateVisibility(array $cds, array $visible): void
    {
        foreach ($cds as $cd) {
            Recommendation::query()->withDeleted()->whereKey((int) $cd)
                ->update(['hyouji_flg' => isset($visible[$cd]) ? '' : Recommendation::HIDDEN_APP]);
        }
    }

    /**
     * Alta o modificación (recommend_register.php). $data con las claves del formulario.
     * El temporal se copia a <cd>_big y se reduce a <cd> (150 de ancho).
     */
    public function save(array $data, ?Recommendation $recommendation): Recommendation
    {
        $recommendation ??= new Recommendation;

        $picName = (string) ($data['pic_name'] ?? '');
        $tempPath = $picName !== 'delete' ? $this->tempImages->find(self::FOLDER, $picName) : null;

        $recommendation->fill([
            'tb_shop_cd' => filled($data['tb_shop_cd'] ?? null) ? (int) $data['tb_shop_cd'] : null,
            // Texto como get_form2() de recommend_register.php (CONVENCIONES §4): se imprime con {!! !!}
            'naiyou' => LegacyText::input($data['news_naiyou'] ?? ''),
            'disp_date' => ($data['news_year'] ?? '') . '/' . ($data['news_month'] ?? '') . '/' . ($data['news_day'] ?? ''),
            'title' => LegacyText::input($data['title'] ?? ''),
            'link' => LegacyText::input($data['link'] ?? ''),
            'pic_link' => LegacyText::input($data['pic_link'] ?? ''),
            'link_type' => LegacyText::input($data['link_type'] ?? ''),
            'hyouji_flg' => ($data['hyouji_flg'] ?? '') === Recommendation::HIDDEN_APP ? Recommendation::HIDDEN_APP : '',
            'disp_flg' => '1',
        ]);
        if ($picName === 'delete') {
            $recommendation->img_flg = '';
        } elseif ($tempPath !== null) {
            $recommendation->img_flg = '1';
        }
        $recommendation->save();

        if ($tempPath !== null) {
            $this->storeImage($recommendation, $tempPath);
        }

        return $recommendation;
    }

    private function storeImage(Recommendation $recommendation, string $tempPath): void
    {
        $disk = Storage::disk('public');
        $extension = str_ends_with($tempPath, '.gif') ? 'gif' : 'jpg';
        $cd = $recommendation->cd;
        $dir = self::FOLDER . '/' . $cd;
        $contents = $disk->get($tempPath);

        $disk->put("{$dir}/{$cd}_big.{$extension}", $contents);
        $disk->put("{$dir}/{$cd}.{$extension}", $this->resizer->scale($contents, $extension, 150, null, $extension) ?? $contents);
        $disk->delete($tempPath);

        $recommendation->forceFill(['img_type' => $extension])->saveQuietly();
    }

    /** recommend_delete_register.php: delete_flg 'del' → disp_flg 9, 'recover' → 1 */
    public function delete(mixed $cd, string $flag): void
    {
        $value = match ($flag) {
            'del' => '9',
            'recover' => '1',
            default => null,
        };
        if ($value !== null && is_numeric($cd)) {
            Recommendation::query()->withDeleted()->whereKey((int) $cd)->update(['disp_flg' => $value]);
        }
    }

    // --- Datos de las vistas ------------------------------------------------------------------

    /** Imagen del formulario de modificación: <cd>_big si existe, extensión de img_type */
    public function formImageUrl(?Recommendation $recommendation): ?string
    {
        if ($recommendation === null || ! $recommendation->img_flg) {
            return null;
        }
        $extension = $recommendation->img_type === 'gif' ? 'gif' : 'jpg';
        $cd = $recommendation->cd;
        $big = self::FOLDER . "/{$cd}/{$cd}_big.{$extension}";
        $path = Storage::disk('public')->exists($big) ? $big : self::FOLDER . "/{$cd}/{$cd}.{$extension}";

        return asset($path) . '?t=' . time();
    }

    /**
     * Miniatura y enlace a la grande de la lista (img_flg == 1). El GIF tiene prioridad.
     *
     * @return array{0: ?string, 1: ?string} [imagen, enlace]
     */
    public function listImage(Recommendation $recommendation): array
    {
        if ((float) $recommendation->img_flg != 1) {
            return [null, null];
        }
        $disk = Storage::disk('public');
        $cd = $recommendation->cd;
        $extension = $disk->exists(self::FOLDER . "/{$cd}/{$cd}.gif") ? 'gif' : 'jpg';
        $image = self::FOLDER . "/{$cd}/{$cd}.{$extension}";
        $big = self::FOLDER . "/{$cd}/{$cd}_big.{$extension}";

        return [asset($image), asset($disk->exists($big) ? $big : $image)];
    }

    /** CrmSystem\Mypage\OutputHtml::normalizeAriticleLink */
    public function articleLink(?string $type, ?string $url): string
    {
        if (! $url) {
            return '';
        }

        return match ($type) {
            'http://' => preg_match('/^https?:\/\//', $url) ? $url : $type . $url,
            'tel:', 'mailto:' => $type . $url,
            default => '',
        };
    }

    /** Columna 登録店舗: NULL → 全店舗; si no, el nombre (getShopNameFromCd, '' si no existe) */
    public function shopLabel(Recommendation $recommendation): ?string
    {
        return $recommendation->tb_shop_cd === null ? null : $this->shop->shopName($recommendation->tb_shop_cd);
    }
}

<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Accesos directos de la cabecera (`tb_bookmark`, ショートカット管理): `bookmark_flg = '1'` visibles,
 * orden `user_rank`. Si tiene `tb_kengen_contents_cd` + `tb_kengen_contents_node`, solo con ese permiso.
 *
 * `url` guarda la dirección legacy (copia literal de bookmark_reset.sql). Muchas pantallas ya no están en esa
 * URL (スタッフ → /admin/operaciones, クーポン → /admin/promocion…): `targetUrl()` da la de Laravel, o null si la
 * pantalla se descartó o está fuera de alcance (ese acceso no se ofrece).
 */
class Bookmark extends LegacyModel
{
    protected $table = 'tb_bookmark';

    protected $casts = [
        'cd' => 'integer',
        'user_rank' => 'integer',
        'tb_kengen_contents_cd' => 'integer',
        'tb_kengen_contents_node' => 'integer',
    ];

    /**
     * Pantallas que cambiaron de URL y no siguen la regla de category3 (ver resolveUrl()). null = sin pantalla:
     * descartadas por no tener consumidor (Doc/diseno/contenido-terminales.md: トピックス, ヘッダ画像, 携帯HP,
     * フォトギャラリー) o fuera de alcance (touchcation: kiosk).
     */
    public const MOVED = [
        '/system/category5/staff/staff_entry.php' => '/admin/operaciones/staff/create',
        '/system/category5/staff/staff_list.php' => '/admin/operaciones/staff',
        '/system/category5/staff/form_config.php' => '/admin/operaciones/staff-fields',
        '/system/category5/staff/information.php' => '/admin/operaciones/information',
        '/system/category6/user_hp/mobile_hp_edit.php' => '/admin/mypage/app-images/all',
        '/system/category6/user_hp/recommend.php' => '/admin/mypage/recommendations',
        '/system/category6/recruit/recruit_new.php' => '/admin/mypage/recruits/create',
        '/system/category6/recruit/recruit_list.php' => '/admin/mypage/recruits',
        '/system/category6/recruit/recruit_category.php' => '/admin/mypage/recruit-categories',
        '/system/category6/user_hp/topics.php' => null,
        '/system/category6/user_hp/header.php' => null,
        '/system/category6/shop_hp/mobile_hp_edit.php' => null,
        '/system/category6/shop_hp/whatsnew.php' => null,
        '/system/category6/shop_hp/header.php' => null,
        '/system/category6/shop_hp/company.php' => null,
        '/system/category6/shop_hp/freepage.php' => null,
        '/system/category6/digital_catalog/digital_catalog_new.php' => null,
        '/system/category6/digital_catalog/digital_catalog_list.php' => null,
        '/system/category6/digital_catalog/digital_catalog_tag.php' => null,
        '/system/category7/kiosk/movie_program_list.php' => null,
        '/system/category7/kiosk/display_list.php' => null,
    ];

    /** @var array<string, string|null> URL ya resueltas en esta petición */
    private static array $resolved = [];

    /** URL de Laravel para este acceso, o null si no tiene pantalla */
    public function targetUrl(): ?string
    {
        return self::resolveUrl((string) $this->url);
    }

    /**
     * La URL legacy si sigue teniendo ruta; si no, la de MOVED; si no, la regla de 販売促進管理
     * (`/system/category3/<dir>/<archivo>.php` → `/admin/promocion/<dir>/<archivo>`) si esa ruta existe.
     */
    public static function resolveUrl(string $url): ?string
    {
        if (! array_key_exists($url, self::$resolved)) {
            self::$resolved[$url] = self::resolvePath($url);
        }

        return self::$resolved[$url];
    }

    private static function resolvePath(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        if (self::hasRoute($path)) {
            return $url;
        }
        if (array_key_exists($path, self::MOVED)) {
            return self::MOVED[$path];
        }
        if (preg_match('#^/system/category3/(\w+)/(\w+)\.php$#', $path, $m) && self::hasRoute($moved = "/admin/promocion/{$m[1]}/{$m[2]}")) {
            return $moved . ($query ? '?' . $query : '');
        }

        return null;
    }

    /** ¿Hay una ruta GET para esta dirección? */
    public static function hasRoute(string $path): bool
    {
        try {
            Route::getRoutes()->match(Request::create($path, 'GET'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

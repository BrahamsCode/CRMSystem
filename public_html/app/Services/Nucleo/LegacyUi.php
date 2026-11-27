<?php

namespace App\Services\Nucleo;

use App\Http\Controllers\Admin\Nucleo\CategoryTopController;
use App\Models\AdminMaster;
use App\Models\BaseSystem;
use App\Models\BaseSystemChild;
use App\Models\BaseSystemGrandchild;
use App\Models\Bookmark;
use App\Models\ContentsMaster;
use App\Models\Office;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Datos de la UI del legacy para los layouts (`class/Base.php`, `inc/header.php`, `left_navi*`):
 * versión de UI (`admin_master.base_system_version_flg`: 2 = CRM2 con mainStyle.css, 1 = clásica),
 * nombre del sitio, pestañas de módulos, accesos directos y el menú lateral desde `config/menu/*.php`.
 * Una instancia por petición (scoped).
 */
class LegacyUi
{
    private ?AdminMaster $adminMaster = null;

    private ?Office $office = null;

    public function versionFlg(): string
    {
        return (string) $this->adminMaster()->base_system_version_flg === '2' ? '2' : '1';
    }

    public function adminMaster(): AdminMaster
    {
        return $this->adminMaster ??= AdminMaster::current();
    }

    public function siteName(): string
    {
        return ($this->office ??= Office::current())->siteName();
    }

    public function sellerId(): int
    {
        return (int) $this->adminMaster()->seller_id;
    }

    /** Número de categoría de la URL (`/system/category<N>/…`), como Base::getSelectCategoryNo */
    public function categoryFromUrl(): ?int
    {
        return preg_match('#^system/category(\d+)(/|$)#', request()->path(), $m) ? (int) $m[1] : null;
    }

    /**
     * Sufijo de la clase `pageCategory` del <body>, como Base::getSelectCategoryNo: el segundo tramo de la URL sin
     * "category" (`/system/category1/…` → "1", `/system/select.php` → "select.php"). Las URL nuevas (`/admin/…`)
     * usan la categoría del menú del módulo (`$fallback`).
     */
    public function pageCategory(?int $fallback = null): string
    {
        $segments = explode('/', request()->path());

        return ($segments[0] ?? '') === 'system' && isset($segments[1])
            ? str_replace('category', '', $segments[1])
            : (string) $fallback;
    }

    /** Partes HTML que cambian entre la UI 1 (imágenes) y la 2 (texto): Base::createHtmlPart */
    public function part(string $name, array $data = []): string
    {
        $v2 = $this->versionFlg() === '2';
        $e = fn ($s) => e((string) $s);

        return match ($name) {
            'headerLogo' => $v2 ? '<img src="/system/img/v2/logo.png" alt="総合業務管理システム" />' : '<img src="/system/img/logo.gif" alt="総合業務管理システム" />',
            'headerShiire' => $v2 ? '仕入れ' : '<img src="/system/img/hn4_off.gif" width="100" height="35" alt="仕入れ" />',
            'headerOtoiawase' => $v2 ? 'お問合せ' : '<img src="/system/img/hn_contact_off.gif" width="99" height="35" alt="お問合わせ" />',
            'headerSupport' => $v2 ? 'サポート' : '<img src="/system/img/hn3_off.gif" width="99" height="35" alt="QandA" />',
            'headerBackTop' => $v2 ? '総合トップ' : '<img src="/system/img/hn1_off.gif" width="99" height="35" alt="総合トップへ戻る" />',
            'headerLogout' => $v2 ? 'ログアウト' : '<img src="/system/img/hn2_off.gif" width="99" height="35" alt="ログアウト" />',
            'functionSelect' => $v2 ? $e($data['system_name']) : '<img src="img/img_select' . $e($data['cd']) . '.gif" alt="' . $e($data['system_name']) . '" />',
            'leftNavi' => $v2 ? $e($data['systemName']) : '<img src="/system/img/' . $e($data['imageName']) . '" width="195" height="61" alt="' . $e($data['systemName']) . '" />',
            'bookmarkArea' => $v2 ? '' : '<img src="/system/img/bookmark_area_arrow_btn_' . $e($data['bookmarkArea_flg']) . '.gif" alt="BookMark" onclick="BookmarkAreaFunc();" />',
            'bookmarkAreaProperty' => $v2 ? '' : ' style="margin-top: 80px;"',
            'bookmarkAreaProperty2' => $v2 ? '' : ' style="display: block;"',
            default => '',
        };
    }

    /**
     * Módulos visibles para el staff (select.php y pestañas de header.php): activos por `narabi`,
     * 0 solo master, 8 nunca, SNS管理 solo con kengen[1034][1] o master.
     */
    public function visibleModules(?Staff $staff): Collection
    {
        return BaseSystem::query()->active($this->versionFlg())->get()
            ->reject(fn (BaseSystem $m) => $m->cd === 0 && ! $staff?->isMaster())
            ->reject(fn (BaseSystem $m) => $m->cd === BaseSystem::RETIRED_CD)
            ->reject(fn (BaseSystem $m) => $m->system_name === BaseSystem::SNS_NAME && ! ($staff && Gate::forUser($staff)->allows('sns.configure')))
            ->values();
    }

    /** Enlace 仕入れ de la cabecera (contents_master shiire-ya/btn_disp_flg) o null */
    public function shiireUrl(?Staff $staff): ?string
    {
        $row = ContentsMaster::query()->where('category', 'shiire-ya')->where('field', 'btn_disp_flg')
            ->where('disp_flg', '1')->where('hyouji_flg', '1')->orderByDesc('cd')->first();
        if (! $row) {
            return null;
        }
        $subDomain = preg_replace('/.crm-s.net/', '', request()->getHost());

        return $row->text_data . '?sub_domain=' . $subDomain . '&rank=.' . $staff?->legacyRank() . '&shop=' . $staff?->shop_cd;
    }

    /**
     * Base::isExtShopDomain(): modo "tienda en app" (`contents_master` mycolle/app_layout_mode = 1).
     * Oculta campos (責任者, 給与丸め…) y cambia menús en varias pantallas.
     */
    public function isExtShopDomain(): bool
    {
        return (int) ContentsMaster::text('mycolle', 'app_layout_mode', '0') === 1;
    }

    /** ¿Dominio crm-s? (enlaces サポート de header.php y select.php) */
    public function isCrmsDomain(): bool
    {
        return (explode('.', request()->getHost())[1] ?? null) === 'crm-s';
    }

    /** Accesos directos visibles (tb_bookmark), con el permiso de cada uno */
    public function bookmarks(?Staff $staff): Collection
    {
        return Bookmark::query()->where('bookmark_flg', '1')->orderBy('user_rank')->get()
            ->filter(function (Bookmark $b) use ($staff) {
                if ($b->tb_kengen_contents_cd === null || $b->tb_kengen_contents_node === null) {
                    return true;
                }

                return $staff && Gate::forUser($staff)->allows("kengen.{$b->tb_kengen_contents_cd}.{$b->tb_kengen_contents_node}");
            })->values();
    }

    /**
     * Texto calculado del menú (`label_from` / `title_from` = [Clase::class, 'metodo']): el resultado, o
     * el texto fijo si devuelve vacío. Ej.: オススメ管理 titulado con el nombre de tb_mobile_navi.
     */
    private static function dynamicText(array $callable, string $default): string
    {
        $text = is_callable($callable) ? (string) call_user_func($callable) : '';

        return $text !== '' ? $text : $default;
    }

    /**
     * Menú lateral de un módulo desde `config/menu/<clave>.php` (ver NUCLEO.md), filtrado por
     * hijo activo (`tb_base_system_child`), nieto activo y permisos. Null si la clave no existe.
     *
     * @return array{category: ?int, login_class: string, blocks: array}|null
     */
    public function menu(string $key, ?Staff $staff): ?array
    {
        $config = config("menu.$key");
        if (! is_array($config)) {
            return null;
        }
        // Sin 'category' en el config, la del mapa de portadas (system/category<N>/index.php)
        $category = $config['category'] ?? (array_search($key, CategoryTopController::MENUS, true) ?: null);
        $children = $category === null ? collect() : BaseSystemChild::query()->active((int) $category)->pluck('system_name')->values();
        $gate = $staff ? Gate::forUser($staff) : null;
        $allowed = fn ($can) => $can === null || ($gate && collect((array) $can)->contains(fn ($ability) => $gate->allows($ability)));

        $blocks = [];
        foreach ($config['blocks'] ?? [] as $i => $block) {
            if (isset($block['child']) && ! $children->contains($block['child'])) {
                continue;
            }
            if (! $allowed($block['can'] ?? null)) {
                continue;
            }
            $links = collect($block['links'] ?? [])
                ->filter(fn ($link) => $allowed($link['can'] ?? null))
                ->filter(fn ($link) => ! isset($link['grandchild']) || BaseSystemGrandchild::activeLike($link['grandchild']))
                ->map(fn ($link) => isset($link['label_from']) ? ['label' => self::dynamicText($link['label_from'], $link['label'] ?? '')] + $link : $link)
                ->values()->all();
            if (isset($block['title_from'])) {
                $block['title'] = self::dynamicText($block['title_from'], $block['title'] ?? '');
            }

            // Los bloques ligados a un hijo siguen el orden de tb_base_system_child (rank, cd), como left_navi1
            $order = isset($block['child']) ? $children->search($block['child']) : $i;
            $blocks[] = array_merge($block, ['links' => $links, '_order' => [$order, $i], 'ac' => $block['ac'] ?? 'ac' . ($i + 1)]);
        }
        usort($blocks, fn ($a, $b) => $a['_order'] <=> $b['_order']);

        return [
            'category' => $category,
            'login_class' => $config['login_class'] ?? 'loginName' . $category,
            'blocks' => $blocks,
        ];
    }
}

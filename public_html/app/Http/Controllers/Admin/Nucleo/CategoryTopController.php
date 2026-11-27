<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use App\Models\BaseSystem;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Portadas de los módulos (system/category<N>/index.php): imagen coverN.gif y el menú lateral del módulo.
 * 顧客管理 y 販売促進管理 añaden los contadores de socios (inc/entry_counter.php). category9 redirige a su
 * agenda (yoyaku/time_table.php), como el legacy. La de category0 es Base\IndexController.
 */
class CategoryTopController extends Controller
{
    /** Clave de config/menu de cada categoría */
    public const MENUS = [1 => 'clientes', 3 => 'promocion', 5 => 'operaciones', 6 => 'mypage', 7 => 'terminales'];

    public function show(int $category): View
    {
        $systemName = (string) BaseSystem::query()->where('cd', $category)->where('system_flg', '1')->value('system_name');

        return view('admin.nucleo.category-top', [
            'category' => $category,
            'menuKey' => self::MENUS[$category],
            'systemName' => $systemName,
            // category3/index.php fija el sufijo del título; el resto usa el nombre del sitio
            'titleSuffix' => $category === 3 ? '-総合業務管理システム-' : null,
            'counter' => in_array($category, [1, 3], true) ? $this->entryCounter(auth('admin')->user()) : null,
        ]);
    }

    public function reservations(): RedirectResponse
    {
        return redirect('/system/category9/yoyaku/time_table.php');
    }

    /**
     * inc/entry_counter.php: socios (touroku_kubun = 2) en total, del mes y del día; los que no son master solo
     * cuentan su tienda. `del1` = 退会済 (disp_flg 0), `del2` = 不達 (mailmaga_flg 9).
     */
    private function entryCounter(?Staff $staff): array
    {
        $shopCd = $staff && ! $staff->isMaster() ? (int) session('shop_cd', $staff->shop_cd) : null;
        $members = fn () => DB::table('tb_customer')->where('touroku_kubun', '2')
            ->when($shopCd !== null, fn ($q) => $q->where('tb_shop_cd', $shopCd));
        $since = fn (string $column, int $from) => fn ($q) => $q->whereRaw("(CASE WHEN {$column} ~ '^[0-9]+$' THEN {$column}::bigint END) >= ?", [$from]);

        $now = Carbon::now();
        $periods = ['all' => null, 'month' => $now->copy()->startOfMonth(), 'day' => $now->copy()->startOfDay()];
        $counts = [];
        foreach ($periods as $key => $from) {
            $ts = $from?->timestamp;
            $counts[$key] = [
                'from' => $from?->format('Y/m/d'),
                'add' => $members()->where('disp_flg', '1')->when($ts, $since('touroku_date', (int) $ts))->count(),
                'del1' => $members()->where('disp_flg', '0')->when($ts, $since('update_date', (int) $ts))->count(),
                'del2' => $members()->where('disp_flg', '1')->where('mailmaga_flg', '9')->when($ts, $since('update_date', (int) $ts))->count(),
            ];
        }

        return ['counts' => $counts, 'shopQuery' => $shopCd !== null ? '&tb_shop_cd=' . $shopCd : '', 'now' => $now];
    }
}

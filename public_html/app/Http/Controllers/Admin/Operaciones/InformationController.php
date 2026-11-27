<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\BelongPost;
use App\Models\Operaciones\EmploymentSystem;
use App\Models\Operaciones\Information;
use App\Models\Operaciones\OfficialPosition;
use App\Models\Shop;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * インフォメーション管理 (information.php, information_register.php, information_delete_register.php).
 * Texto como get_form2() del legacy: LegacyText::input() al guardar y {!! !!} al pintar.
 */
class InformationController extends Controller
{
    public const LIST_LIMIT = 10;

    public function index(Request $request): View
    {
        $staff = $request->user('admin');
        $isMaster = $staff->isMaster();

        // El master ve todos; el resto, los de su tienda y los de 全店舗
        $list = Information::query()->latestFirst()->limit(self::LIST_LIMIT);
        if (! $isMaster) {
            $list->visibleForShop($staff->shop_cd);
        }

        $shops = Shop::query()->where('disp_flg', '<>', '9')->orderBy('cd')->pluck('kaishamei', 'cd');

        return view('admin.operaciones.information.index', [
            'information' => $request->filled('cd') ? Information::find((int) $request->query('cd')) : null,
            'informations' => $list->get(),
            'shopNames' => $shops,
            'shopOptions' => $isMaster ? $shops : $shops->only([$staff->shop_cd]),
            'isMaster' => $isMaster,
            'currentShop' => $staff->shop,
            'employmentSystems' => EmploymentSystem::orderBy('cd')->pluck('employmentsystem')->all(),
            'belongPosts' => BelongPost::orderBy('cd')->pluck('belongpost')->all(),
            'officialPositions' => OfficialPosition::orderBy('cd')->pluck('officialposition')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Information::create($this->attributes($request) + ['disp_flg' => Information::ACTIVE_FLAG]);

        return to_route('admin.operaciones.information.index');
    }

    public function update(Request $request, int $cd): RedirectResponse
    {
        Information::findOrFail($cd)->update($this->attributes($request));

        return to_route('admin.operaciones.information.index');
    }

    /** information_delete_register.php: disp_flg = '9' */
    public function destroy(int $cd): RedirectResponse
    {
        Information::find($cd)?->delete();

        return to_route('admin.operaciones.information.index');
    }

    /** Columnas como information_register.php; cargos separados por espacio, shop_cd vacío = NULL (全店舗) */
    private function attributes(Request $request): array
    {
        $text = fn (string $key) => LegacyText::input($request->input($key));
        $positions = $request->input('officialposition');
        $positions = is_array($positions)
            ? implode(' ', array_map(fn ($p) => LegacyText::input($p), $positions))
            : '';
        $shopCd = $request->input('shop_cd');

        return [
            'shop_cd' => is_scalar($shopCd) && $shopCd !== '' ? (int) $shopCd : null,
            'comment' => $text('comment'),
            'belongpost' => $text('belongpost'),
            'employmentsystem' => $text('employmentsystem'),
            'officialposition' => $positions,
            'staff_name' => $text('staff_name'),
        ];
    }
}

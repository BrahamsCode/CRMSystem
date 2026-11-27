<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\ReservationMenuRequest;
use App\Models\AdminMaster;
use App\Models\Reservations\Facility;
use App\Models\Reservations\ReservationMenu;
use App\Models\Reservations\ReservationMenuCategory;
use App\Services\Reservations\ReservationMenuImageStore;
use App\Services\Reservations\ReservationMenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 予約項目管理 (legacy: category9/yoyaku/yoyaku_koumoku_new.php, yoyaku_koumoku_register.php,
 * ajax_yoyaku_koumoku_new.php). Ventana emergente sin navegación.
 */
class ReservationMenuController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private ReservationMenuService $menus) {}

    /** Formulario (alta, o edición con `?cd=`) y contenedor de la lista; `?shop=` elige tienda (master) */
    public function index(Request $request, ReservationMenuImageStore $images): View
    {
        $menu = filled($request->query('cd'))
            ? ReservationMenu::query()->where('disp_flg', ReservationMenu::ACTIVE_FLAG)->find((int) $request->query('cd'))
            : null;
        $shopCd = $this->resolveShopCd($menu?->tb_shop_cd, $request->query('shop'));
        $yoyakuSetsubiFlg = (string) AdminMaster::current()->yoyaku_setsubi_flg;

        return view('admin.reservas.menus', [
            'menu' => $menu,
            'shopCd' => $shopCd,
            'shopName' => $shopCd === ReservationMenu::ALL_SHOPS ? null : $this->shopName($shopCd),
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
            'categories' => ReservationMenuCategory::query()->where('disp_flg', ReservationMenuCategory::ACTIVE_FLAG)->orderBy('rank')->get(),
            'yoyakuSetsubiFlg' => $yoyakuSetsubiFlg,
            'facilities' => $yoyakuSetsubiFlg === '1' && $shopCd
                ? Facility::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', Facility::ACTIVE_FLAG)->orderBy('cd')->get()
                : collect(),
            'menuEndTime' => $menu?->endTimeParts() ?? [null, null],
            'imageUrl' => $menu ? $images->thumbnailUrl($menu->cd) : null,
        ]);
    }

    public function store(ReservationMenuRequest $request): RedirectResponse
    {
        return $this->save($request, new ReservationMenu);
    }

    public function update(ReservationMenuRequest $request, ReservationMenu $menu): RedirectResponse
    {
        return $this->save($request, $menu);
    }

    public function destroy(ReservationMenu $menu): RedirectResponse
    {
        $this->menus->delete($menu);

        return redirect()->route('admin.reservas.menus.index', ['shop' => $menu->tb_shop_cd]);
    }

    /** Lista de menús (lo que devolvía ajax_yoyaku_koumoku_new.php sin `send_type`) */
    public function list(Request $request): View|string
    {
        return $this->renderList($request);
    }

    /** ▲▼ (`send_type` = rankup / rankdown, `send_targetCd`) y la lista actualizada */
    public function sort(Request $request): View|string
    {
        $request->validate(['send_type' => ['nullable', 'in:rankup,rankdown'], 'send_targetCd' => ['nullable', 'integer']]);

        if (filled($request->input('send_type')) && $menu = ReservationMenu::query()->find((int) $request->input('send_targetCd'))) {
            $this->menus->move($menu, $request->input('send_type'));
        }

        return $this->renderList($request);
    }

    private function save(ReservationMenuRequest $request, ReservationMenu $menu): RedirectResponse
    {
        $this->menus->save($menu, $request->validated());
        $this->menus->syncImage($menu, $request->file('img'), (int) $request->input('img_del_flg') === 1);

        return redirect()->route('admin.reservas.menus.index', ['shop' => $menu->tb_shop_cd]);
    }

    private function renderList(Request $request): View|string
    {
        // Sin tienda el legacy no devolvía nada (exit)
        if (blank($request->input('shop_cd'))) {
            return '';
        }

        $shopCd = (int) $request->input('shop_cd');
        $this->menus->normalizeRanks();
        $base = $this->menus->listSections($shopCd, ReservationMenu::TYPE_BASE);
        $options = $this->menus->listSections($shopCd, ReservationMenu::TYPE_OPTION);
        $allMenus = collect($base)->merge($options)->flatMap(fn ($section) => $section['menus']);

        return view('admin.reservas.partials.menu_list', [
            'shopCd' => $shopCd,
            'isMaster' => $this->isMasterUser(),
            'baseSections' => $base,
            'optionSections' => $options,
            'thumbnails' => $this->menus->thumbnails($allMenus),
        ]);
    }
}

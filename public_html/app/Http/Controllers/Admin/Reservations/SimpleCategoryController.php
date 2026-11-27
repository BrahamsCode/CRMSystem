<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Reservations\SimpleReservationCategory;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 予約カテゴリー登録・変更 de la reserva simple (legacy category3/reservation/category_entry.php,
 * category_register.php y category_delete.php) sobre `tb_reserve_name`.
 * Master elige tienda o 全店舗 (`tb_shop_cd` NULL); el resto solo ve y crea las de su tienda.
 */
class SimpleCategoryController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private readonly LegacyUi $legacyUi) {}

    /** category_entry.php (?cd= para modificar) */
    public function index(Request $request): View
    {
        $isMaster = $this->isMasterUser();
        $sessionShop = $this->sessionShopCd();
        $editing = is_numeric($request->query('cd')) ? SimpleReservationCategory::withDeleted()->find((int) $request->query('cd')) : null;

        $shops = $this->shopOptions()->when(! $isMaster, fn ($c) => $c->where('cd', $sessionShop)->values());
        $categories = SimpleReservationCategory::query()
            ->where('disp_flg', '1')
            ->when(! $isMaster, fn ($q) => $q->where('tb_shop_cd', (int) $sessionShop))
            ->orderByDesc('cd')
            ->get();

        return view('admin.reservas.simple.categories', [
            'editing' => $editing,
            // Tienda seleccionada: la de la categoría; 全店舗 (NULL) = '-1'
            'selectedShop' => $editing ? ($editing->tb_shop_cd === null ? '-1' : (string) $editing->tb_shop_cd) : null,
            'isMaster' => $isMaster,
            'shops' => $shops,
            'shopNames' => $this->shopOptions()->pluck('kaishamei', 'cd'),
            'categories' => $categories,
            'siteName' => $this->legacyUi->siteName(),
        ]);
    }

    /** category_register.php (act = new | up): alta con hatuban o cambio; vuelve a la lista en 2 s */
    public function store(Request $request): View
    {
        $act = (string) $request->input('act');
        if (in_array($act, ['new', 'up'], true)) {
            $shop = (string) $request->input('shop_cd');
            if (! $this->isMasterUser()) {
                $shop = (string) $this->sessionShopCd();
            }
            $values = [
                // get_form2(): el nombre se guarda con entidades HTML, como el legacy (se imprime con {!! !!})
                'reserve_cate_name' => LegacyText::input($request->input('cate_name')),
                'disp_flg' => '1',
                // '-1' = 全店舗 (NULL). El legacy no tocaba la columna al modificar a 全店舗; aquí sí se guarda NULL.
                'tb_shop_cd' => $shop === '-1' ? null : (int) $shop,
            ];

            DB::transaction(function () use ($act, $request, $values) {
                if ($act === 'new') {
                    SimpleReservationCategory::query()->create($values);
                } else {
                    SimpleReservationCategory::withDeleted()->whereKey((int) $request->input('cd'))->first()?->fill($values)->save();
                }
            });
        }

        return $this->processing('予約カテゴリー登録 ', '予約カテゴリー登録', '登録中です。');
    }

    /** category_delete.php: disp_flg = '9' */
    public function destroy(Request $request): View
    {
        $cd = $request->route('category') ?? $request->input('cd');
        if ($request->filled('delete_flg') && is_numeric($cd)) {
            SimpleReservationCategory::query()->whereKey((int) $cd)->first()?->delete();
        }

        return $this->processing('予約カテゴリー削除 ', '予約カテゴリー削除', '削除中です。');
    }

    private function processing(string $title, string $heading, string $message): View
    {
        return view('admin.reservas.simple.processing', [
            'title' => $title . $this->legacyUi->siteName(),
            'heading' => $heading,
            'message' => $message,
            'alt' => $message,
            'boxClass' => 'center',
            'seconds' => 2,
            'url' => route('admin.reservas.simple-categories.index'),
        ]);
    }
}

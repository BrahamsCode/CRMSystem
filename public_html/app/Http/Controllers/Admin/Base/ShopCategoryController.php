<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 店舗グループ (system/category0/shop/shop_category.php + _frm + _register): todo en una URL, como el legacy.
 * - GET ?cd=N: carga para editar. GET ?thisCd&thisRow&kind=up|down: cambia el orden con la vecina y renumera.
 * - POST act=new|up|del: guarda (hatuban, sort = n.º de activas + 1) o borra (flg = 9) y vuelve a la lista.
 */
class ShopCategoryController extends Controller
{
    public function __invoke(Request $request, LegacyUi $legacyUi): View
    {
        $act = (string) $request->input('act', '');
        if (in_array($act, ['new', 'up', 'del'], true)) {
            $mode = $this->register($act, $request);

            return view('admin.base.shops.wait', [
                'title' => '店舗グループ管理 - ' . $legacyUi->siteName(),
                'heading' => '■店舗グループ 登録',
                'refresh' => 'shop_category.php?act=',
                'message' => $mode . '中です。',
                'class' => 'center',
                'seconds' => 0,
            ]);
        }

        if ((int) $request->query('thisRow') > 0 && preg_match('/^[0-9]+$/', (string) $request->query('thisCd'))) {
            $this->move((int) $request->query('thisCd'), (int) $request->query('thisRow'), (string) $request->query('kind'));
        }

        $edit = $request->filled('cd') ? ShopCategory::find((int) $request->input('cd')) : null;

        return view('admin.base.shop-categories.index', [
            'edit' => $edit,
            'title' => $edit?->title ?? '',
            'rows' => ShopCategory::query()->active()->get(),
        ]);
    }

    /** shop_category_register.php; form2Db: 半角カナ → 全角 y recorte de espacios */
    private function register(string $act, Request $request): string
    {
        $now = now()->format('Y/m/d H:i:s');

        if ($act === 'del') {
            DB::table('tb_shop_category')->where('cd', (int) $request->input('cd'))->update(['flg' => 9, 'update_date' => $now]);

            return '削除';
        }

        $title = LegacyText::trim(mb_convert_kana((string) $request->input('title', ''), 'KV'));
        DB::transaction(function () use ($act, $request, $title, $now) {
            if ($act === 'new') {
                ShopCategory::create([
                    'title' => $title,
                    'record_date' => $now,
                    'update_date' => $now,
                    'sort' => ShopCategory::query()->where('flg', 1)->count() + 1,
                    'flg' => 1,
                ]);

                return;
            }
            DB::table('tb_shop_category')->where('cd', (int) $request->input('cd'))->update(['title' => $title, 'update_date' => $now, 'flg' => 1]);
        });

        return '登録';
    }

    /** ▲▼: intercambia sort con la activa vecina y renumera 1..n */
    private function move(int $cd, int $row, string $kind): void
    {
        DB::transaction(function () use ($cd, $row, $kind) {
            $neighbor = match ($kind) {
                'up' => DB::table('tb_shop_category')->where('sort', '<', $row)->where('flg', 1)->orderByDesc('sort')->first(),
                'down' => DB::table('tb_shop_category')->where('sort', '>', $row)->where('flg', 1)->orderBy('sort')->first(),
                default => null,
            };
            if ($neighbor) {
                DB::table('tb_shop_category')->where('cd', $neighbor->cd)->update(['sort' => $row]);
                DB::table('tb_shop_category')->where('cd', $cd)->update(['sort' => $neighbor->sort]);
            }
            foreach (DB::table('tb_shop_category')->where('flg', 1)->orderBy('sort')->pluck('cd') as $i => $rowCd) {
                DB::table('tb_shop_category')->where('cd', $rowCd)->update(['sort' => $i + 1]);
            }
        });
    }
}

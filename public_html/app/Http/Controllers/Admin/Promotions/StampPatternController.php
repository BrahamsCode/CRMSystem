<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * スタンプデザイン管理 (category3/stamp/stamp_pattern.php + _register.php): de los diseños de tb_stamp_pattern
 * (disp_flg '1') se eligen hasta 10 para Mi página (pic_shop_flg) y uno por defecto (pic_default).
 * Las imágenes no se suben aquí: son /hp/uploads/c4/stamp/<cd>.gif (Storage, ruta de S1).
 * También redirige las URL .php del legacy (menú left_navi3) a las pantallas nuevas de F5.
 */
class StampPatternController extends Controller
{
    public function index(): View
    {
        return view('admin.promocion.stamp.stamp_pattern', [
            'patterns' => DB::table('tb_stamp_pattern')->where('disp_flg', '1')
                ->orderByRaw("CAST(NULLIF(narabi, '') AS INT)")->orderBy('cd')->get(),
        ]);
    }

    /** stamp_pattern_register.php: sin 設定 (default) o sin 選択 no cambia nada, como el SQL del legacy que fallaba entero */
    public function update(Request $request): View
    {
        $default = (string) $request->input('default', '');
        $selected = array_map('intval', array_filter(array_keys((array) $request->input('sentaku', [])), 'is_numeric'));
        if ($request->has('send') && ctype_digit($default) && $selected !== []) {
            DB::transaction(function () use ($selected, $default) {
                DB::table('tb_stamp_pattern')->update(['pic_shop_flg' => '', 'pic_default' => '']);
                DB::table('tb_stamp_pattern')->whereIn('cd', $selected)->update(['pic_shop_flg' => '1']);
                DB::table('tb_stamp_pattern')->where('cd', (int) $default)->update(['pic_default' => '1']);
            });
        }

        return view('admin.promocion.stamp.stamp_done', [
            'title' => 'スタンプデザイン管理',
            'message' => '登録中です。',
            'seconds' => 2,
            'next' => route('admin.promocion.stamps.pattern'),
        ]);
    }

    /** GET de las .php del legacy (menú, enlaces, popups de plantillas) → pantalla nueva con su query */
    public function legacy(Request $request, string $target, ?string $type = null): RedirectResponse
    {
        return redirect()->to(route($target, ($type !== null ? ['type' => $type] : []) + $request->query()));
    }
}

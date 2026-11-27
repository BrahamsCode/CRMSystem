<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * スタッフ検索項目設定 (form_config.php + form_config_register.php): las filas de tb_koumoku con
 * tb_name = 'tb_staff'. tb_koumoku es una tabla compartida (campos de clientes, personal, reservas):
 * aquí solo se tocan las del personal y solo mail_mag_select (検索) y koumoku_select (CSV).
 */
class StaffFieldController extends Controller
{
    public function edit(): View
    {
        return view('admin.operaciones.staff-fields.edit', [
            'fields' => DB::table('tb_koumoku')->where('tb_name', 'tb_staff')->orderBy('cd')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $max = (int) $request->input('item_max_no');

        DB::transaction(function () use ($request, $max) {
            for ($no = 1; $no <= $max; $no++) {
                $value = fn (string $name) => is_scalar($request->input($name)) ? (string) $request->input($name) : '';

                DB::table('tb_koumoku')
                    ->where('tb_name', 'tb_staff')
                    ->where('cd', (int) $request->input("item_cd{$no}"))
                    ->update([
                        'mail_mag_select' => $value("item_cd3_{$no}"),
                        'koumoku_select' => $value("item_cd4_{$no}"),
                    ]);
            }
        });

        return to_route('admin.operaciones.staff-fields.edit');
    }
}

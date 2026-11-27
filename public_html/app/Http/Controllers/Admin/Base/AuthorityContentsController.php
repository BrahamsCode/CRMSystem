<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 権限項目管理 (mantenimiento): authority_contents_list.php (lista + 表示), authority_contents.php (popup de
 * alta/edición) y ajax_authority_contents_register.php. Los cd < 1000 son フォーム項目タイプ y los ≥ 1001
 * 機能タイプ: el alta toma el siguiente de su rango (como el legacy, sin hatuban).
 */
class AuthorityContentsController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->isMethod('post') && $request->input('update_hyouji_flg') === 'update') {
            $flags = (array) $request->input('hyouji_flg_array', []);
            DB::transaction(function () use ($request, $flags) {
                foreach ((array) $request->input('update_cd', []) as $cd) {
                    DB::table('tb_kengen_contents')->where('cd', (int) $cd)->update(['hyouji_flg' => isset($flags[$cd]) ? '1' : '']);
                }
            });
        }

        return view('admin.base.authority-contents.index', [
            'rows' => DB::table('tb_kengen_contents')->orderBy('cd')->get(),
            'baseSystems' => $this->baseSystems()->pluck('system_name', 'cd'),
        ]);
    }

    public function form(Request $request): View
    {
        $data = $request->filled('cd') ? DB::table('tb_kengen_contents')->where('cd', (int) $request->input('cd'))->first() : null;

        return view('admin.base.authority-contents.form', ['data' => $data, 'baseSystems' => $this->baseSystems()]);
    }

    /** ajax_authority_contents_register.php: responde vacío (el popup muestra la respuesta y recarga la lista) */
    public function register(Request $request): Response
    {
        $in = fn (string $key): string => LegacyText::db($request->input($key));

        DB::transaction(function () use ($in) {
            $now = (string) time();
            $cd = $in('cd');
            if ($cd === '') {
                $type1 = $in('type') === '1';
                $max = DB::table('tb_kengen_contents')->where('cd', $type1 ? '<' : '>', 1000)->max('cd');
                $cd = $max === null ? ($type1 ? 1 : 1001) : (int) $max + 1;
                DB::table('tb_kengen_contents')->insert(['cd' => $cd, 'touroku_date' => $now]);
            }

            DB::table('tb_kengen_contents')->where('cd', (int) $cd)->update([
                'name' => $in('name'),
                'tb_base_system_cd' => (int) $in('tb_base_system_cd'),
                'comment' => $in('comment'),
                'hyouji_flg' => $in('hyouji_flg'),
                'update_date' => $now,
            ]);
        });

        return response('');
    }

    private function baseSystems(): \Illuminate\Support\Collection
    {
        return DB::table('tb_base_system')->where('system_name', '<>', '')->where('system_flg', '1')->orderBy('narabi')->get();
    }
}

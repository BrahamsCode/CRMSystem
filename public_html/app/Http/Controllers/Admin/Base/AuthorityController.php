<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\Hatuban;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 権限管理 (system/category0/config/authority.php): se elige un grupo de personal y se marcan las 4 funciones
 * de cada 権限コンテンツ. La matriz llega por AJAX (ajax_group_authority.php) y se guarda por AJAX
 * (ajax_group_authority_register.php, respuesta vacía = OK), como el legacy.
 */
class AuthorityController extends Controller
{
    public function index(): View
    {
        return view('admin.base.authority.index', [
            'groups' => DB::table('tb_staff_group')->where('disp_flg', '1')->orderBy('rank')->get(['cd', 'name']),
        ]);
    }

    /** ajax_group_authority.php?gcd=N: matriz del grupo (contenidos < 1000 = datos personales, >= 1000 = funciones) */
    public function group(Request $request): View
    {
        $gcd = (int) $request->query('gcd');
        $kengen = DB::table('tb_kengen')->where('tb_staff_group_cd', $gcd)->get()->keyBy('tb_kengen_contents_cd');

        $contents = DB::table('tb_kengen_contents as tb1')
            ->join('tb_base_system as tb2', 'tb1.tb_base_system_cd', '=', 'tb2.cd')
            ->where('tb2.system_flg', '1')->where('tb1.hyouji_flg', '1')
            ->orderBy('tb2.cd')->orderBy('tb1.cd')
            ->get(['tb1.*']);

        $staff = DB::table('tb_staff as tb1')->join('tb_shop as tb2', 'tb1.shop_cd', '=', 'tb2.cd')
            ->where('tb1.tb_staff_group_cd', $gcd)->where('tb1.disp_flg', '1')
            ->orderBy('tb2.cd')->orderBy('tb1.cd')->get(['tb1.name', 'tb2.kaishamei']);

        return view('admin.base.authority.group', [
            'personal' => $contents->where('cd', '<', 1000)->values(),
            'functions' => $contents->where('cd', '>=', 1000)->values(),
            'flag' => fn ($cd, $column) => (string) ($kengen[$cd]->{$column} ?? '') === '1',
            'staff' => $staff,
        ]);
    }

    /** ajax_group_authority_register.php: una fila de tb_kengen por contenido visible (nueva con hatuban) */
    public function register(Request $request, Hatuban $hatuban): Response
    {
        if ($request->input('act') !== 'update') {
            return response('');
        }
        $group = (int) $request->input('staff_group');
        $now = (string) time();

        DB::transaction(function () use ($request, $hatuban, $group, $now) {
            DB::statement('LOCK TABLE tb_kengen_contents, tb_kengen');
            $existing = DB::table('tb_kengen')->where('tb_staff_group_cd', $group)->pluck('cd', 'tb_kengen_contents_cd');

            foreach (DB::table('tb_kengen_contents')->where('hyouji_flg', '1')->orderBy('cd')->pluck('cd') as $contentsCd) {
                $flags = [];
                foreach (['etsuran_flg', 'shinki_flg', 'shusei_flg', 'sakujo_flg'] as $column) {
                    $flags[$column] = $request->has($column . '_' . $contentsCd) ? '1' : '';
                }
                if (isset($existing[$contentsCd])) {
                    DB::table('tb_kengen')->where('cd', $existing[$contentsCd])->update($flags + ['update_date' => $now]);
                } else {
                    DB::table('tb_kengen')->insert($flags + [
                        'cd' => $hatuban->next('tb_kengen'),
                        'tb_staff_group_cd' => $group,
                        'tb_kengen_contents_cd' => $contentsCd,
                        'touroku_date' => $now,
                        'update_date' => $now,
                    ]);
                }
            }
        });

        return response('');
    }
}

<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ショートカット管理 (system/category0/config/bookmark*.php y ajax_bookmark.php).
 * - index: accesos directos registrados, ordenables (jQuery UI sortable → rank por AJAX).
 * - list: funciones que se pueden registrar; act_flg=koushin guarda las casillas, act_flg=reset
 *   restaura los 60 accesos de fábrica (database/sql/nucleo/bookmark_reset.sql, copia literal del legacy).
 */
class BookmarkController extends Controller
{
    public function index(): View
    {
        return view('admin.base.bookmarks.index', [
            'rows' => $this->visible()->where('tb1.bookmark_flg', '1')->orderBy('tb1.user_rank')->get(),
        ]);
    }

    /** ajax_bookmark.php: user_rank = bm_<cd> de cada fila; las que no llegan quedan en null */
    public function rank(Request $request): Response
    {
        DB::transaction(function () use ($request) {
            foreach (DB::table('tb_bookmark')->orderBy('cd')->pluck('cd') as $cd) {
                $rank = (string) $request->input('bm_' . $cd, '');
                DB::table('tb_bookmark')->where('cd', $cd)->update(['user_rank' => $rank !== '' ? (int) $rank : null]);
            }
        });

        return response('');
    }

    public function list(Request $request): View
    {
        match ((string) $request->input('act_flg')) {
            'koushin' => $this->saveFlags($request),
            'reset' => DB::transaction(fn () => DB::unprepared(file_get_contents(database_path('sql/nucleo/bookmark_reset.sql')))),
            default => null,
        };

        $rows = $this->visible()
            ->addSelect('tb2.system_name as base_name', 'tb3.system_name as child_name')
            ->orderBy('tb1.rank')->get();

        return view('admin.base.bookmarks.list', ['groups' => $this->group($rows->reject(fn ($row) => $this->skip($row->title)))]);
    }

    /** Accesos activos cuya categoría y subcategoría están habilitadas (tb_base_system*.system_flg) */
    private function visible(): \Illuminate\Database\Query\Builder
    {
        return DB::table('tb_bookmark as tb1')
            ->join('tb_base_system as tb2', 'tb1.tb_base_system_cd', '=', 'tb2.cd')
            ->join('tb_base_system_child as tb3', 'tb1.tb_base_system_child_cd', '=', 'tb3.cd')
            ->where('tb1.disp_flg', '1')->where('tb2.system_flg', '1')->where('tb3.system_flg', '1')
            ->select('tb1.*');
    }

    /** bookmark_flg = '1' si llegó la casilla bookmark_<cd>, si no '' (como el legacy) */
    private function saveFlags(Request $request): void
    {
        DB::transaction(function () use ($request) {
            foreach (DB::table('tb_bookmark')->orderBy('cd')->pluck('cd') as $cd) {
                DB::table('tb_bookmark')->where('cd', $cd)->update(['bookmark_flg' => (string) $request->input('bookmark_' . $cd, '')]);
            }
        });
    }

    /** Funciones que solo se ofrecen si su pantalla (tb_base_system_grandchild) está habilitada */
    private function skip(?string $title): bool
    {
        return match ($title) {
            'フリーページ管理' => (string) DB::table('tb_base_system_grandchild')->where('system_name', 'フリーページ管理')->value('system_flg') !== '1',
            '顧客ランク設定', '顧客ランク振り分け設定' => DB::table('tb_base_system_grandchild')
                ->where('system_name', 'like', '顧客ランク%')->where('system_flg', '1')->count() < 1,
            default => false,
        };
    }

    /**
     * Agrupa por categoría (una tabla por categoría) y dentro por subcategoría, en el orden de `rank`.
     *
     * @return list<array{name: string, children: list<array{name: string, rows: list<object>}>}>
     */
    private function group(Collection $rows): array
    {
        $groups = [];
        $base = $child = null;
        foreach ($rows as $row) {
            if ($base !== $row->tb_base_system_cd) {
                $groups[] = ['name' => (string) $row->base_name, 'children' => []];
                $base = $row->tb_base_system_cd;
                $child = null;
            }
            $g = array_key_last($groups);
            if ($child !== $row->tb_base_system_child_cd) {
                $groups[$g]['children'][] = ['name' => (string) $row->child_name, 'rows' => []];
                $child = $row->tb_base_system_child_cd;
            }
            $groups[$g]['children'][array_key_last($groups[$g]['children'])]['rows'][] = $row;
        }

        return $groups;
    }
}

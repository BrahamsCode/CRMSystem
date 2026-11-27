<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\Hatuban;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * エリア管理 (system/category0/config/area.php, area_edit.php, area_confirm.php, area_register.php):
 * árbol padre/hijo de tb_area con dTree, alta/edición con confirmación, orden ▲▼, 表示 y borrado lógico
 * (los hijos suben un nivel), como el legacy.
 */
class AreaController extends Controller
{
    private const PER_PAGE = 10;

    /** area.php: hijos de `pid` (raíz si vacío), con 更新 en bloque (orden + 表示) y ▲▼ */
    public function index(Request $request): View
    {
        $pid = (int) $request->input('pid');
        $siblings = fn () => DB::table('tb_area')->where('disp_flg', '<>', '9')
            ->when($pid > 0, fn ($q) => $q->where('pid', $pid), fn ($q) => $q->where(fn ($w) => $w->whereNull('pid')->orWhere('pid', '<', 1)));

        if ($request->input('update') === 'update') {
            for ($i = 0; $i < (int) $request->input('update_max'); $i++) {
                $values = ['flg' => (string) $request->input('flg' . $i, '')];
                if (preg_match('/^[0-9]+$/', (string) $request->input('narabi' . $i))) {
                    $values['row_lis'] = (int) $request->input('narabi' . $i);
                }
                DB::table('tb_area')->where('cd', (int) $request->input('cd' . $i))->update($values);
            }
        }

        if ((int) $request->query('thisRow') > 0 && preg_match('/^[0-9]+$/', (string) $request->query('thisCd'))) {
            $this->move((int) $request->query('thisCd'), (int) $request->query('thisRow'), (string) $request->query('kind'), $pid, $siblings);
        }

        $max = $siblings()->count();
        $page = max(1, (int) ($request->input('page') ?: 1));
        $from = ($page - 1) * self::PER_PAGE;

        return view('admin.base.areas.index', [
            'pid' => $pid,
            'above' => $pid > 0 ? DB::table('tb_area')->where('cd', $pid)->first(['cd', 'pid', 'name']) : null,
            'rows' => $siblings()->orderBy('row_lis')->limit(self::PER_PAGE)->offset($from)->get(),
            'max' => $max,
            'page' => $page,
            'maxpage' => (int) ceil($max / self::PER_PAGE),
            'from' => $from,
        ]);
    }

    /** ▲▼: intercambia row_lis con el vecino del mismo padre y renumera 1..n */
    private function move(int $cd, int $row, string $kind, int $pid, \Closure $siblings): void
    {
        DB::transaction(function () use ($cd, $row, $kind, $pid, $siblings) {
            $sameParent = fn ($q) => $pid > 0 ? $q->where('pid', $pid) : $q->whereNull('pid');
            $neighbor = match ($kind) {
                'up' => $sameParent(DB::table('tb_area')->where('row_lis', '<', $row))->orderByDesc('row_lis')->first(),
                'down' => $sameParent(DB::table('tb_area')->where('row_lis', '>', $row))->orderBy('row_lis')->first(),
                default => null,
            };
            if ($neighbor) {
                DB::table('tb_area')->where('cd', $neighbor->cd)->update(['row_lis' => $row]);
                DB::table('tb_area')->where('cd', $cd)->update(['row_lis' => $neighbor->row_lis]);
            }
            foreach ($siblings()->orderBy('row_lis')->pluck('cd') as $i => $rowCd) {
                DB::table('tb_area')->where('cd', $rowCd)->update(['row_lis' => $i + 1]);
            }
        });
    }

    /** area_edit.php: formulario; con upflg=new|update valida (エリア名 obligatorio) y pasa al 確認 */
    public function edit(Request $request): View|RedirectResponse
    {
        $data = $request->only(['name', 'comment', 'pid', 'area_cd']);
        $upflg = 'new';
        $error = null;

        if (in_array($request->input('upflg'), ['new', 'update'], true)) {
            if (trim((string) $request->input('name')) === '') {
                $error = '<p class="m0">※エリア名は必須入力項目です。</p>';
            } else {
                return view('admin.base.areas.confirm', ['data' => $data, 'mode' => $request->input('upflg') === 'new' ? 'new' : 'update']);
            }
            $upflg = (string) $request->input('upflg');
        } elseif ($request->filled('area_cd')) {
            if (! preg_match('/^[0-9]+$/', (string) $request->input('area_cd'))) {
                return redirect('area.php');
            }
            $row = DB::table('tb_area')->where('cd', (int) $request->input('area_cd'))->first();
            if (! $row) {
                return redirect('area.php');
            }
            $data = ['name' => $row->name, 'comment' => $row->comment, 'pid' => $request->input('pid'), 'area_cd' => $row->cd, 'parent' => $row->pid];
            $upflg = 'update';
        }

        return view('admin.base.areas.edit', [
            'data' => $data,
            'upflg' => $upflg,
            'error' => $error,
            'pidChanged' => $upflg === 'update' && isset($data['parent']) && (string) $request->input('pid') !== (string) $data['parent'],
        ]);
    }

    /** area_confirm.php?del_cd=N: confirmación del borrado */
    public function confirmDelete(Request $request): View|RedirectResponse
    {
        $row = preg_match('/^[0-9]+$/', (string) $request->query('del_cd')) ? DB::table('tb_area')->where('cd', (int) $request->query('del_cd'))->first() : null;
        if (! $row) {
            return redirect('area.php');
        }

        return view('admin.base.areas.confirm', [
            'data' => ['name' => $row->name, 'comment' => $row->comment, 'pid' => $request->query('pid'), 'cd' => $row->cd],
            'mode' => 'del',
        ]);
    }

    /** area_register.php: alta (hatuban, row_lis = máx. del padre + 1), edición o borrado; vuelve a area.php */
    public function register(Request $request, Hatuban $hatuban): View
    {
        $upflg = (string) $request->input('upflg');
        $pid = (int) $request->input('pid');
        $now = (string) time();
        $label = ['new' => '新規登録', 'update' => '情報更新', 'del' => '情報削除'][$upflg] ?? '';

        DB::transaction(function () use ($request, $hatuban, $upflg, $pid, $now) {
            if ($upflg === 'new' || $upflg === 'update') {
                $values = [
                    'name' => LegacyText::form($request->input('name')),
                    'comment' => LegacyText::form($request->input('comment')),
                    'flg' => '1',
                    'disp_flg' => '1',
                    'update_date' => $now,
                ];
                if ($pid > 0) {
                    $values['pid'] = $pid;
                }
                if ($upflg === 'new') {
                    $maxRow = DB::table('tb_area')->when($pid > 0, fn ($q) => $q->where('pid', $pid))->max('row_lis');
                    DB::table('tb_area')->insert($values + [
                        'cd' => $hatuban->next('tb_area'),
                        'touroku_date' => $now,
                        'row_lis' => $maxRow !== null ? $maxRow + 1 : 1,
                    ]);
                } else {
                    DB::table('tb_area')->where('cd', (int) $request->input('area_cd'))->update($values);
                }
            } elseif ($upflg === 'del') {
                $cd = (int) $request->input('del_cd');
                $row = DB::table('tb_area')->where('cd', $cd)->first(['cd', 'pid']);
                DB::table('tb_area')->where('cd', $cd)->update(['disp_flg' => '9']);
                if ($row) {
                    // Los hijos suben un nivel
                    DB::table('tb_area')->where('pid', $row->cd)->update(['pid' => $row->pid ?: null]);
                }
            }
        });

        return view('admin.base.shops.wait', [
            'title' => 'エリア' . $label . ' - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName(),
            'heading' => '■エリア' . $label,
            'refresh' => 'area.php?pid=' . ($pid ?: ''),
            'message' => $label . '中です。',
            'class' => 'textCenter',
            'seconds' => 0,
            'ac' => 'ac1',
        ]);
    }
}

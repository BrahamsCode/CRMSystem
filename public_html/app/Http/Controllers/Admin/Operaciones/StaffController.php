<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\PosGateway;
use App\Services\Operaciones\StaffSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * スタッフ管理: lista con búsqueda (staff_list.php) y CSV (csv_download.php).
 * Doc/diseno/operaciones-integraciones.md §1.1.
 */
class StaffController extends Controller
{
    public function __construct(private readonly StaffSearch $search)
    {
    }

    /**
     * スタッフ一覧. Como el legacy: sin act (entrada por el menú o 検索条件クリア) solo se ve el formulario y
     * se olvidan sus valores; con act=up|return se lista la última búsqueda guardada en la sesión.
     */
    public function index(Request $request): View
    {
        $act = (string) $request->query('act', '');
        $kensu = $this->kensu($request->query('kensu'));
        $page = max(1, (int) $request->query('page', 1));

        if ($act === '') {
            $request->session()->forget(StaffSearch::SESSION_WORDS);
        }

        $conditions = $request->session()->get(StaffSearch::SESSION_CONDITIONS);
        $list = null;
        if (in_array($act, ['up', 'return'], true) && $conditions !== null) {
            $total = $this->search->query($conditions)->count();
            $maxPage = (int) ceil($total / $kensu);
            $page = min($page, $maxPage); // ページオーバーを防ぐ (0 sin resultados)
            $from = max(0, ($page - 1) * $kensu);

            $list = [
                'total' => $total,
                'from' => $from,
                'rows' => $this->search->query($conditions)->orderByDesc('cd')->limit($kensu)->offset($from)->get(),
                'maxPage' => $maxPage,
            ];
        }

        return view('admin.operaciones.staff.index', [
            'act' => $act,
            'kensu' => $kensu,
            'page' => $page,
            'list' => $list,
            'fields' => $this->search->fields(),
            'words' => $request->session()->get(StaffSearch::SESSION_WORDS, []),
            'shops' => Shop::where('disp_flg', '!=', '9')->orderBy('cd')->get(['cd', 'kaishamei']),
            'belongposts' => DB::table('tb_belongpost')->orderBy('cd')->pluck('belongpost'),
        ]);
    }

    /**
     * Envío del formulario de búsqueda (form_search). Con el botón この条件で抽出する se recalculan las
     * condiciones; sin él (cambio de 表示件数 por JS) se conserva la búsqueda anterior.
     */
    public function search(Request $request): RedirectResponse
    {
        if ($request->has('search_staff')) {
            $parsed = $this->search->parse($request->all());
            $request->session()->put(StaffSearch::SESSION_CONDITIONS, $parsed['conditions']);
            $request->session()->put(StaffSearch::SESSION_WORDS, $parsed['words']);
        }

        return redirect()->to(route('admin.operaciones.staff.index', [
            'act' => 'up',
            'kensu' => $this->kensu($request->input('kensu')),
        ]).'#result');
    }

    /**
     * この条件でCSV出力する (solo master, como el botón del legacy): campos con CSV marcado, orden name_kana,
     * Shift_JIS, CRLF y cada valor como "\t<valor>". Sin búsqueda previa salen todos (también los borrados).
     */
    public function csv(Request $request): StreamedResponse
    {
        $fields = $this->search->csvFields();
        $rows = $this->search->query($request->session()->get(StaffSearch::SESSION_CONDITIONS, []))->orderBy('name_kana')->get();
        $withTitle = $request->input('titleflg') === '1';

        return response()->streamDownload(function () use ($fields, $rows, $withTitle) {
            $sjis = fn ($value) => mb_convert_encoding((string) $value, 'SJIS', 'UTF-8');

            if ($withTitle) {
                echo $fields->map(fn ($f) => '"'.$sjis($f->tb_koumoku_name).'"')->implode(',')."\r\n";
            }

            $shops = [];
            foreach ($rows as $staff) {
                echo $fields->map(function ($f) use ($staff, $sjis, &$shops) {
                    $value = $staff->getAttribute($f->tb_koumoku);
                    if (in_array($f->tb_koumoku_name, ['会員登録日', '情報変更日'], true)) {
                        $value = date('Y/m/d H:i', (int) $value);
                    } elseif ($f->tb_koumoku_name === '登録店舗') {
                        $value = $shops[$value] ??= $value === null ? null : Shop::withDeleted()->where('cd', $value)->value('kaishamei');
                    }

                    return "\"\t".$sjis($value).'"';
                })->implode(',')."\r\n";
            }
        }, 'user.csv', ['Content-Type' => 'text/x-csv; charset=Shift_JIS'], 'attachment');
    }

    /**
     * スタッフ登録情報 (staff_detail.php): 4 pestañas (?tab= vacío = スタッフ情報1, 2 = スタッフ情報2, 3 = 交通費情報,
     * 4 = ホームページ情報 solo con un tb_base_system_grandchild activo de スタッフ管理). Incluye a los borrados
     * (de ahí sale 削除を取り消す).
     */
    public function show(Request $request, int $cd): View
    {
        $staff = Staff::withDeleted()->where('cd', $cd)->firstOrFail();
        $hasHomepage = DB::table('tb_base_system_grandchild')->where('tb_base_system_child_cd', 50)->where('system_flg', '1')->exists();
        $tab = (string) $request->query('tab', '');

        return view('admin.operaciones.staff.show', [
            'staff' => $staff,
            'tab' => in_array($tab, ['2', '3'], true) || ($tab === '4' && $hasHomepage) ? $tab : '',
            'hasHomepage' => $hasHomepage,
            'shopName' => Shop::withDeleted()->where('cd', $staff->shop_cd)->value('kaishamei'),
            'photoUrl' => $this->photoUrl($staff),
            'back' => array_filter([
                'act' => (string) $request->query('act', 'return') ?: 'return',
                'page' => $request->query('page'),
                'kensu' => $request->query('kensu'),
            ], fn ($v) => is_scalar($v) && $v !== ''),
        ]);
    }

    /**
     * 削除する (staff_delete_register.php, delete_flg=del): disp_flg = '9', suelta su tarjeta (borra tb_card y
     * vacía felica_no en tb_staff y en todos sus tb_work_time) y lo da de baja en el POS.
     */
    public function destroy(int $cd, PosGateway $pos): RedirectResponse
    {
        abort_if($cd === 1, 403); // el legacy nunca ofrece borrar al cd 1
        $staff = Staff::withDeleted()->where('cd', $cd)->firstOrFail();

        DB::transaction(function () use ($staff) {
            DB::table('tb_staff')->where('cd', $staff->cd)->update(['disp_flg' => Staff::DELETED_FLAG, 'synchronize_flg' => '']);
            $this->releaseCardOf($staff->cd);
        });
        $pos->removeStaff($staff->cd);

        return to_route('admin.operaciones.staff.index');
    }

    /**
     * 削除を取り消す (staff_recover_confirm.php): ficha completa y aviso si su ID o su メールアドレス1 ya los usa
     * otro empleado activo (entonces no hay botón 復元する).
     */
    public function restoreConfirm(int $cd): View
    {
        $staff = Staff::onlyDeleted()->where('cd', $cd)->firstOrFail();

        return view('admin.operaciones.staff.restore', [
            'staff' => $staff,
            'shopName' => Shop::withDeleted()->where('cd', $staff->shop_cd)->value('kaishamei'),
            'photoUrl' => $this->photoUrl($staff),
            'duplicates' => $this->restoreDuplicates($staff),
        ]);
    }

    /** 復元する (staff_delete_register.php, delete_flg=recover): disp_flg = '1' y alta en el POS */
    public function restore(int $cd, PosGateway $pos): RedirectResponse
    {
        $staff = Staff::onlyDeleted()->where('cd', $cd)->firstOrFail();
        if (array_filter($this->restoreDuplicates($staff))) {
            // El legacy no volvía a comprobar al registrar; aquí no se restaura un duplicado
            return to_route('admin.operaciones.staff.restore-confirm', $cd);
        }

        DB::table('tb_staff')->where('cd', $cd)->update(['disp_flg' => Staff::ACTIVE_FLAG, 'synchronize_flg' => '']);
        $pos->syncStaff($cd);

        return to_route('admin.operaciones.staff.index');
    }

    /** 関連付けを解除する (staff_card_delete.php): suelta la tarjeta FeliCa del empleado */
    public function releaseCard(int $cd): RedirectResponse
    {
        $staff = Staff::withDeleted()->where('cd', $cd)->firstOrFail();
        DB::transaction(fn () => $this->releaseCardOf($staff->cd));

        return to_route('admin.operaciones.staff.index');
    }

    /** tb_card del empleado fuera y felica_no vacío en tb_staff y tb_work_time (staff_cd de tb_card es texto) */
    private function releaseCardOf(int $cd): void
    {
        DB::table('tb_card')->where('staff_cd', (string) $cd)->delete();
        DB::table('tb_staff')->where('cd', $cd)->update(['felica_no' => '']);
        DB::table('tb_work_time')->where('staff_cd', $cd)->update(['felica_no' => '']);
    }

    /** @return array{id: bool, email1: bool} campos ya usados por un empleado no borrado */
    private function restoreDuplicates(Staff $staff): array
    {
        $taken = fn (string $column) => filled($staff->{$column})
            && Staff::where($column, $staff->{$column})->where('cd', '!=', $staff->cd)->exists();

        return ['id' => $taken('id'), 'email1' => $taken('email1')];
    }

    /** Foto en /hp/uploads/c5/staff/<cd>/<cd>.jpg (disco public) o null si no tiene pic_name */
    private function photoUrl(Staff $staff): ?string
    {
        return filled($staff->pic_name) ? "/hp/uploads/c5/staff/{$staff->cd}/{$staff->cd}.jpg?pcc=".time() : null;
    }

    /** 表示件数: el de la petición o 10 */
    private function kensu(mixed $value): int
    {
        $kensu = is_scalar($value) ? (int) $value : 0;

        return $kensu > 0 ? $kensu : 10;
    }
}

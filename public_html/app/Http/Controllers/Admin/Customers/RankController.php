<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\RankRequest;
use App\Services\Customers\RankService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 顧客ランク設定 (legacy: category1/rank/customer_rank_list.php, kingaku_rank_new.php / _update.php,
 * raiten_rank_new.php / _update.php, customer_rank_frm.php, customer_rank_frm_register.php y
 * ajax_customer_rank_list.php). `{type}` = kingaku (使用金額ランク) | raiten (来店回数ランク).
 */
class RankController extends Controller
{
    public function __construct(private RankService $ranks) {}

    /** Lista de rangos por tipo activo y マイページへのランク表示設定 */
    public function index(): View
    {
        $types = $this->ranks->availableTypes();

        return view('admin.clientes.ranks.index', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'types' => $types,
            'ranks' => collect($types)->mapWithKeys(fn ($type) => [$type => $this->ranks->list($type)])->all(),
            'dispFlg' => $this->ranks->displayFlag(),
            'dispFlgUpdated' => (bool) session('rank_disp_flg_updated'),
        ]);
    }

    /** 表示を変更 (admin_master.customer_rank_disp_flg) */
    public function updateDisplay(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_rank_disp_flg' => ['nullable', 'in:' . implode(',', RankService::DISP_FLAGS)]]);
        $this->ranks->updateDisplayFlag((string) ($data['customer_rank_disp_flg'] ?? ''));

        return redirect()->route('admin.clientes.ranks')->with('rank_disp_flg_updated', true);
    }

    public function create(string $type): View
    {
        return $this->form($type, null);
    }

    public function store(RankRequest $request, string $type): RedirectResponse
    {
        $class = $this->ranks->modelClass($type);
        $this->ranks->save(new $class, $request->rankData());

        return redirect()->route('admin.clientes.ranks');
    }

    public function edit(string $type, int $cd): View|RedirectResponse
    {
        $rank = $this->ranks->modelClass($type)::query()->find($cd);

        // Como *_rank_update.php: si no existe, vuelve a la lista
        return $rank ? $this->form($type, $rank) : redirect()->route('admin.clientes.ranks');
    }

    public function update(RankRequest $request, string $type, int $cd): RedirectResponse
    {
        $this->ranks->save($this->find($type, $cd), $request->rankData());

        return redirect()->route('admin.clientes.ranks');
    }

    public function destroy(string $type, int $cd): RedirectResponse
    {
        $this->ranks->delete($this->find($type, $cd));

        return redirect()->route('admin.clientes.ranks');
    }

    /** Casilla 表示 de la lista (ajax type=hyouji, checked=true|false) */
    public function updateStatus(Request $request, string $type, int $cd): Response
    {
        $this->ranks->setVisibility($this->find($type, $cd), $request->input('checked') === 'true');

        return response('success');
    }

    /** Arrastrar y soltar (ajax type=rank, r_<cd>=orden) */
    public function sort(Request $request, string $type): Response
    {
        $this->ranks->reorder($type, $this->orderFromRequest($request));

        return response('');
    }

    /**
     * URL del legacy que llama el JS de la lista: system/category1/rank/ajax_customer_rank_list.php
     * (type=rank | hyouji, ranktype=kingaku|raiten). El borrado (type=delete, GET) ahora es admin.clientes.ranks.destroy.
     */
    public function legacyAjax(Request $request): Response
    {
        $type = (string) $request->input('ranktype');
        abort_unless(array_key_exists($type, RankService::MODELS), 404);

        return match ((string) $request->input('type')) {
            'rank' => $this->sort($request, $type),
            'hyouji' => preg_match('/^[0-9]+$/', (string) $request->input('cd'))
                ? $this->updateStatus($request, $type, (int) $request->input('cd'))
                : response(''),
            default => response(''),
        };
    }

    private function form(string $type, ?Model $rank): View
    {
        return view('admin.clientes.ranks.form', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'type' => $type,
            'rank' => $rank,
        ]);
    }

    private function find(string $type, int $cd): Model
    {
        return $this->ranks->modelClass($type)::query()->findOrFail($cd);
    }

    /** @return array<int, string> cd => orden, de los campos r_<cd> */
    private function orderFromRequest(Request $request): array
    {
        $order = [];
        foreach ($request->all() as $key => $value) {
            if (is_string($key) && preg_match('/^r_(\d+)$/', $key, $m) && is_scalar($value)) {
                $order[(int) $m[1]] = (string) $value;
            }
        }

        return $order;
    }
}

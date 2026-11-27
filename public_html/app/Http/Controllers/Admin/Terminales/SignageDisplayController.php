<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminales\SignageDisplayRequest;
use App\Models\Terminales\SignageDisplay;
use App\Services\Terminales\SignageDisplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ディスプレイ (category7/digital_signage/display_*.php) */
class SignageDisplayController extends Controller
{
    public function __construct(private SignageDisplayService $displays) {}

    /** display_list.php (GET y POST: el selector de 件数 y «<<一覧へ戻る» envían por POST) */
    public function index(Request $request): View
    {
        return view('admin.terminales.signage-displays.index',
            $this->displays->listing($request, route('admin.terminales.signage-displays.index')));
    }

    /** ▲▼ del listado (thisCd, thisRow > 0, kind) y vuelta al listado */
    public function sort(Request $request): RedirectResponse
    {
        if ((int) $request->query('thisRow') > 0 && ctype_digit((string) $request->query('thisCd'))) {
            $this->displays->move((int) $request->query('thisCd'), (string) $request->query('kind'));
        }

        return redirect()->route('admin.terminales.signage-displays.index', [
            'page' => $request->query('page'),
            'kensu' => $request->query('kensu'),
        ]);
    }

    /** display_new.php */
    public function create(): View
    {
        return view('admin.terminales.signage-displays.form', [
            'display' => null,
            'programs' => $this->displays->programs(),
        ]);
    }

    public function store(SignageDisplayRequest $request): View
    {
        $display = $this->displays->save(null, $request->validated());

        return $this->processing('new', $display);
    }

    /** display_detail.php */
    public function show(int $cd): View
    {
        $display = $this->displays->find($cd) ?? abort(404);

        return view('admin.terminales.signage-displays.show', $this->displays->showData($display));
    }

    /** display_update.php (GET y POST: el botón 編集する del detalle envía por POST) */
    public function edit(int $cd): View
    {
        return view('admin.terminales.signage-displays.form', [
            'display' => $this->displays->findOwned($cd) ?? abort(404),
            'programs' => $this->displays->programs(),
        ]);
    }

    public function update(SignageDisplayRequest $request, int $cd): View
    {
        $display = $this->displays->findOwned($cd) ?? abort(404);
        $this->displays->save($display, $request->validated());

        return $this->processing('update', $display);
    }

    /** display_delete.php: «削除中です。» y vuelta al listado */
    public function destroy(Request $request, int $cd): View
    {
        $display = $this->displays->find($cd) ?? abort(404);
        if ($request->input('delete_flg') === 'del') {
            $this->displays->delete($display);
        }

        return view('admin.terminales.partials.processing', [
            'family' => 'displays',
            'action' => 'delete',
            'refreshUrl' => route('admin.terminales.signage-displays.index', [
                'shop_cd' => $request->input('shop_cd'),
                'display_cd' => $request->input('display_cd'),
                'page' => $request->input('page'),
                'kensu' => $request->input('kensu'),
            ]),
        ]);
    }

    /** «登録中です。» con Refresh al detalle (display_register.php) */
    private function processing(string $action, SignageDisplay $display): View
    {
        return view('admin.terminales.partials.processing', [
            'family' => 'displays',
            'action' => $action,
            'refreshUrl' => route('admin.terminales.signage-displays.show', [$display->cd, 'register_type' => $action]),
        ]);
    }
}

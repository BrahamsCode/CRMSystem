<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Services\Terminales\FelicaTerminalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** ICカード管理 / 端末管理 (category7/felica/terminal_list.php y el borrado de ajax_del.php) */
class FelicaTerminalController extends Controller
{
    public function __construct(private FelicaTerminalService $terminals) {}

    /** terminal_list.php (GET y POST: el selector 表示件数 envía form_search por POST) */
    public function index(Request $request): View
    {
        return view('admin.terminales.felica.terminals',
            $this->terminals->listing($request, route('admin.terminales.felica-terminals.index')));
    }

    /**
     * ▲▼ (thisCd, kind), como cListUpDown, y vuelta al listado. thisRow (rank) va en la URL como en el
     * legacy pero no se exige > 0: el ranker renumera, así funciona también con rank NULL.
     */
    public function sort(Request $request): RedirectResponse
    {
        if (ctype_digit((string) $request->query('thisCd'))) {
            $this->terminals->move((int) $request->query('thisCd'), (string) $request->query('kind'));
        }

        return redirect()->route('admin.terminales.felica-terminals.index', array_filter([
            'page' => $request->query('page'),
            'kensu' => $request->query('kensu'),
        ], 'filled'));
    }

    /** Borrado por AJAX (antes ajax_del.php con tbNo=terminal): el JS carga la respuesta en la fila */
    public function destroy(Request $request): Response
    {
        $cd = (string) $request->input('cd');
        $deleted = ctype_digit($cd) && $this->terminals->delete((int) $cd);

        return response()->view('admin.terminales.felica.ajax-del', ['deleted' => $deleted, 'cd' => $cd]);
    }

    /** URL del legacy (menú lateral y marcadores) → pantalla nueva */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route('admin.terminales.felica-terminals.index', $request->query());
    }
}

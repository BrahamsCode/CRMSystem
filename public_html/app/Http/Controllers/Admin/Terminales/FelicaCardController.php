<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminales\FelicaCardUpdateRequest;
use App\Services\Terminales\FelicaCardService;
use App\Services\Terminales\LegacyListPaginator;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** ICカード管理 / カード管理 (category7/felica/card_list.php, ajax_search_kaiin.php y ajax_del.php) */
class FelicaCardController extends Controller
{
    public function __construct(private FelicaCardService $cards) {}

    /**
     * card_list.php: el buscador envía search=each_search por POST; la paginación y 表示件数
     * lo repiten (en el legacy se perdía y volvía a «検索して下さい»).
     */
    public function index(Request $request): View
    {
        $conditions = $this->cards->conditions($request);
        $searched = $request->input('search') === 'each_search';

        return view('admin.terminales.felica.cards', [
            'conditions' => $conditions,
            'searched' => $searched,
            'cards' => $searched ? $this->cards->search($conditions, $request, route('admin.terminales.felica-cards.index')) : null,
            'kensu' => LegacyListPaginator::kensu($request),
            'canChooseShop' => $this->cards->canChooseShop(),
            'shops' => $this->cards->shopOptions(),
            'sessionShop' => $this->cards->sessionShop(),
        ]);
    }

    /** «更新する»: tienda y socio de cada fila; vuelve al listado con la misma búsqueda */
    public function update(FelicaCardUpdateRequest $request): RedirectResponse
    {
        $errors = $this->cards->updateRows($request->rows());

        return redirect()
            ->route('admin.terminales.felica-cards.index', array_filter([
                'search' => 'each_search',
                'page' => $request->input('page'),
                'kensu' => $request->input('kensu'),
            ], 'filled'))
            ->with('felica_card_errors', $errors);
    }

    /** ajax_search_kaiin.php: tabla de socios del colorbox «会員検索» */
    public function searchMembers(Request $request): View
    {
        $shopCd = (string) $request->input('shop_cd');

        return view('admin.terminales.felica.ajax-search-kaiin', [
            'members' => $this->cards->searchMembers(
                ctype_digit($shopCd) ? (int) $shopCd : null,
                LegacyText::trim((string) $request->input('name', '')),
                LegacyText::trim((string) $request->input('id', '')),
                $request->input('felica_no') === '1',
            ),
        ]);
    }

    /** Borrado por AJAX (antes ajax_del.php con tbNo=customer_card) */
    public function destroy(Request $request): Response
    {
        $cd = (string) $request->input('cd');
        $deleted = ctype_digit($cd) && $this->cards->delete((int) $cd);

        return response()->view('admin.terminales.felica.ajax-del', ['deleted' => $deleted, 'cd' => $cd]);
    }

    /** URL del legacy (menú lateral: card_list.php?sesClear=card) → pantalla nueva */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route('admin.terminales.felica-cards.index', $request->query());
    }
}

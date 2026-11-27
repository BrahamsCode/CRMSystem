<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\DmLabelRequest;
use App\Services\Promotions\DmCustomerList;
use App\Services\Promotions\DmLabelPdf;
use App\Services\Promotions\SearchFormData;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * DM発行 (system/category3/dm/): 顧客検索 (customer_list.php) y 宛名ラベル A4×12 en PDF (aterna_a4_12.php).
 * Como el legacy, solo pide la sesión del personal (no tiene entrada de menú ni permiso propio).
 */
class DmController extends Controller
{
    public function __construct(
        private readonly StaffContext $staff,
        private readonly DmCustomerList $list,
    ) {}

    /** customer_list.php: formulario search_set1 y, con act=up, el resultado */
    public function index(Request $request, SearchFormData $formData): View
    {
        $kensu = max(1, (int) ($request->input('kensu') ?: 10));
        $filters = [];
        $result = null;
        $uniqid = null;

        if ($request->input('act') === 'up') {
            ['uniqid' => $uniqid, 'filters' => $filters] = $this->list->search($request->all(), $this->staff->isMaster(), $this->staff->shopCd());
            $result = $this->list->page($uniqid, (int) $request->input('page', 1), $kensu);
        }

        return view('admin.promocion.dm.customer_list', $formData->for($filters) + [
            'kensu' => $kensu,
            'searchUniqid' => $uniqid,
            'result' => $result,
        ]);
    }

    /** aterna_a4_12.php: PDF en línea, como FPDF::Output() sin argumentos */
    public function labels(DmLabelRequest $request, DmLabelPdf $pdf): Response
    {
        return response($pdf->render($this->list->labels($request->validated('search_uniqid'))), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="doc.pdf"',
        ]);
    }

    /** GET de la URL del legacy: misma pantalla */
    public function legacy(): RedirectResponse
    {
        return redirect()->route('admin.promocion.dm.index');
    }
}

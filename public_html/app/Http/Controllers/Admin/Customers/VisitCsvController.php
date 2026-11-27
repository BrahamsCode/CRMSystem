<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Customers\CustomerSearchForm;
use App\Services\Customers\Reports\VisitCsvService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * csv出力管理画面 (`system/category1/shukei/customerDataOutPut.php`, ventana emergente de 集計ツール).
 * Como el legacy, todo va a la misma URL con `mode`: `search` guarda el buscador en sesión, `search_clear` lo borra
 * y `csv` descarga el bloque `page` de `view` filas con la búsqueda guardada.
 */
class VisitCsvController extends Controller
{
    /** Clave de sesión de la búsqueda guardada (`$_SESSION['sess'.__FILE__]` del legacy) */
    private const SESSION_KEY = 'clientes.visit_csv.search';

    public function index(Request $request, VisitCsvService $service, CustomerSearchForm $form): View|StreamedResponse
    {
        $mode = (string) $request->input('mode');
        if ($mode === 'csv') {
            return $this->download($request, $service);
        }
        if ($mode === 'search') {
            session([self::SESSION_KEY => $request->except(['_token', 'mode', 'csv_limit'])]);
        } elseif ($mode === 'search_clear') {
            session()->forget(self::SESSION_KEY);
        }

        $staff = $this->staff($request);
        $filters = (array) session(self::SESSION_KEY, []);
        $limit = $service->limit($request->input('csv_limit'));
        // El legacy solo contaba al buscar (al entrar o al borrar mostraba 0件)
        $total = $mode === 'search' ? $service->count($staff, $filters) : 0;

        return view('admin.clientes.reports.visit-csv', array_merge(
            $form->viewData($staff, 'category1', $filters, $this->hiddenSearchMenus($request)),
            [
                'total' => $total,
                'limits' => VisitCsvService::LIMITS,
                'csvLimit' => $limit,
                'csvLimitSelected' => $request->has('csv_limit'),
                // Botones de bloques: (int)(total / límite) + 1, como el legacy
                'viewPage' => $mode === 'search' ? intdiv($total, $limit) + 1 : 0,
            ],
        ));
    }

    public function download(Request $request, VisitCsvService $service): StreamedResponse
    {
        $page = $request->filled('page') ? max(0, (int) $request->input('page')) : null;
        $view = $service->limit($request->input('view'));

        return $service->download($this->staff($request), (array) session(self::SESSION_KEY, []), $page, $view);
    }

    /** Menús plegados del buscador: cookie `hideSearchMenu` (la escribe el JS, sin cifrar) separada por comas */
    private function hiddenSearchMenus(Request $request): array
    {
        $raw = null;
        foreach (explode(';', (string) $request->headers->get('cookie')) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($name === 'hideSearchMenu') {
                $raw = urldecode($value);
            }
        }

        return $raw === null || $raw === '' ? [] : explode(',', $raw);
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }
}

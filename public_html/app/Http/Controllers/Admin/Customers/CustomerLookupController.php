<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Customers\CustomerListService;
use App\Services\Customers\CustomerSearchForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 顧客検索 para elegir el cliente de una カルテ (`system/category1/customer/karute_customer_search.php`):
 * buscador de search_set1 (filtros de `CustomerSearch`, con el rango de tienda del personal) y lista
 * ORDER BY touroku_date DESC con el botón 詳細 que abre el alta de カルテ del cliente (karute_new.php).
 * act=up busca (guarda la búsqueda con un uniqid como customer_list.php); se_flg=1 + search_uniqid pagina.
 */
class CustomerLookupController extends Controller
{
    /** Parámetros que no son filtros del buscador */
    private const NON_FILTER_KEYS = ['_token', 'act', 'enter', 'search_uniqid', 'se_flg', 'kensu', 'kensu_act', 'page', 'item_max_no', 'post_wherestr'];

    public function index(Request $request, CustomerListService $list, CustomerSearchForm $form): View
    {
        $staff = $this->staff($request);
        $kensu = (int) $request->input('kensu', 10);
        $kensu = $kensu > 0 ? $kensu : 10;
        $page = max(1, (int) $request->input('page', 1));

        $uniqid = (string) $request->input('search_uniqid', '');
        $description = null;
        if ($request->input('act') === 'up' && $request->isMethod('post')) {
            $uniqid = $list->store($request->all(), $staff, $uniqid);
            $page = 1;
        }
        if ($request->input('act') === 'up') {
            $description = $list->description($uniqid);
            if ($description === null) {
                // Sin búsqueda en sesión (enlace directo): todos los socios activos, como el legacy sin WHERE
                $uniqid = $list->store([], $staff);
                $description = $list->description($uniqid);
            }
        }
        $filters = $description !== null ? (array) ($description['params'] ?? []) : [];
        $formData = $form->viewData($staff, 'category1', $filters, []);

        $data = $formData + [
            'searched' => $description !== null,
            'searchUniqid' => $uniqid,
            'kensu' => $kensu,
            'page' => $page,
            'itemMaxNo' => count($formData['searchItems']),
            'total' => 0,
            'customers' => collect(),
            'formContent' => array_filter($filters, 'is_scalar'),
        ];

        if ($description !== null) {
            $query = $list->query($staff, $description);
            $data['total'] = (clone $query)->count();
            $data['customers'] = $query->orderByDesc('touroku_date')->orderByDesc('cd')
                ->offset(($page - 1) * $kensu)->limit($kensu)
                ->get(['cd', 'id', 'name', 'kojin_birth', 'zip', 'add', 'tel', 'email1', 'mailmaga_flg', 'error_send_cnt']);
        }

        return view('admin.clientes.lookup.index', $data);
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }
}

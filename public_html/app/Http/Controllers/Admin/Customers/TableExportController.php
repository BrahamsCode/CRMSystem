<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\CustomerCsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSVエクスポート (`system/category1/customer/csv_export.php`): volcado de las maestras de clientes y personal.
 * En el legacy no la enlazaba ningún menú (herramienta de mantenimiento, pareja de la CSVインポート) y no
 * comprobaba permisos; aquí se exige el Gate `nucleo.maintenance`, porque tb_staff lleva contraseñas.
 */
class TableExportController extends Controller
{
    public function index(): View
    {
        abort_unless(Gate::allows('nucleo.maintenance'), 403);

        return view('admin.clientes.table-export.index', [
            'tables' => CustomerCsvExporter::DUMP_TABLES,
            'error' => (string) session('table_export_error', ''),
        ]);
    }

    /** POST act=export&table=<tabla>, como el formulario del legacy */
    public function export(Request $request, CustomerCsvExporter $csv): StreamedResponse|RedirectResponse
    {
        abort_unless(Gate::allows('nucleo.maintenance'), 403);

        $table = (string) $request->input('table');
        if ($request->input('act') !== 'export' || ! isset(CustomerCsvExporter::DUMP_TABLES[$table])) {
            return $this->back('不正アクセス！！！');
        }
        if (! DB::table($table)->exists()) {
            return $this->back('データがありません。');
        }

        return $csv->tableDump($table);
    }

    private function back(string $error): RedirectResponse
    {
        return redirect()->route('admin.clientes.table-export')->with('table_export_error', $error);
    }
}

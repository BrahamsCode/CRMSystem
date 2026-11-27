<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\CustomerCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CSVインポート (`system/category1/customer/csv_import.php`): formulario y su POST (act=import).
 * El legacy solo enseñaba el enlace a desarrolladores y support_login (left_navi1.php); aquí se comprueba en el
 * servidor con el Gate `nucleo.maintenance`. El `sessId` del legacy lo sustituye el token CSRF.
 */
class CustomerImportController extends Controller
{
    public function create(): View
    {
        abort_unless(Gate::allows('nucleo.maintenance'), 403);

        return view('admin.clientes.import.create', [
            'result' => session('import_result'),
        ]);
    }

    public function store(Request $request, CustomerCsvImporter $importer): RedirectResponse
    {
        abort_unless(Gate::allows('nucleo.maintenance'), 403);

        $file = $request->file('csvFile');
        $result = ['added' => 0, 'updated' => 0, 'error' => ''];

        if ($file === null || is_array($file)) {
            $result['error'] = 'ファイルを選択して下さい。';
        } elseif (! $file->isValid()) {
            $result['error'] = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => min(ini_get('post_max_size'), ini_get('upload_max_filesize')).'以上のファイルはアップロード出来ません。',
                default => '不明なエラー発生しました。もう一度アップロードしてください。',
            };
        } else {
            // Sin copia temporal en uploads/temp: se lee el archivo subido directamente
            $result = $importer->import($file->getRealPath(), (int) session('shop_cd'));
        }

        return redirect()->route('admin.clientes.import')->with('import_result', $result);
    }
}

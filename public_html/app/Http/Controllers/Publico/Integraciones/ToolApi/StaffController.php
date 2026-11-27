<?php

namespace App\Http\Controllers\Publico\Integraciones\ToolApi;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * system/tool/api/category5/getStaffList.php: personal no borrado con el nombre de su tienda.
 * Devuelve todas las columnas de tb_staff (también login_pass), como el legacy: anotado para Japón.
 */
class StaffController extends Controller
{
    use PagesToolApi;

    public function index(Request $request): Response
    {
        $where = ["coalesce(tb1.disp_flg,'')<>'9'"];
        $bindings = [];
        foreach (['cd' => 'tb1.cd', 'shop' => 'tb1.shop_cd'] as $param => $column) {
            if (Out::form2Db($request->input($param)) !== '') {
                $where[] = "{$column} = ?";
                $bindings[] = (int) Out::form2Db($request->input($param));
            }
        }

        $rows = Out::rows('SELECT '.Out::textColumns('tb_staff', 'tb1').', tb2.kaishamei::text AS kaishamei'
            .' FROM tb_staff AS tb1 INNER JOIN tb_shop AS tb2 ON tb1.shop_cd = tb2.cd'
            .' WHERE '.implode(' AND ', $where).' ORDER BY tb1.rank, tb1.cd'.$this->page($request), $bindings);

        return $this->listResponse($request, $rows);
    }
}

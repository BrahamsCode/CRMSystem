<?php

namespace App\Http\Controllers\Publico\Integraciones;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Ventana CTI de la aplicación de escritorio (system/desktop_request/). Sin autenticación, como el legacy
 * (anotado para Japón). Salida byte a byte: tests/Fixtures/integraciones/tool (casos cti_*).
 */
class CtiController extends Controller
{
    /** Columnas de tb_customer que pinta la ficha (vacías si el socio no existe, como $array = array()) */
    private const DETAIL_KEYS = ['id', 'kanri_id', 'name_kana', 'name', 'kojin_sex', 'kojin_birth', 'pref', 'add',
        'tb_staff_cd', 'shoukai_tb_customer_cd', 'bikou'];

    /**
     * ctiWindow.php?tel=: lleva al alta (act=new), a la agenda de reservas del socio (customerCd; category 9 u 11
     * activa, si no a select.php) o a la búsqueda por teléfono. Las URL son las del legacy.
     */
    public function window(Request $request): Response
    {
        if (! $request->has('tel')) {
            return Out::text("<!DOCTYPE html>\r\n<html lang=\"ja\">\r\n<head>\r\n\r\n<title>CRM SYSTEM</title>\r\n\r\n</head>\r\n\r\n<body>\r\n\r\n"
                ."<div id=\"wrapper\">\r\n<!-- div#wrapper END --></div>\r\n\r\n</body>\r\n\r\n</html>");
        }

        $tel = $request->input('tel');
        if (! is_string($tel) || ! ctype_digit($tel)) {
            return Out::text('Incorrect tel format.');
        }

        if ($request->input('act') === 'new') {
            return $this->redirect('/system/category1/customer/customer_new.php?tel='.$tel);
        }

        $customerCd = $request->input('customerCd');
        if ($customerCd !== null && $customerCd !== '') {
            if (! is_string($customerCd) || ! ctype_digit($customerCd)) {
                return Out::text('Incorrect customerCd format.');
            }
            $category = DB::table('tb_base_system')->where('system_flg', '1')->whereIn('cd', [9, 11])->orderByDesc('cd')->value('cd');

            return $this->redirect($category !== null
                ? "/system/category{$category}/yoyaku/time_table.php?isCti=1&tel={$tel}&customerCd={$customerCd}"
                : '/system/select.php');
        }

        return $this->redirect("/system/category1/customer/customer_list.php?tel={$tel}&kojin_mobile={$tel}&kinmusaki_tel={$tel}"
            .'&search_method1_type=2&act=up&kensu=10&se_flg=1#customerSearchList');
    }

    /** customerDetailHtml.php?cd=: ficha HTML del socio y sus últimas 5 visitas */
    public function customerHtml(Request $request): Response
    {
        $cd = Out::form2Db($request->input('cd'));
        if ($cd === '') {
            return Out::text('ERROR');
        }

        $customer = preg_match('/^-?[0-9]+$/', $cd)
            ? DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')->where('cd', $cd)->orderBy('update_date')->first()
            : null;
        $array = array_fill_keys(self::DETAIL_KEYS, null);
        if ($customer) {
            $array = array_map(fn ($v) => $v === null ? null : (string) $v, (array) $customer) + $array;
        }

        $visits = Out::rows('SELECT tb1.raiten_time::text AS raiten_time, tb2.kaishamei::text AS kaishamei FROM tb_raiten_rireki AS tb1'
            ." INNER JOIN tb_shop AS tb2 ON tb1.shop_cd = tb2.cd WHERE tb1.user_cd = ? AND COALESCE(tb1.disp_flg,'')<>'9'"
            .' ORDER BY raiten_time DESC LIMIT 5', [(int) $cd]) ?? [];

        $html = view('publico.integraciones.cti.customer-detail', [
            'array' => $array,
            'visits' => $visits,
            'getStaff' => fn ($staffCd) => ['name' => DB::table('tb_staff')->where('cd', (int) $staffCd)->value('name')],
            'getCustomer' => fn ($customerCd) => (array) (DB::table('tb_customer')->where('cd', (int) $customerCd)->first(['id', 'name'])
                ?? ['id' => null, 'name' => null]),
        ])->render();

        return Out::text($html);
    }

    /** jGetCustomerData.php: socios activos por cd, id o teléfono como líneas `cd<>name<>name_kana<>id<>kanri_id<> ` */
    public function customerJson(Request $request): Response
    {
        $cd = Out::form2Db($request->input('cd'));
        $id = Out::form2Db($request->input('id'));
        $tel = Out::form2Db($request->input('tel'));
        if ($cd === '' && $id === '' && $tel === '') {
            return Out::text('NO_DATA');
        }

        $query = DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')->orderBy('cd');
        if ($cd !== '') {
            if (! preg_match('/^-?[0-9]+$/', $cd)) {
                return Out::text('NO_DATA'); // el legacy: cd = 'x' con bigint falla → sin filas
            }
            $query->where('cd', $cd);
        }
        if ($id !== '') {
            $query->where('id', $id);
        }
        if ($tel !== '') {
            $query->where(fn ($q) => $q->where('tel', $tel)->orWhere('kojin_mobile', $tel)->orWhere('kinmusaki_tel', $tel));
        }

        $body = '';
        foreach ($query->get(['cd', 'name', 'name_kana', 'id', 'kanri_id']) as $row) {
            $body .= $row->cd.'<>'.$row->name.'<>'.$row->name_kana.'<>'.$row->id.'<>'.$row->kanri_id."<> \n"; // 改行直前の空白必須
        }

        return Out::text($body !== '' ? $body : 'NO_DATA');
    }

    private function redirect(string $location): Response
    {
        return Out::text('', 302, ['Location' => $location]);
    }
}

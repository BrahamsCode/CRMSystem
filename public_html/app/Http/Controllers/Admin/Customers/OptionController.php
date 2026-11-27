<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * <option> que cargan los JS del legacy con `$(...).load(...)`, con la misma respuesta HTML:
 * `system/category1/inc/ajax_category.php` y `customer/ajax_category.php` (施術カテゴリー de la tienda, formulario カルテ)
 * y `customer/ajax_staff.php` (担当者 de la tienda con reserva).
 * Los nombres se escapan sin doble codificar (el legacy los imprimía tal cual).
 */
class OptionController extends Controller
{
    /** ajax_category.php: tb_sejutsu_cate activas comunes (tb_shop_cd NULL) o de la tienda shop_cd, ORDER BY cd */
    public function categories(Request $request): Response
    {
        $categories = DB::table('tb_sejutsu_cate')
            ->where('disp_flg', '1')
            ->where(fn ($q) => $q->whereNull('tb_shop_cd')->orWhere('tb_shop_cd', (int) $request->input('shop_cd')))
            ->orderBy('cd')
            ->get(['cd', 'name']);

        return $this->options('▼施術選択', $categories);
    }

    /** ajax_staff.php: tb_staff activos con reserva (reserve_flg 1) de la tienda shop_cd, ORDER BY cd */
    public function staff(Request $request): Response
    {
        $staffs = DB::table('tb_staff')
            ->where('disp_flg', '1')->where('reserve_flg', '1')
            ->where('shop_cd', (int) $request->input('shop_cd'))
            ->orderBy('cd')
            ->get(['cd', 'name']);

        if ($staffs->isEmpty()) {
            return response('<option value="">&nbsp;</option>'); // 2009.11.02 マルティン
        }

        return $this->options('▼担当者選択', $staffs);
    }

    /** Primera opción vacía con el rótulo y una opción por fila (cd, name) */
    private function options(string $label, iterable $rows): Response
    {
        $html = "\n<option value=\"\">{$label}</option>\n\n";
        foreach ($rows as $row) {
            $html .= '<option value="' . (int) $row->cd . '">' . e((string) $row->name, false) . "</option>\n";
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

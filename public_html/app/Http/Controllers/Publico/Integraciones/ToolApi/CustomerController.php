<?php

namespace App\Http\Controllers\Publico\Integraciones\ToolApi;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\ToolApi\RelatedFeatures;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * system/tool/api/category1: searchCustomerList.php, getCustomerDetail.php y getSearchCateFormData.php.
 * Mismos parámetros y la misma salida que el legacy (fixtures en tests/Fixtures/integraciones/tool).
 */
class CustomerController extends Controller
{
    use PagesToolApi;

    /** Columnas en las que busca cada palabra de `keyword` y cómo se convierte la palabra */
    private const KEYWORD_COLUMNS = [
        'name' => null, 'name_kana' => 'KVC', 'id' => null, 'tel' => 'a', 'fax' => null, 'email1' => 'a',
        'email2' => null, 'kojin_mail' => null, 'kojin_birth' => null, 'kanri_id' => null, 'kojin_mobile' => null,
        'kinmusaki_tel' => null, 'kinmusaki_fax' => null,
    ];

    /** searchCustomerList.php: socios activos (touroku_kubun 2), por tienda y palabras clave */
    public function search(Request $request): Response
    {
        $where = ["disp_flg = '1'", "touroku_kubun = '2'"];
        $bindings = [];
        if (($shop = Out::form2Db($request->input('shop'))) !== '') {
            $where[] = 'tb_shop_cd = ?';
            $bindings[] = (int) $shop;
        }
        if (($keyword = Out::form2Db($request->input('keyword'))) !== '') {
            // Cada palabra (separada por espacios 半角/全角, también las vacías) es un grupo OR
            foreach (explode(' ', str_replace('　', ' ', $keyword)) as $word) {
                $or = [];
                foreach (self::KEYWORD_COLUMNS as $column => $kana) {
                    $or[] = "{$column} LIKE ?";
                    $bindings[] = '%'.($kana ? mb_convert_kana($word, $kana) : $word).'%';
                }
                $where[] = '('.implode(' OR ', $or).')';
            }
        }

        $rows = Out::rows('SELECT '.Out::textColumns('tb_customer', 'tb_customer').' FROM tb_customer'
            .' WHERE '.implode(' AND ', $where)." ORDER BY COALESCE(last_raiten_date, '') DESC, cd DESC".$this->page($request), $bindings);

        return $this->listResponse($request, $rows);
    }

    /**
     * getCustomerDetail.php (cCustomer::getCustomerData): la ficha y `sub_info` con los datos personalizados
     * de categorías que no son de registro (record_type <> '1'). Como el legacy, `sub_info` solo lleva el
     * último de ellos (el array se reiniciaba en cada vuelta).
     */
    public function show(Request $request): Response
    {
        $cd = Out::form2Db($request->input('cd'));
        if ($cd === '') {
            return Out::empty();
        }

        $customer = preg_match('/^-?[0-9]+$/', $cd)
            ? (Out::rows('SELECT '.Out::textColumns('tb_customer', 'tb_customer').' FROM tb_customer WHERE cd = ?', [$cd])[0] ?? null)
            : null;
        if (! $customer) {
            return Out::envelope($request, 'NO_DATA', 0, '', list: false);
        }

        $pairs = Out::pairs($customer);
        $subs = Out::rows('SELECT '.Out::textColumns('tb_customer_sub', 'tb1').' FROM tb_customer_sub AS tb1'
            ." INNER JOIN tb_search_cate AS tb2 ON tb1.tb_search_cate_cd = tb2.cd WHERE tb2.record_type <> '1' AND tb1.tb_customer_cd = ?", [$cd]) ?? [];
        if ($subs) {
            $last = end($subs);
            $pairs[] = '"sub_info":{"'.$last['tb_search_form_cd'].'":{'.implode(',', Out::pairs($last)).'}}';
        }

        return Out::envelope($request, 'SUCCESS', 1, implode(',', $pairs), list: false);
    }

    /**
     * getSearchCateFormData.php: campos de la categoría de 関連機能 (admin_master.related_features) con su
     * HTML de formulario (createform_request, aquí el parcial custom_field de S2) y sus columnas, todo con rawurlencode.
     */
    public function searchForm(Request $request, RelatedFeatures $related): Response
    {
        $category = $related->category();
        if (($category['disp_flg'] ?? null) !== '1') {
            return Out::envelope($request, 'NO_DATA', 0, '');
        }

        $forms = [];
        foreach ($related->forms((int) $category['cd']) as $form) {
            $html = view('admin.clientes.partials.custom_field', [
                'field' => $form, 'values' => [], 'mode' => 'input', 'menuItems' => collect(),
            ])->render();
            $forms[] = '{ "htmlData":"'.rawurlencode($html).'", '.implode(',', Out::pairs($form, encode: true)).' }';
        }

        return Out::envelope($request, 'SUCCESS', 1, implode(',', $forms));
    }
}

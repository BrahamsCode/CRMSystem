{{-- Legacy: bloque repetido en system/category1/inc/detail_tab9.php y detail_tabcustom.php
     (filas <tr>/<th>/<td> de los campos de tb_search_form de una categoría, con disp_column columnas).
     Se imprime dentro de <table class="tableBox">.
     Variables:
       $fields   (lista de campos tb_search_form con hyouji_flg IN ('1','2') ordenados por rank; claves: name, form_type, unit, comment,
                  html = salida de createform_confirm() del legacy con el valor de tb_customer_sub ya cargado)
       $baseCnt  (int: tb_search_cate.disp_column)
       $thStyle  (string|null: atributo style del <th>; detail_tabcustom usa 'width: 150px') --}}
@php
    $baseCnt = (int) $baseCnt;
    $fieldMax = count($fields);
    $cnt = 1;
@endphp
@foreach ($fields as $fieldRow => $field)
{{-- カラム始まりは<tr>つける。 --}}
@if ($baseCnt > 1)
@if ($cnt % $baseCnt == 1)<tr>@endif
@else
<tr>
@endif
@php
    // 繰り返しが最後の場合は残りの数だけtdにcolspanをつける。
    $tdColspans = null;
    if ($fieldRow == $fieldMax - 1 && $baseCnt) {
        $tdColspansNow = ($fieldRow + 1) % $baseCnt * 2;
        if ($tdColspansNow > 0) {
            $tdColspans = 2 * $baseCnt - $tdColspansNow + 1;
        }
    }
@endphp

		<th{!! !empty($thStyle) ? ' style="' . e($thStyle) . '"' : '' !!}>{{ $field['name'] }}</th>
		<td{!! $tdColspans ? ' colspan="' . (int) $tdColspans . '"' : '' !!}>

{{-- createform_confirm(): HTML generado por el sistema que el legacy imprimía sin escapar --}}
{!! $field['html'] !!}
@if ($field['unit'])
@if ($field['form_type'] == '起算日')
<br />※検索は年数の範囲を指定します。（{{ $field['unit'] }}）<br />
@elseif ($field['form_type'] == 'テーブル')
{{-- テーブルの場合は単位を出さない。 --}}
@else
{{ $field['unit'] }}<br />
@endif
@endif

	<!--{!! nl2br(e($field['comment'])) !!}-->

		</td>

{{-- カラム数を見て、閉じるか閉じないか (misma lógica que el legacy) --}}
@if ($baseCnt > 1)
@if ($fieldMax - $cnt > $baseCnt)
@if ($cnt % $baseCnt == 0)</tr>@endif
@elseif ($fieldMax + $baseCnt - $cnt == 0)
</tr>
@endif
@else
</tr>
@endif
@php $cnt++; @endphp
@endforeach

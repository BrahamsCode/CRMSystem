{{-- Legacy: bloque "カスタマイズ" de system/category1/inc/customer_new.php (modo 'input')
     y de customer_new_confirm.php (modo 'confirm'): un <fieldset> por categoría (tb_search_cate) con sus campos (tb_search_form).
     Variables:
       $categories  colección de tb_search_cate (category='category1', disp_flg<>'9', hyouji_flg='1', record_type vacío, ORDER BY rank);
                    cada una con: cd, name, disp_column y 'forms' = sus tb_search_form (disp_flg<>'9', hyouji_flg IN ('1','2'), ORDER BY rank)
                    con: cd, name, hissu, form_type, style_class, flds_row, flds_col, titles, data, unit, comment
       $values      array de valores por nombre de campo (ver partials/custom_field)
       $mode        'input' | 'confirm'
       $menuItems   filas de tb_yoyaku_koumoku_master para form_type 'メニュー項目' (opcional)
     En modo 'input' muestra el error de validación de "form{cd}" como el legacy (<br /><font color=red>…</font>). --}}
@foreach ($categories as $category)
@php
    $forms = collect($category['forms'] ?? [])->values();
    $formMax = $forms->count();
    $baseCnt = (int) ($category['disp_column'] ?? 0);
    $cnt = 1;
@endphp
<!--カスタマイズ情報table start-->

<fieldset>
<legend>{{ $category['name'] }}</legend>
<table class="tableBox">
@foreach ($forms as $row => $form)
@php
    // カラム始まりは<tr>つける。
    $openTr = $baseCnt > 1 ? ($cnt % $baseCnt == 1) : true;

    // ラベルの値を設定
    $formLabelName = match (true) {
        in_array($form['form_type'], ['起算日', '年', '年月', '年月日'], true) => 'form'.$form['cd'].'yyy',
        $form['form_type'] === 'ラジオボタン' => 'form'.$form['cd'].'0',
        $form['form_type'] === 'チェックボックス' => 'form'.$form['cd'].'_0',
        default => 'form'.$form['cd'],
    };

    // 繰り返しが最後の場合は残りの数だけtdにcolspanをつける。
    $colspan = null;
    if ($row == $formMax - 1 && $baseCnt) {
        $colspansNow = ($row + 1) % $baseCnt * 2;
        if ($colspansNow > 0) {
            $colspan = 2 * $baseCnt - $colspansNow + 1;
        }
    }

    // カラム数を見て、閉じるか閉じないか（legacy と同じ条件）
    if ($baseCnt > 1) {
        if ($formMax - $cnt > $baseCnt) {
            $closeTr = $cnt % $baseCnt == 0;
        } else {
            $closeTr = $formMax + $baseCnt - $cnt == 0;
        }
    } else {
        $closeTr = true;
    }
    $cnt++;
@endphp
@if ($openTr)<tr>@endif
@if ($mode === 'input')
	<th style="width:87px;"><label for="{{ $formLabelName }}">{{ $form['name'] }}@if ($form['hissu'] == '1')<em>※</em>@endif</label>

</th>
@else

	<th style="width:87px;"><label for="bikou">{{ $form['name'] }}@if ($form['hissu'] == '1')<em>※</em>@endif</label>

</th>
@endif
	<td @if ($colspan) colspan="{{ $colspan }}"@endif >
@include('admin.clientes.partials.custom_field', ['field' => $form, 'values' => $values, 'mode' => $mode, 'menuItems' => $menuItems ?? []])
@if ($form['unit'] ?? '')
@if ($form['form_type'] === '起算日')
<br />※検索は年数の範囲を指定します。（{{ $form['unit'] }}）<br />
@elseif ($form['form_type'] === 'テーブル')
{{-- テーブルの場合は単位を出さない。 --}}
@else
{{ $form['unit'] }}<br />
@endif
@endif
@if ($mode === 'input')
{{-- comment: texto configurado por el administrador; el legacy lo imprimía sin escapar (nl2br) --}}
{!! nl2br((string) ($form['comment'] ?? '')) !!}
@error('form'.$form['cd'])
<br /><font color=red>{{ $message }}</font>
@enderror
@else
<!--{!! nl2br((string) ($form['comment'] ?? '')) !!}-->
@endif

</td>
@if ($closeTr)</tr>@endif
@endforeach

</table>
</fieldset>

<!--カスタマイズ情報table end-->
@endforeach

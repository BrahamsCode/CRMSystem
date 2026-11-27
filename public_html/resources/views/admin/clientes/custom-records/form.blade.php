{{-- Legacy: system/category1/inc/add_record_new.php ($mode 'input'), add_record_confirm.php con errores ($mode 'error')
     y sin errores ($mode 'confirm'); customer/add_record_*.php = las mismas con menú (顧客登録情報, 追加情報 de varios registros).
     Variables:
       $popup        bool: ventana emergente (sin menú), versión inc/ del legacy
       $mode         'input' | 'error' | 'confirm'
       $customer     Customer (cd, id, name)
       $category     array tb_search_cate (cd, name, disp_column)
       $fields       array de tb_search_form de la categoría (disp_flg <> 9, hyouji_flg 1 o 2, ORDER BY rank)
       $values       array de valores de los campos (form{cd}…, yoyaku_menu_cd; de tb_customer_sub o lo enviado)
       $fieldErrors  array form{cd} => mensaje de createform_errorcheck (solo 'error')
       $menuItems    filas de tb_yoyaku_koumoku_master para form_type 'メニュー項目'
       $hidden       array: kaiin_cd, tab, act, search_cate_cd, fukusu_group_cd, page, add_record, wintype (lo que el legacy reenviaba)
       $verifyToken  string: add_record_verify (time(), evita el doble envío) --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客登録情報 - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@php
    $formMax = count($fields);
    $baseCnt = (int) ($category['disp_column'] ?? 0);
    $isInput = $mode !== 'confirm';
    $wintype = ($popup ?? false) ? ['wintype' => 'no_headers'] : [];
    // Vuelta a la ficha (el legacy hacía POST a customer_detail.php con todo lo enviado)
    $detailParams = array_merge([$customer->cd, 'kaiin_cd' => $customer->cd, 'from' => 'reg'], array_intersect_key($hidden, array_flip(['tab', 'search_cate_cd', 'fukusu_group_cd', 'page'])), $wintype);
@endphp

@section('js')
@if ($mode === 'input')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>
@endif

<script type="text/javascript">
@if ($mode !== 'input')
var form_sended = 0;
@endif


function submitChk () {
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	return flg;
}
@if ($mode !== 'input')

function check_error() {
	if (form_sended > 0) {
		alert("ダブルクリックをしないでください。");
		return false;
	} else {
		form_sended++;
		return true;
	}
}
@endif
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■顧客登録情報</p>

@if ($isInput)
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.custom-records.confirm') }}">
@csrf
@include('admin.clientes.partials.hidden_fields', ['fields' => $hidden])
@else
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.clientes.custom-records.store') }}" onsubmit="return check_error()">
@csrf
@endif

<!--カスタマイズ情報table start-->

<fieldset>
<legend>{{ $category['name'] }}</legend>
<table class="tableBox">
	<tr>
		<th><label for="user_id">顧客ID</label></th>
		<td>{{ $customer->id }}</td>
	</tr>

	<tr>
		<th><label for="user_id">顧客名</label></th>
		<td>{{ $customer->name }}</td>
	</tr>

@foreach ($fields as $row => $form)
@php
    // カラム始まりは<tr>つける。
    $cnt = $row + 1;
    $openTr = $baseCnt > 1 ? ($cnt % $baseCnt == 1) : true;
    // ラベルの値を設定
    $formLabelName = match (true) {
        in_array($form['form_type'], ['起算日', '年', '年月', '年月日'], true) => 'form' . $form['cd'] . 'yyy',
        $form['form_type'] === 'ラジオボタン' => 'form' . $form['cd'] . '0',
        $form['form_type'] === 'チェックボックス' => 'form' . $form['cd'] . '_0',
        default => 'form' . $form['cd'],
    };
    // 繰り返しが最後の場合は残りの数だけtdにcolspanをつける。
    $colspan = null;
    if ($row == $formMax - 1 && $baseCnt) {
        $colspansNow = ($row + 1) % $baseCnt * 2;
        if ($colspansNow > 0) {
            $colspan = 2 * $baseCnt - $colspansNow + 1;
        }
    }
    // カラム数を見て、閉じるか閉じないか
    if ($baseCnt > 1) {
        $closeTr = ($formMax - $cnt > $baseCnt) ? ($cnt % $baseCnt == 0) : ($formMax + $baseCnt - $cnt == 0);
    } else {
        $closeTr = true;
    }
    // comment: texto configurado por el administrador; el legacy lo imprimía sin escapar (nl2br)
    $comment = nl2br((string) ($form['comment'] ?? ''));
@endphp
@if ($openTr)<tr>@endif
@if ($isInput)
    <th><label for="{{ $formLabelName }}">{{ $form['name'] }}@if ($form['hissu'] == '1')<em>※</em>@endif</label></th>
@else
    <th>{{ $form['name'] }}</th>
@endif
    <td @if ($colspan) colspan="{{ $colspan }}"@endif>
@include('admin.clientes.partials.custom_field', ['field' => $form, 'values' => $values, 'mode' => $isInput ? 'input' : 'confirm', 'menuItems' => $menuItems])
@if ($mode === 'error')

<br />{!! $comment !!}

<br /><font color=red>{{ $fieldErrors['form' . $form['cd']] ?? '' }}</font>
</td>
@endif
@if ($isInput && ($form['unit'] ?? ''))
@if ($form['form_type'] === '起算日')
<br />※検索は年数の範囲を指定します。（{{ $form['unit'] }}）<br />
@elseif ($form['form_type'] !== 'テーブル')
{{ $form['unit'] }}<br />
@endif
@endif
@if ($mode === 'error')
{!! $comment !!}
@endif

</td>

@if ($closeTr)</tr>@endif
@endforeach

</table>
</fieldset>

<!--カスタマイズ情報table end-->

<div class="btnBox">
@if ($mode === 'input')
<input type="button" name="dummy_back_input" id="dummy_back_input" class="btn backBtnStyle" value="戻る" onclick="document.back_form.submit()" />
<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="この内容で確認する" />
@elseif ($mode === 'error')
<input type="button" name="dummy_back_input" id="dummy_back_input" class="btn" value="戻る" onclick="document.back_form.submit()" />
<input type="submit" name="enter" id="enter" class="btn" value="この内容で確認する" />
@else
<input type="button" name="dummy_back_input" id="dummy_back_input" class="btn backBtnStyle" value="書き直す" onclick="document.back_form.submit()" />

@include('admin.clientes.partials.hidden_fields', ['fields' => $hidden + $values])
<input type="submit" name="enter" id="send_to_register" class="btn btnStyleS" value="この内容で登録する" />
<input type="hidden" name="add_record_verify" id="add_record_verify" value="{{ $verifyToken }}" />
@endif
</div>

</form>

@if ($isInput)
<form method="get" name="back_form" id="back_form" class="displayNone" action="{{ route('admin.clientes.show', $detailParams) }}">
@foreach (\Illuminate\Support\Arr::except($detailParams, [0]) as $key => $value)
<input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
<input type="submit" name="back_input" id="back_input" class="btn" value="戻る" />

</form>
@else
<form method="post" name="back_form" id="back_form" class="displayNone" action="{{ route('admin.clientes.custom-records.create') }}">
@csrf
@include('admin.clientes.partials.hidden_fields', ['fields' => $hidden + $values])
<input type="hidden" name="rewrite" value="1">
<input type="submit" name="back_input" id="back_input" class="btn" value="書き直す" />

</form>
@endif

<!-- / .contents --></div>
@endsection

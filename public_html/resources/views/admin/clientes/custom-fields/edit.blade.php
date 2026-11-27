{{-- Legacy: system/category1/customer/search_form_update.php (『カテゴリー』 検索フォーム部品設定登録更新) con el botón
     削除する (search_form_delete_up.php). La validación de search_form_new_check.php vuelve aquí con errores y old().
     Variables:
       $siteName      nombre del sitio
       $category      CustomCategory del campo
       $field         CustomField
       $values        columnas del campo + eachformN/eachsendN/eachunitN desde titles/data/unit
                      (guardadas con get_form2(): se imprimen sin escapar, como el legacy)
       $pageTitle, $formTypes, $optionTypes, $styleClasses   como en create
       $page, $kensu  página y 表示件数 de la lista de origen --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', $pageTitle . '登録更新')

@section('css')
<style type="text/css"><!--


#kojinOption,
#houjinOption {
	display: none;
}


--></style>
@endsection

@section('js')
<script type="text/javascript">
function submitChk () {
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
	return flg;
}
</script>
@include('admin.clientes.custom-fields._form-js', ['tableEachformClass' => 'writeArea inputL'])
@endsection

@php
    // Tras un error, lo enviado (escapado); si no, lo guardado (ya con entidades de get_form2)
    $hasOld = session()->hasOldInput();
    $val = fn (string $key) => $hasOld ? e((string) old($key, '')) : (string) ($values[$key] ?? '');
    $formType = html_entity_decode($val('form_type'), ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $listParams = ['pcd' => $category->cd] + array_filter(['page' => $page, 'kensu' => $kensu], fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
<a href="{{ route('admin.clientes.custom-fields.create', $category->cd) }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-fields', [$category->cd, 'pcd' => $category->cd, 'se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}登録更新</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>
<div class="btnBox" style="text-align: left;">
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.custom-fields.update', $field->cd) }}">
@csrf
@method('PUT')
<fieldset>
<legend>{{ $pageTitle }}</legend>
<table class="tableBox">





	<tr>
		<th><label for="name">フォームタイトル<span class="attention">※</span></label></th>
		<td @error('name') class="Ng"@enderror><input type="text" name="name" id="name" class="writeArea" value="{!! $val('name') !!}" />
		@error('name')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

	<tr>
		<th><label for="unit">単位</label></th>
		<td><input type="text" name="unit" id="unit" class="writeArea" @if ($formType !== 'テーブル') value="{!! $val('unit') !!}" @else value="" disabled="disabled" @endif/>
		</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{!! $val('comment') !!}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="form_type">フォームタイプ</label></th>
		<td>
<select name="form_type" onChange="disp(this)">
@foreach ($formTypes as $type)
<option value="{{ $type }}"@if ($formType === $type) selected @endif>{{ $type }}</option>
@endforeach
</select>
@include('admin.clientes.custom-fields._options', ['part' => 'optionDisp'])
	</td>
	</tr>

	<tr>
		<th><label for="name">フォーム設定</label></th>
		<td>
@include('admin.clientes.custom-fields._options', ['part' => 'eachformData'])
<span class="attention">※ラジオボタン、セレクト、チェックボックスを選んだ場合にフォームで表示する値と実際に送信する値を設定できます。</span>
		</td>
	</tr>

	<tr>
		<th><label for="style_class">スタイルクラス名</label></th>
		<td>
		<select name="style_class">
		{{ \App\Services\Customers\CustomFieldService::styleOptions(html_entity_decode($val('style_class'))) }}
		</select>
		</td>
	</tr>


</table>
</fieldset>


<!--基本情報table start-->


<a href="{{ route('admin.clientes.custom-fields', [$category->cd] + $listParams) }}"><img src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" width="90" height="15" alt="" /></a>

<input type="hidden" name="act" value="up">
<input type="hidden" name="tb_search_form_cd" value="{{ $field->cd }}">
<input type="hidden" name="pcd" id="pcd" value="{{ $category->cd }}" />
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensu }}">
<input type="submit" name="enter" id="enter" class="btn" value="{{ $pageTitle }}更新">
</form>


<form method="post" class="form" action="{{ route('admin.clientes.custom-fields.destroy', $field->cd) }}" onsubmit="return submitChk()">
@csrf
@method('DELETE')
<input type="submit" name="delete" class="btn" value="削除する">
<input type="hidden" name="act" value="delete">
<input type="hidden" name="tb_search_form_cd" value="{{ $field->cd }}">
<input type="hidden" name="pcd" id="pcd" value="{{ $category->cd }}" />
</form>


</div>
<!-- / .contents --></div>
@endsection

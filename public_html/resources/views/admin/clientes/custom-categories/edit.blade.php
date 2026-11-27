{{-- Legacy: system/category1/customer/search_cate_update.php (顧客追加情報カテゴリ設定登録更新) con el botón 削除する
     (search_cate_delete_up.php). La validación de search_cate_new_check.php vuelve aquí con errores y old().
     Variables:
       $siteName  nombre del sitio
       $category  CustomCategory (name/comment guardados con get_form2(): se imprimen sin escapar, como el legacy)
       $page, $kensu  página y 表示件数 de la lista de origen (para volver a ella) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客追加情報カテゴリ設定登録更新 - ' . $siteName)

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
@endsection

@php
    $pageTitle = '顧客追加情報カテゴリ設定';
    // Tras un error se muestra lo enviado (escapado); si no, lo guardado (ya con entidades de get_form2)
    $raw = fn (string $key) => old($key) !== null ? e(old($key)) : (string) $category->{$key};
    $listParams = array_filter(['page' => $page, 'kensu' => $kensu], fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
<a href="{{ route('admin.clientes.custom-categories.create') }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-categories', ['se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}登録更新 - {{ $siteName }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>
<div class="btnBox">
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.custom-categories.update', $category->cd) }}">
@csrf
@method('PUT')
<fieldset class="left">
<legend>{{ $pageTitle }}</legend>
<table class="tableBox">





	<tr>
		<th><label for="name"><small>カテゴリー名<span class="attention">※</span></small></label></th>
		<td @error('name') class="Ng"@enderror><input type="text" name="name" id="name_kana" class="writeArea inputSmall" value="{!! $raw('name') !!}" />
		@error('name')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

	<tr>
		<th><label for="comment"><small>コメント</small></label></th>
		<td><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{!! $raw('comment') !!}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="disp_column">表示列数</label></th>
		<td>
		<select name="disp_column">
		@for ($i = 1; $i <= 3; $i++)
		<option value="{{ $i }}"@if (old('disp_column', $category->disp_column) == $i) selected="selected"@endif>{{ $i }}</option>
		@endfor
		</select> 列
		</td>
	</tr>

	<tr>
		<th><label for="record_type">レコードタイプ</label></th>
		<td>
		<select name="record_type">
		<option value="">通常タイプ</option>
		<option value="1"@if (old('record_type', $category->record_type) == '1') selected="selected"@endif>複数タイプ</option>
		</select>
		</td>
	</tr>


</table>
</fieldset>


<!--基本情報table start-->


<a href="{{ route('admin.clientes.custom-categories', $listParams) }}"><img src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" width="90" height="15" alt="" /></a>

<input type="hidden" name="act" value="up">
<input type="hidden" name="tb_search_cate_cd" value="{{ $category->cd }}">
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensu }}">
<input type="submit" name="enter" id="enter" class="btn" value="{{ $pageTitle }}更新">
</form>


<form method="post" class="form" action="{{ route('admin.clientes.custom-categories.destroy', $category->cd) }}" onsubmit="return submitChk()">
@csrf
@method('DELETE')
<input type="submit" name="delete" class="btn" value="削除する">
<input type="hidden" name="act" value="delete">
<input type="hidden" name="tb_search_cate_cd" value="{{ $category->cd }}">
</form>


</div>
<!-- / .contents --></div>
@endsection

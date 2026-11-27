{{-- Legacy: system/category1/customer/search_cate_new.php (顧客追加情報カテゴリ設定新規登録); la validación de
     search_cate_new_check.php vuelve aquí con los errores (Ng + <p class="m0">) y lo escrito (old()).
     Variables:
       $siteName  nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客追加情報カテゴリ設定新規登録 - ' . $siteName)

@section('css')
<style type="text/css"><!--


#kojinOption,
#houjinOption {
	display: none;
}


--></style>
@endsection

@php $pageTitle = '顧客追加情報カテゴリ設定'; @endphp

@section('content')
<a href="{{ route('admin.clientes.custom-categories.create') }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-categories', ['se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}新規登録 - {{ $siteName }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.custom-categories.store') }}">
@csrf
<fieldset>
<legend>{{ $pageTitle }}</legend>
<table class="tableBox">





	<tr>
		<th><label for="name">カテゴリー名<span class="attention">※</span></label></th>
		<td @error('name') class="Ng"@enderror><input type="text" name="name" id="name" class="writeArea" value="{{ old('name') }}" />
		@error('name')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ old('comment') }}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="disp_column">表示列数</label></th>
		<td>
		<select name="disp_column">
		@for ($i = 1; $i <= 3; $i++)
		<option value="{{ $i }}"@if (old('disp_column') == $i) selected="selected"@endif>{{ $i }}</option>
		@endfor
		</select> 列
		</td>
	</tr>

	<tr>
		<th><label for="record_type">レコードタイプ</label></th>
		<td>
		<select name="record_type">
		<option value="">通常タイプ</option>
		<option value="1"@if (old('record_type') == '1') selected="selected"@endif>複数タイプ</option>
		</select>
		</td>
	</tr>


</table>
</fieldset>


<!--基本情報table start-->





<div class="btnBox">
<a href="{{ route('admin.clientes.custom-categories') }}"><img src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" width="90" height="15" alt="" /></a>

<input type="hidden" name="act" value="new">
<input type="submit" name="enter" id="enter" class="btn" value="{{ $pageTitle }}登録">
</div>

</form>

<!-- / .contents --></div>
@endsection

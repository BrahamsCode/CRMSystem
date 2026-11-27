{{-- Legacy: system/category1/customer/search_form_new.php (『カテゴリー』 検索フォーム部品設定新規登録); la validación de
     search_form_new_check.php vuelve aquí con los errores (Ng + <p class="m0">) y lo escrito (old()).
     Variables:
       $siteName      nombre del sitio
       $category      CustomCategory (pcd)
       $pageTitle     『カテゴリー名』 検索フォーム部品設定
       $formTypes     los 15 form_type en el orden del legacy
       $optionTypes   form_type con オプション数
       $styleClasses  make_stylesheet_options() (no se usa directamente: CustomFieldService::styleOptions()) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', $pageTitle . '新規登録')

@section('css')
<style type="text/css"><!--


#kojinOption,
#houjinOption {
	display: none;
}


--></style>
@endsection

@section('js')
@include('admin.clientes.custom-fields._form-js', ['tableEachformClass' => 'writeArea inputM'])
@endsection

@php
    // Lo enviado tras un error, escapado (equivale a h($_POST[…]) del legacy)
    $val = fn (string $key) => e((string) old($key, ''));
    $formType = (string) old('form_type', '');
@endphp

@section('content')
<a href="{{ route('admin.clientes.custom-fields.create', $category->cd) }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-fields', [$category->cd, 'pcd' => $category->cd, 'se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}新規登録 - {{ $siteName }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.custom-fields.store', $category->cd) }}">
@csrf
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
		<th>フォーム設定</th>
		<td>
@include('admin.clientes.custom-fields._options', ['part' => 'eachformData'])
<span class="attention">※ラジオボタン、セレクト、チェックボックスを選んだ場合にフォームで表示する値と実際に送信する値を設定できます。</span>
		</td>
	</tr>

	<tr>
		<th><label for="style_class">スタイルクラス名</label></th>
		<td>
		<select name="style_class">
		{{ \App\Services\Customers\CustomFieldService::styleOptions(old('style_class')) }}
		</select>
		</td>
	</tr>


</table>
</fieldset>


<!--基本情報table start-->





<div class="btnBox">
<a href="{{ route('admin.clientes.custom-fields', [$category->cd, 'pcd' => $category->cd]) }}"><img src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" width="90" height="15" alt="" /></a>

<input type="hidden" name="act" value="new">
<input type="hidden" name="pcd" value="{{ $category->cd }}">
<input type="submit" name="enter" id="enter" class="btn" value="{{ $pageTitle }}登録">
</div>

</form>

<!-- / .contents --></div>
@endsection

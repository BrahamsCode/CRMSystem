{{-- Legacy: sección シフト区分管理 de system/category5/kintai/config.php (+ staff_shift_kind_register.php).
     Formulario de alta/edición (nombre y カラー con el ColorPicker del legacy) y lista con 修正・削除 y ▲▼.
     Variables: $kind (StaffShiftKind|null en edición), $kinds (activos en orden), $siteName --}}
@extends('layouts.legacy')

@php($pageTitle = 'シフト区分管理')

@section('menu', 'operaciones')

@section('title', $pageTitle.' - '.($siteName ?? ''))

@section('css')
<link rel="stylesheet" media="screen" type="text/css" href="/system/category5/kintai/css/colorpicker.css" />
@endsection

@section('js')
<script src="/system/category5/kintai/js/colorpicker.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac3").accordion('option', 'active', 0);

	$('#color, #colorSelector').ColorPicker({
	onSubmit: function(hsb, hex, rgb, el) {
		$(el).val(hex);
		$(el).ColorPickerHide();
	},
	onBeforeShow: function () {
		$(this).ColorPickerSetColor(this.value);
	},
	onChange: function (hsb, hex, rgb) {
		$('#color').val('#' + hex);
		$('#colorSelector').css('backgroundColor', '#' + hex);
	}
})
});

function submitChk () {
	if(document.form1.name.value == ""){
		alert("{{ $pageTitle }}名が空白です。\n\n{{ $pageTitle }}名をご記入下さい。");
		return false;
	}
	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}

function submitChk2 (val) {
	element = eval("document.form_" + val);
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");
	if(flg){
		element.submit();
	}
}
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $pageTitle }} 登録</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $kind ? route('admin.operaciones.shift-kinds.update', $kind->cd) : route('admin.operaciones.shift-kinds.store') }}" onsubmit="return submitChk()">
@csrf
@if ($kind)
@method('PUT')
@endif
<fieldset>
<legend>{{ $pageTitle }}情報</legend>
<table class="tableBox">
<tr>
<th>{{ $pageTitle }}名</th>
<td>
<input type="text" name="name" id="name" class="writeArea" value="{{ old('name', $kind?->name) }}" />
</td>
</tr>
<tr>
<th>{{ $pageTitle }}　カラー</th>
<td>
<input type="text" name="color" id="color" class="writeArea" value="{{ old('color', $kind?->color) }}" />
<div id="colorSelector" style="width: 20px;height: 20px;background-color: {{ old('color', $kind?->color) }};border: 2px solid #aaa;display: inline-block;"></div>
</td>
</tr>
</table>
</fieldset>

<div class="btnBox">
@if ($kind)
<input type="submit" name="enter" id="revise" class="btnStyleM" value="この内容で修正する" />
<input type="hidden" name="cd" id="cd" value="{{ $kind->cd }}" />
@else
<input type="submit" name="enter" id="reivse" class="btnStyleM" value="この内容で登録する" />
@endif
</div>
</form>

<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>
<div class="form">
@if ($kinds->isNotEmpty())
<p class="textCenter">{{ $kinds->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
	<tr>
		<th>{{ $pageTitle }}名</th>
		<th>カラー</th>
		<th>修正・削除</th>
		<th>並び</th>
	</tr>
@foreach ($kinds as $row)
@php($memCnt = $loop->iteration)
	<tr @class(['tableBg' => $loop->index % 2 != 0])>
	<td>{{ $row->name }}</td>
	<td>
@if (filled($row->color))
		<div style="width: 20px;height: 20px;background-color: {{ $row->color }};border: 2px solid #aaa;display: inline-block;"></div>
@endif
	</td>
	<td class="textCenter" width="100">
	<a href="{{ route('admin.operaciones.shift-kinds.index', ['cd' => $row->cd]) }}" class="btnStyle">修正</a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;" class="btnStyle">削除</a>
	<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.operaciones.shift-kinds.destroy', $row->cd) }}">
	@csrf
	@method('DELETE')
	<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
	<input type="submit" name="delete" class="btn" value="削除する">
	</form>
	</td>
	<td style="width: 3em;">{{ $loop->iteration }}
	<ul style="padding:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
@unless ($loop->first)
	<li><a href="{{ route('admin.operaciones.shift-kinds.sort', ['thisCd' => $row->cd, 'kind' => 'up']) }}">▲</a></li>
@else
	&nbsp;
@endunless
@unless ($loop->last)
	<li><a href="{{ route('admin.operaciones.shift-kinds.sort', ['thisCd' => $row->cd, 'kind' => 'down']) }}">▼</a></li>
@endunless
	</ul>
	</td>
  </tr>
@endforeach
</table>
@else
{{ $pageTitle }}は登録されていません。
@endif

<ul class="btnBox2">
</ul>

</div>

<!-- / .contents --></div>
@endsection

{{--
	カードリーダ設定 — legacy: system/category3/visit/touch_config.php
	Variables: $shops (colección de ['cd', 'kaishamei', 'interval']), $options ([segundos => texto])
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'カードリーダ設定 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#zen_kankaku").change(function(){
		val = $("#zen_kankaku").val();
		if (val != "") $(".kankaku").val(val);
	});

	$(".kankaku").change(function(){
		$("#zen_kankaku").val("");
	});
});
</script>
@endsection

@section('content')
@php
	$count = $shops->count();
	$perLine = $count > 1 ? 2 : 1; // 一行で表示する店舗数
@endphp
<div class="contents">
<p class="h2Title">■カードリーダ設定</p>

<div>

@if ($count > 0)
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.visit.touch-config.update') }}">

<fieldset>
<legend>カード重複読込時間間隔の設定</legend>

@if ($count > 1)
<table class="tableBox" style="width:50%;">
<tr>
	<th style="width:20%; text-align:center;">&nbsp;</th>
	<th style="width:10%; text-align:center;">間隔</th>
</tr>
<tr>
	<th style="width:20%; text-align:center;">全店舗</th>
	<td style="width:10%; text-align:center;">
		<select id="zen_kankaku">
			<option value=""></option>
@foreach ($options as $value => $label)
			<option value="{{ $value }}">{{ $label }}</option>
@endforeach
		</select>
	</td>
</tr>
</table>

<p><span class="attention">※</span>全店舗変更をすると以前の設定が消えてしまいますので、変更の際はご注意ください。</p><br />
@endif
<table class="tableBox">
<tr>
@for ($i = 0; $i < $perLine; $i++)
		<th style="width:20%;">店舗名</th>
		<th style="width:10%; text-align:center;">間隔</th>
@endfor
</tr>
@foreach ($shops->values() as $i => $shop)
	@if ($i % $perLine == 0)<tr>
	@endif
		<th>{{ $shop['kaishamei'] }}</th>
		<td style="text-align:center;">
		<select name="kankaku[{{ $shop['cd'] }}]" class="kankaku">
@foreach ($options as $value => $label)
			<option value="{{ $value }}" @selected($shop['interval'] === $value)>{{ $label }}</option>
@endforeach
		</select>
		</td>
	@if ($i % $perLine == $perLine - 1)</tr>
	@endif
@endforeach
@if (($count - 1) % $perLine != $perLine - 1)
		<td colspan="{{ (($perLine - 1) - ($count - 1) % $perLine) * 3 }}">&nbsp;</td>
	</tr>
@endif

</table>
</fieldset>


<div class="btnBox">
<input type="hidden" name="act" id="act" value="up">
<input type="submit" name="send" id="send" class="btn" value="更新" />
</div>

</form>


@else
スタンプ柄データはあかりません。
@endif


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection

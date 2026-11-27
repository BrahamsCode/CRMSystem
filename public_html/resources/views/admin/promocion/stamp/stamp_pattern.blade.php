{{--
	スタンプデザイン管理 — legacy: system/category3/stamp/stamp_pattern.php
	Variables: $patterns (tb_stamp_pattern con disp_flg '1', por narabi)
	Imágenes: /hp/uploads/c4/stamp/<cd>.gif (archivos de producción en Storage; ruta /hp/uploads de S1).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'スタンプデザイン管理' . ' - ' . $siteName)

@php
	$perLine = 5; // 一行で表示するスタンプ数
	$count = $patterns->count();
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac6").accordion('option', 'active', 0);
	window.scroll(0,0);

	check_elements();

	$(".sentaku").click(function(){
		check_elements();
	});
});

var stamp_max = 10;
var total_checked;

// チェックボックスとラジオボタンのdisable/enable切り替え (attr("checked") del jQuery antiguo → prop)
function check_elements() {
	total_checked = 0;
	$(".sentaku").each(function(){
		cd = $(this).val();
		if ($(this).prop("checked")) {
			total_checked++;
			$("#default_"+cd).prop("disabled", false);
		} else {
			$("#default_"+cd).prop("checked", false);
			$("#default_"+cd).prop("disabled", true);
		}
	});
	if (total_checked >= stamp_max) {
		$(".sentaku").each(function(){
			if (!$(this).prop("checked")) {
				$(this).prop("disabled", true);
			}
		});
	} else {
		$(".sentaku").prop("disabled", false);
	}
}

// フォームのバリデーション
function validate_form() {
	if (total_checked == 0){
		alert ("スタンプを選択して下さい。");
		return false;
	}

	if (!$(".default:checked").val()) {
		alert ("デフォルトスタンプを選択して下さい。");
		return false;
	}

	return true;
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■スタンプデザイン管理</p>

<div>

@if ($count > 0)
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.stamps.pattern.update') }}" onsubmit="return validate_form()">
@csrf

<p>選択…MYページでお客様が選択できるスタンプデザインを10個まで選択できます。<br />
設定…MYページで選択がない場合に表示される初期設定のスタンプデザインです。</p>

<p><span class="attention">※</span>複数のお店がある場合も共通のデザインとなりますので、変更の際はご注意ください。</p>

<fieldset>
<legend>スタンプ選択</legend>

<table class="tableBox">

<tr>
@for ($i = 0; $i < $perLine; $i++)
		<th style="width:50px; text-align:center;">ｽﾀﾝﾌﾟ</th>
		<th style="width:50px; text-align:center;">選択</th>
		<th style="width:50px; text-align:center;">設定</th>
@endfor
</tr>
@foreach ($patterns as $i => $pattern)
	@if ($i % $perLine === 0)
<tr>
	@endif
		<th style="width:50px; text-align:center;"><label for="sentaku_{{ $pattern->cd }}"><img src="/hp/uploads/c4/stamp/{{ $pattern->cd }}.gif"></label></th>
		<td><input type="checkbox" class="sentaku" name="sentaku[{{ $pattern->cd }}]" id="sentaku_{{ $pattern->cd }}" value="{{ $pattern->cd }}" @if ((string) $pattern->pic_shop_flg === '1') checked @endif/></td>
		<td><input type="radio" class="default" name="default" id="default_{{ $pattern->cd }}" value="{{ $pattern->cd }}" @if ((string) $pattern->pic_default === '1') checked @else disabled @endif/></td>
	@if ($i % $perLine === $perLine - 1)
</tr>
	@endif
@endforeach
@if (($count - 1) % $perLine !== $perLine - 1)
		<td colspan="{{ (($perLine - 1) - ($count - 1) % $perLine) * 3 }}">&nbsp;</td>
	</tr>
@endif

</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="act" id="act" value="up">
<input type="submit" name="send" id="send" class="btn btnStyleM" value="更新" />
</div>

</form>

@else
スタンプ柄データはあかりません。
@endif

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection

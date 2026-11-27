{{-- Legacy: system/category5/kintai/kintai_new.php (勤怠データ新規追加). Mismo HTML y mensajes de error.
     Variables: $shopCd, $shopName, $yy/$mm/$dd (int), $hours ([kijun, kijun+24]), $staff (cd, name),
     $statuses (KintaiHiduke::DAY_TYPES), $input (petición, para volver a pintar), $errorText (HTML del legacy),
     $query (yy/mm/dd/shop) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠データ新規追加 - '.($siteName ?? '').' - ')

@section('body_class', 'kintaiNew')

@php
    $old = fn (string $key) => is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
@endphp

@section('css')
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />

<style type="text/css">
<!--

p.errorText
{
	padding: 5px;
	border: 1px dotted #FF0000;
	color: #FF0000;
}

-->
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});

// =============================================================================
// 戻るボタン
// =============================================================================

function pageBack(){

	location.href = "{!! route('admin.operaciones.kintai.index', $query) !!}";

} // function pageBack() --- END

// =============================================================================
// フォームの送信時にdisabledを解除
// =============================================================================

function undisabled(){

	$('#scheduleTbl select').attr('disabled',false);
	return true;

} // function undisabled() --- END
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■勤怠データ新規追加</p>


<form class="form" method="post" action="{{ route('admin.operaciones.kintai.store') }}">
@csrf


<p>勤怠データを手動で新規追加できます。<br />
<span class="attention bold">*</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>
@if ($errorText !== '')<p class="errorText">{!! $errorText !!}</p>@endif


<div class="configArea">



<fieldset>
<legend>新規追加登録内容</legend>

<table class="tableBox">


<tr>
<th>登録店舗</th>
<td>{{ $shopName }}<input type="hidden" name="shop" id="shop" value="{{ $shopCd }}" /></td>
</tr>


<tr>
<th>勤怠データを追加する日付</th>
<td>

@include('admin.operaciones.kintai.partials.date-selects')

</td>
</tr>


<tr>
<th>スタッフ名<span class="attention">*</span></th>
<td>

<select id="user" name="user">
<option value="">選択してください</option>
@foreach ($staff as $member)
<option value="{{ $member->cd }}"@if ($old('user') === (string) $member->cd) selected="selected"@endif>{{ $member->name }}</option>
@endforeach
</select>

</td>
</tr>


<tr>
<th>勤務状況</th>
<td>

<select id="status" name="status">
@foreach ($statuses as $status)
<option value="{{ $status }}"@if ($old('status') === $status) selected="selected"@endif>{{ $status }}</option>
@endforeach
</select>

</td>
</tr>

@foreach (['shukkin' => '出勤時間<span class="attention">*</span>', 'gaishutu' => '外出時間', 'modori' => '戻り時間', 'taikin' => '退勤時間'] as $key => $label)

<tr>
<th>{!! $label !!}</th>
<td>

<select id="{{ $key }}_h" name="{{ $key }}_h">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $old($key.'_h')])
</select>
：
<select id="{{ $key }}_m" name="{{ $key }}_m">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $old($key.'_m')])
</select>

</td>
</tr>
@endforeach


<tr>
<th>備考</th>
<td><textarea class="inputM2" cols="40" rows="4" id="bikou" name="bikou">{{ $old('bikou') }}</textarea></td>
</tr>


</table>

</fieldset>



<div class="btnBox">
<input type="button" name="back" class="btnStyleM" onclick="pageBack();" value="　　戻る　　" />
<input type="submit" name="act" class="btnStyleM" id="act" onclick="undisabled();" value="　　登録　　" />
<!-- □□ /.btnBox □□ --></div>



<!-- □□ /.configArea □□ --></div>


</form>


<!-- / .contents --></div>
@endsection

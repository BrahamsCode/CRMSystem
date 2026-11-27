{{-- Legacy: system/category1/config/felica_mode.php (カードリーダーモード設定). Los dos formularios hacen POST a
     admin.clientes.reader-mode.update (el legacy, a la misma página).
     Variables: $siteName, $mode (tb_felica_mode.mode de la fila cd = 2; '' sin fila), $interval (interval_time),
       $intervalMinutes (lista de minutos del selector) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '初期設定管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac3").accordion('option', 'active', 0);

});

</script>

<script type="text/javascript">
<!--
function modeSelect(i){
	document.mode_action.felica_mode[i].checked = 'check'
	document.mode_action.submit();
}



--></script>
@endsection

@php
    // Radio marcada como el legacy: 9, 1, 2 o, en cualquier otro caso, 0
    $checked = in_array($mode, ['9', '1', '2'], true) ? $mode : '0';
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■カードリーダーモード設定</p>

<form name="mode_action" id="mode_action" class="form" action="{{ route('admin.clientes.reader-mode.update') }}" method="post">
@csrf

<fieldset>
<legend>モードセレクト</legend>

<ul class="modeSelect">
<li{!! $mode === '0' ? ' class="modeSelected"' : '' !!} onclick="modeSelect(0)">運用モード</li>
<li{!! $mode === '1' ? ' class="modeSelected"' : '' !!} onclick="modeSelect(1)">入室強制モード</li>
<li{!! $mode === '2' ? ' class="modeSelected"' : '' !!} onclick="modeSelect(2)">退室強制モード</li>
</ul>

<div class="displayNone">
<ul>
<li><input type="radio" name="felica_mode" id="felica_mode1" value="0" @checked($checked === '0')>運用モード</li>
<li><input type="radio" name="felica_mode" id="felica_mode2" value="1" @checked($checked === '1')>入室強制モード</li>
<li><input type="radio" name="felica_mode" id="felica_mode3" value="2" @checked($checked === '2')>退室強制モード</li>
</ul>
<input type="hidden" name="felica_mode_up" value="up">
<input type="submit" value="モード変更">
</div>

</fieldset>

</form>



<form name="interval_time_action" id="interval_time_action" class="form" action="{{ route('admin.clientes.reader-mode.update') }}" method="post">
@csrf

<fieldset>
<legend>カード重複読込時間間隔の設定</legend>

<select name="inter_time">
@foreach ($intervalMinutes as $minutes)
<option value="{{ $minutes * 60 }}"@if ($interval !== '' && is_numeric($interval) && (float) $interval == $minutes * 60) selected="selected"@endif>{{ $minutes }}分</option>
@endforeach
<option value="1"@if (is_numeric($interval) && (float) $interval == 1) selected="selected"@endif>テスト</option>
</select>
<input type="submit" name="interval_time_enter" id="interval_time_enter" value="設定を変更する" />
<input type="hidden" name="interval_time_action_act" id="interval_time_action_act" value="up" />

<p class="attention">※連続した入退室を防止するために、運用時の間隔はテスト以外を選択しておいてください。</p>

</fieldset>

</form>



<!-- / .contents --></div>
@endsection

{{-- Legacy: system/category1/shukei/shukei_list.php (集計ツール).
     Variables: $links (colección ReportLink visible por rank: cd, url, title, naiyou), $isTablet (bool: roid.popUp),
       $host (esquema y dominio: $system_domain del legacy para roid.popUp).
     title y naiyou se imprimen sin escapar, como el legacy: los escribe solo la configuración del sistema (db_fix.php). --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '集計ツール - ' . $siteName)

@section('js')
<script type="text/javascript" src="/system/js/formToWizard.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■集計ツール</p>

<form method="post" name="form1" id="form1" class="form" action="" onsubmit="return false">

@if ($links->isNotEmpty())
<fieldset>
<legend>用途を選択してください</legend>


<table class="tableBox bgHover">
@foreach ($links as $link)
@php
	$popJs = $isTablet
		? "roid.popUp('" . e($host . $link->url) . "');"
		: "window.open('" . e($link->url) . "', 'popShukei" . $link->cd . "', 'menubar=no, toolbar=no, scrollbars=yes');";
@endphp
	<tr>
		<td><a href="javascript:void(0)" onclick="{!! $popJs !!}"><span class="text2L bold">{!! $link->title !!}</span></a><br />
		{!! $link->naiyou !!}</td>
	</tr>
@endforeach
</table>
</fieldset>
@endif


</form>
<!-- / .contents --></div>
@endsection

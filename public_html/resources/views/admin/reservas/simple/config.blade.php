{{-- Legacy: system/category3/reservation/config.php + inc/check_select.php (予約項目設定: tb_koumoku con tb_name = 'tb_reserve').
     Variables:
       $items     Collection filas de tb_koumoku (cd, tb_koumoku_name, koumoku_disp, koumoku_check) ORDER BY cd
       $siteName  nombre del sitio
     La fila 担当者指名 del 飲食店版 que ocultaba check_select.php dependía de $tb_package_master_array, que config.php
     nunca cargaba: se muestran todas. --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約項目設定 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);

});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■予約項目設定</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.simple.config.update') }}">
@csrf
<fieldset>
<legend>顧客情報</legend>
<font color="#C2E4EF">■</font>…登録時表示項目　<font color="#FF9F9F">■</font>…必須項目<br />
<table class="tableBox">
@foreach ($items as $item)
@php $itemNo = $loop->iteration; @endphp
	<tr>
	<th style="width: 50%;"><small>{{ $item->tb_koumoku_name }}</small></th>

		<td bgcolor="#C2E4EF" class="textCenter"><label class="cellLable"><input type="checkbox" name="item_cd1_{{ $itemNo }}" id="item_cd1_{{ $itemNo }}" value="1" @checked((float) $item->koumoku_disp == 1) /></label></td>

		<td bgcolor="#FF9F9F" class="textCenter"><label class="cellLable"><input type="checkbox" name="item_cd2_{{ $itemNo }}" id="item_cd2_{{ $itemNo }}" value="1" @checked((float) $item->koumoku_check == 1) /></label>
		<input type="hidden" name="item_cd{{ $itemNo }}" id="item_cd{{ $itemNo }}" value="{{ $item->cd }}" />
		</td>

	</tr>
@endforeach

</table>
</fieldset>
<!--基本情報table start-->

<div class="btnBox">

<input type="hidden" name="item_max_no" value="{{ $items->count() }}">
<input type="hidden" name="act" value="up">
<input type="submit" name="enter" id="enter" class="btn" value="この内容で変更する">
</div>

</form>

<!-- / .contents -->
</div>
@endsection

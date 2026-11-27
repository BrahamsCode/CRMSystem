{{-- Legacy: system/category1/inc/karute_frm.php (incluido por karute_new.php y karute_update.php): カルテ 新規登録.
     Variables:
       $popup              bool: ventana emergente (sin menú), versión inc/ del legacy
       $siteName           nombre del sitio para el <title>
       $customer           Customer (cd, id, name)
       $karte              Karte|null: la カルテ que se edita (null = alta)
       $data               array: tb_shop_cd, hiduke (Y/m/d), tb_sejutsu_cate_cd, comment (texto ya pasado por get_form())
       $fieldErrors        array campo => mensaje del legacy (tb_shop_cd, hiduke, tb_sejutsu_cate_cd)
       $shops              array cd => kaishamei (disp_flg <> 9; sin master solo la de la sesión)
       $fixedShop          bool: una sola tienda o sin master → nombre + campo oculto en vez de selector
       $serviceCategories  array cd => name de tb_sejutsu_cate de la tienda elegida (o de todas)
       $hidden             array: lo recibido que el legacy reenviaba como oculto ($form_content: kaiin_cd, tab, page, wintype…)
       $karuteCd           int|string|null: カルテ (campo karute_cd) --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
{{-- El legacy pone 「新規登録」 también al modificar ($mode fijo en karute_update.php) --}}
@section('title', 'カルテ 新規登録 - ' . $siteName)

@section('js')
{{-- inc/cal.php (calendario) --}}
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/YahhoCal.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/GCalHolidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/yuiloader-dom-event.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/calendar-min.js') }}"></script>
<script type="text/javascript">
function format1(id) {
	YahhoCal.format.padZero = true;
	YahhoCal.render(id);
}
</script>

<link type="text/css" rel="stylesheet" href="{{ asset('legacy/system/css/calendar.css') }}" media="all">

@include('admin.clientes.partials.ac')

<script type="text/javascript">
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#tb_shop_cd").change(function(){
		send_val = $("#tb_shop_cd").val();
		$("#tb_sejutsu_cate_cd").html("<option>Loading...</option>");
		// URL del legacy (inc/ajax_category.php): el JS la llamaba relativa a inc/
		$("#tb_sejutsu_cate_cd").load("{{ url('system/category1/inc/ajax_category.php') }}",{
			shop_cd:send_val
		});
	});
});
</script>
@endsection

@php
    $ng = fn (string $field) => isset($fieldErrors[$field]) ? ' class="Ng"' : '';
    $shopCd = (string) ($data['tb_shop_cd'] ?? '');
    $sejutsuCd = (string) ($data['tb_sejutsu_cate_cd'] ?? '');
    $action = $karte
        ? route('admin.clientes.kartes.update', [$customer->cd, $karte->cd])
        : route('admin.clientes.kartes.store', $customer->cd);
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■カルテ 新規登録</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $action }}">
@csrf
@if ($karte)
@method('PUT')
@endif

<fieldset>
<legend>カルテ</legend>

<table class="tableBox">

	<tr>
		<th><label for="user_id">顧客ID</label></th>
		<td>{{ $customer->id }}</td>
	</tr>

	<tr>
		<th><label for="user_id">顧客名</label></th>
		<td>{{ $customer->name }}</td>
	</tr>

	<tr>
		<th><label for="tb_shop_cd">店舗<em>※</em></label></th>
		<td{!! $ng('tb_shop_cd') !!}>

		@if ($fixedShop)
		@php $firstShopCd = array_key_first($shops); @endphp
		{{ $shops[$firstShopCd] ?? '' }}
		<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $firstShopCd }}">
		@else
		<select name="tb_shop_cd" id="tb_shop_cd">
		<option value="">▼店舗選択</option>
@foreach ($shops as $cd => $name)
<option value="{{ $cd }}" @selected($shopCd === (string) $cd)>{{ $name }}</option>
@endforeach
		</select>
		@endif
		@isset($fieldErrors['tb_shop_cd'])<p class="m0">{{ $fieldErrors['tb_shop_cd'] }}</p>@endisset
		</td>
	</tr>

	<tr>
		<th><label for="hiduke">受付年月日<em>※</em></label></th>
		<td{!! $ng('hiduke') !!}>
		<input type="text" name="hiduke" id="hiduke" class="inputM" maxlength="10" style="ime-mode:disabled;" onclick="format1(this.id);" value="{{ $data['hiduke'] ?? '' }}" />(例: 2009/12/01)
		@isset($fieldErrors['hiduke'])<br />{{ $fieldErrors['hiduke'] }}@endisset
		</td>
	</tr>

	<tr>
		<th><label for="tb_sejutsu_cate_cd">施術<em>※</em></label></th>
		<td{!! $ng('tb_sejutsu_cate_cd') !!}>
		<select name="tb_sejutsu_cate_cd" id="tb_sejutsu_cate_cd">
		<option value="">▼施術選択</option>@foreach ($serviceCategories as $cd => $name)<option value="{{ $cd }}" @selected($sejutsuCd === (string) $cd)>{!! $name !!}</option>@endforeach{{-- name guardado con get_form2() --}}
		</select>
		@isset($fieldErrors['tb_sejutsu_cate_cd'])<p class="m0">{{ $fieldErrors['tb_sejutsu_cate_cd'] }}</p>@endisset
		</td>
	</tr>

	<tr>
		<th><label for="comment">備考</label></th>
		<td>
		<textarea name="comment" id="comment" rows="10" class="writeArea mB5">{{ $data['comment'] ?? '' }}</textarea>
		</td>
	</tr>

</table>
</fieldset>

<input type="button" name="dummy_back_input" id="dummy_back_input" class="btn" value="戻る" onclick="document.back_btn.submit()" />

<div class="btnBox">
@foreach ($hidden as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach

<input type="hidden" name="karute_cd" id="cd" value="{{ $karuteCd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn" value="この内容で登録する" />
</div>

</form>
{{-- 戻る: la ficha (en el legacy, POST a customer_detail.php con los mismos ocultos) --}}
<form name="back_btn" id="back_btn" action="{{ route('admin.clientes.show', $customer->cd) }}" method="get" style="display: none">
@foreach ($hidden as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach

<input type="hidden" name="karute_cd" id="cd" value="{{ $karuteCd }}" />
<input type="hidden" name="from" id="from" value="reg">
<input type="submit" name="enter" id="enter" class="btn" value="戻る">
</form>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection

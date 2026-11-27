{{-- Legacy: system/category3/reservation/reserve_new.php (予約新規登録 / 予約登録情報 修正) y la rama «エラーの場合» de
     reserve_new_confirm.php (mismo formulario con los avisos; ahí la fila de teléfono se llama 電話番号 y .contents lleva .form).
     Variables:
       $data         array con el texto tal cual de cada campo (SimpleReservationService::FORM_FIELDS; tel_display = lo escrito)
       $act          'new' | 'up'
       $customer     ?object socio (cd, id, name, email1…) si viene kaiin_cd
       $isMember     bool: hay kaiin_cd (nombre y correo del socio, no editables)
       $shops        Collection tiendas (cd, kaishamei): todas (master) o la de la sesión
       $shopCd       ?int tienda elegida
       $categories   Collection 予約内容 (cd, reserve_cate_name con entidades: {!! !!})
       $staffList    Collection 指名 (cd, name)
       $ninsuFlg, $shimeiFlg  bool: tb_koumoku reserve_people / reserve_nomination visibles
       $canSeeMail   bool: permiso 20-1 (correo del socio) o master
       $fieldErrors  array [campo => texto] (shopcd, name, tel, email, hiduke, ninsuu); vacío en reserve_new.php
       $isConfirm    bool: pintado desde reserve_new_confirm.php
       $siteName     nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', ($act === 'new' ? '予約新規登録' : '予約登録情報 修正') . ' - ' . $siteName)

@section('css')
{{-- inc/cal.php (calendario) --}}
<link type="text/css" rel="stylesheet" href="{{ asset('legacy/system/css/calendar.css') }}" media="all">
@endsection

@section('js')
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

<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);
	{{-- <body onLoad="document.forms[0].name.focus();"> --}}
	$("#form1 input#name[type=text]").focus();

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#tb_shop_cd").change(function(){
		send_val = $("#tb_shop_cd").val();
		$("#category_cd").html("<option>Loading...</option>");
		$("#category_cd").load("{{ route('legacy.reservas.simple.options.categories') }}",{
			shop_cd:send_val
		});

		if ($('#category_cd > option').length > 1 ) { // category_cdセレクトボックスのoptio数
			$("#category_disp").show();
		} else {
			$("#category_disp").hide();
		}

		$("#staff_cd").html("<option>Loading...</option>");
		$("#staff_cd").load("{{ route('legacy.reservas.simple.options.staff') }}",{
			shop_cd:send_val
		});
	});
});
</script>
@endsection

@php
    $err = fn (string $key) => array_key_exists($key, $fieldErrors) ? ' class="Ng"' : '';
    $errText = fn (string $key) => array_key_exists($key, $fieldErrors) && $fieldErrors[$key] !== '' ? '<br />' . e($fieldErrors[$key]) : '';
@endphp

@section('content')
<div class="contents{{ $isConfirm ? ' form' : '' }}">
<p class="h2Title">■{{ $act === 'new' ? '予約新規登録' : '予約登録情報 修正' }}</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.simple.confirm') }}">
@csrf

<fieldset>
<legend>予約情報</legend>
<table class="tableBox">

@if ($isMember)
	<tr>
		<th>会員番号</th>
		<td>{{ $customer?->id }}</td>
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $data['kaiin_cd'] }}" />
	</tr>
@endif

	<tr>
		<th><label for="tb_shop_cd">店舗<em>※</em></label></th>
		<td{!! $err('shopcd') !!}>
@if ($shops->count() == 1)
{{ $shops->first()->kaishamei }}
<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $shops->first()->cd }}">
@else
			<select name="tb_shop_cd" id="tb_shop_cd">
			<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected($shopCd === (int) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif
		{!! $errText('shopcd') !!}
		</td>
	</tr>

	<tr>
		<th><label for="name">名前<em>※</em></label></th>
		<td{!! $err('name') !!}>
@if ($isMember)
		{{ $customer?->name }}
		<input type="hidden" name="name" id="name" value="{{ $customer?->name }}" />
@else
		<input type="text" name="name" id="name" class="inputL" value="{{ $data['name'] }}" />
@endif
		{!! $errText('name') !!}
		</td>
	</tr>

	<tr>
		<th><label for="tel">{{ $isConfirm ? '電話番号' : '携帯電話番号' }}<em>※</em></label></th>
		<td{!! $err('tel') !!}>
			<input type="text" name="tel" id="tel" class="inputL" value="{{ $data['tel_display'] ?? $data['tel'] }}" style="ime-mode: disabled;" />
			{!! $errText('tel') !!}
		</td>
	</tr>

	<tr>
		<th><label for="email">メールアドレス<em>※</em></label></th>
		<td{!! $err('email') !!}>
@if ($isMember)
		{{ $canSeeMail ? $customer?->email1 : '**********' }}
		<input type="hidden" name="email" id="email" value="{{ $customer?->email1 }}" />
@else
		<input type="text" name="email" id="email" class="inputL" value="{{ $data['email'] }}" style="ime-mode: disabled;" />
@endif
		{!! $errText('email') !!}
		</td>
	</tr>

	<tr>
		<th><label for="hiduke">予約年月日<em>※</em></label></th>
		<td{!! $err('hiduke') !!}><input type="text" name="hiduke" id="hiduke" class="inputM" maxlength="10" style="ime-mode:disabled;" onclick="format1(this.id);" value="{{ $data['hiduke'] }}" />(例: 2009/12/01)
		{!! $errText('hiduke') !!}
		</td>
	</tr>

	<tr>
		<th>時間<em>※</em></th>
		<td>
			<select name="hour1" id="hour1">
@for ($i = 0; $i < 24; $i++)
@php $dispHour = sprintf('%02d', $i); @endphp
			<option value="{{ $dispHour }}" @selected($data['hour1'] === $dispHour)>{{ $dispHour }}</option>
@endfor
			</select>時
			<select name="min1" id="min1">
@for ($i = 0; $i < 6; $i++)
@php $dispMin = $i == 0 ? '00' : (string) ($i * 10); @endphp
			<option value="{{ $dispMin }}" @selected($data['min1'] === $dispMin)>{{ $dispMin }}</option>
@endfor
			</select>分
		</td>
	</tr>

@if ($ninsuFlg)
	<tr>
		<th><label for="ninsuu">予約人数</label></th>
		<td{!! $err('ninsuu') !!}><input type="text" name="ninsuu" id="ninsuu" class="inputS" maxlength="3" style="ime-mode:disabled;" value="{{ $data['ninsuu'] }}" />人
		{!! $errText('ninsuu') !!}
		</td>
	</tr>
@endif

	<tr id="category_disp" @if ($categories->isEmpty()) style="display:none" @endif>
		<th><label for="category_cd">予約内容</label></th>
		<td>
			<select name="category_cd" id="category_cd">
			<option value="">選択して下さい</option>
{{-- reserve_cate_name se guarda con entidades (get_form2): sin escapar, como el legacy --}}
@foreach ($categories as $category)
<option value="{{ $category->cd }}" @selected($data['category_cd'] === (string) $category->cd)>{!! $category->reserve_cate_name !!}</option>
@endforeach
			</select>
		</td>
	</tr>

@if ($shimeiFlg)
	<tr>
		<th><label for="staff_cd">指名</label></th>
		<td>
			<select name="staff_cd" id="staff_cd">
@if ($staffList->isEmpty())
<option value="">登録されていません</option>
@else
<option value="">選択して下さい</option>
@foreach ($staffList as $member)
<option value="{{ $member->cd }}" @selected($data['staff_cd'] === (string) $member->cd)>{{ $member->name }}</option>
@endforeach
@endif
			</select>
		</td>
	</tr>
@endif

</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="act" id="act" value="{{ $act }}">
<input type="hidden" name="reserve_cd" id="reserve_cd" value="{{ $data['reserve_cd'] }}">
<input type="submit" name="enter" id="enter" class="btn" value="この内容で予約する" />
</div>

</form>

<!-- / .contents --></div>
@endsection

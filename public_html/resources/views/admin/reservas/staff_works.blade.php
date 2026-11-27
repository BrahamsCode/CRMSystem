{{--
    Legacy: system/category9/yoyaku/staff_works.php (スタッフ施術管理, ventana emergente sin navegación).
    Variables:
      $isMaster  bool       $_SESSION['rank'] == 'master' (muestra el <select> de tiendas).
      $shopCd    ?int       tienda de la pantalla (`shop` si es master, si no la de la sesión).
      $staffCd   ?string    `staff` de la petición (empleado a seleccionar al cargar).
      $shops     Collection tiendas para make_shop_options() (cd, kaishamei); vacía si no es master.
      $shopName  ?string    getShopNameFromCd() de la tienda de sesión (no master).
      $staffWord string     manageWord('スタッフ', 'c9_yoyaku[スタッフ]').
      $saved     bool       recién guardado (act=reg del legacy): mensaje 設定しました。 y recarga de la ventana de origen.
    AJAX: el personal se carga de /system/category9/yoyaku/ajax_staff.php (compartido, ruta legacy pendiente; en el
    legacy era relativa) y los menús de route('admin.reservas.staff-works.menu') (antes ajax_menu.php).
--}}
@extends('layouts.legacy-popup')

@section('title', 'スタッフ施術管理')

@section('css')
<style type="text/css">

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

</style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac2").accordion('option', 'active', 0);

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#shop").change(function(){
		shop_cd = $("#shop").val();
		searchStaff(shop_cd);
	});

	$("#staff").change(function(){
		searchMenu();
	});

@if (filled($shopCd))
	setStaff('{{ $shopCd }}', '{{ $staffCd }}');
@elseif (! $isMaster)
	searchStaff('{{ $shopCd }}');
@endif

@if ($saved)
	// El legacy hacía parent.location.reload() también fuera del popup (parent === window → bucle): aquí solo si hay otra ventana
	if ( window.opener ) {
		window.opener.location.reload();
	} else if ( window.parent && window.parent !== window ) {
		parent.location.reload();
	}
@endif

});

function searchStaff(shopCD) {
	if (shopCD=='') {
		$("#staff").html('<option value="">店舗を選択して下さい</option>');
		return;
	} else {
		$("#staff").html('<option value="">Loading...</option>');
		$("#staff").load("/system/category9/yoyaku/ajax_staff.php",{
			shop_cd:shopCD
		});
	}
}

function searchMenu() {
		shopCD = $("#shop").val();
		staffCd = $("#staff").val();
		if (shopCD=='' || staffCd=='') {
			return;
		} else {
			$("#menu").load("{{ route('admin.reservas.staff-works.menu') }}?t={{ now()->timestamp }}",{
				shop_cd:shopCD,
				staff_cd:staffCd,
				_token:"{{ csrf_token() }}"
			});
		}
}

function setStaff(shopCD, staffCD) {
		$("#shop").val(shopCD).change();
		$("#staff").val(staffCD).change();
@if ($saved)
		$("#mess").html('設定しました。').show();
		setTimeout('hideMess()', 2000);
@endif
}

function hideMess() {
		$("#mess").hide().html('');
}
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">


<form action="{{ route('admin.reservas.staff-works.update') }}" method="post" name="shop_select" class="form">
@csrf
<table class="tableBox">
	<tr>
		<th style="width:25%">{{ $staffWord }}を選択</th>
		<td>
@if (! $isMaster)
			{{ $shopName }}<input type="hidden" name="shop" id="shop" value="{{ $shopCd }}" />
@else
			<select name="shop" id="shop">
			<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}" >{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif
			&emsp;&emsp;
			<select name="staff" id="staff">
				<option value="">店舗を選択して下さい</option>
			</select>
		</td>
	</tr>
</table>

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>

<div id="menu" style="text-align:center;">

</div>


</form>

<!-- / .contents --></div>
@endsection

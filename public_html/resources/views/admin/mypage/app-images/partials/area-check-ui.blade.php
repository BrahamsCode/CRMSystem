{{--
    Árbol de áreas con sus tiendas (legacy Area::createAreaCheckUi() con is_coverflow = true). Recursivo.
    Recibe: $areas (nodos de $areaTree), $level (1 = raíz), $areaShops, $checkKey, $slot,
            $checkedFlags ([shop_cd => bool], ya vacío si el hueco no tiene contenido), $disabled (bool).
    {!! !!}: solo para los &emsp; de sangría que el legacy construye a propósito (str_repeat fijo, sin datos).
--}}
@foreach ($areas as $area)
	<div class="areaCheckWrap" data-area-code="{{ $area['cd'] }}">
		{!! str_repeat('&emsp;', $level - 1) !!}{{ $level > 1 ? '└ ' : '' }}
			<a class="areaBtn" data-area-code="{{ $area['cd'] }}" style="color: #336699; cursor: pointer;">
				{{ $area['name'] }}
			</a>
			<br>
			@foreach ($areaShops as $areaShop)
				@if ((string) $areaShop['areaCd'] === (string) $area['cd'])
					<div class="areaShopLabel"><label for="area_check_btn_{{ $slot['index'] }}_{{ $areaShop['cd'] }}">{!! str_repeat('&emsp;', $level - 1) !!}<input type="checkbox" id="area_check_btn_{{ $slot['index'] }}_{{ $areaShop['cd'] }}" name="mypage_config[{{ $checkKey }}][{{ $slot['index'] }}][{{ $areaShop['cd'] }}]" value="{{ $areaShop['cd'] }}" @if (! empty($checkedFlags[$areaShop['cd']])) checked @endif @if ($disabled) disabled="disabled" @endif />{{ $areaShop['kaishamei'] }}@if ($areaShop['isClosed']) (閉店)@endif</label></div>
				@endif
			@endforeach
			@if (! empty($area['children']))
				@include('admin.mypage.app-images.partials.area-check-ui', ['areas' => $area['children'], 'level' => $level + 1])
			@endif
	</div>
@endforeach

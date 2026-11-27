{{--
    Recuadro 「表示店舗」 de un hueco de cover/banner, como lo imprime el legacy en la pantalla propia de
    la sección (mobile_mycolle_edit_coverflow.php / _banner.php): checkbox 全店舗 (índice 0) + árbol de
    áreas de Area::createAreaCheckUi() + tiendas sin área.
    Recibe: $checkKey ('cover'|'banner'), $slot, $filled (el legacy: !empty($dispImage)).
    Las marcas solo se pintan si el hueco tiene contenido ($filled), igual que el legacy.
--}}
<div style="margin-top: 5px; border: 1px solid #E2E2E2; padding: 2px; font-size: 12px; margin-bottom: 5px;">
	<small style="font-size: 11px;">表示店舗</small><br>
	<label for="mypage_config_{{ $checkKey }}_{{ $slot['index'] }}_0" style="display:inline-block;float: left; margin-top: -3px;">
		<input type="checkbox" name="mypage_config[{{ $checkKey }}][{{ $slot['index'] }}][0]" id="mypage_config_{{ $checkKey }}_{{ $slot['index'] }}_0" value="1" @if ($filled && $slot['allShops']) checked='checked' @endif>
	</label>

	@include('admin.mypage.app-images.partials.area-check-ui', [
		'areas' => $areaTree,
		'level' => 1,
		'checkedFlags' => $filled ? $slot['shopFlags'] : [],
		'disabled' => $filled && $slot['allShops'],
	])

	{{-- Tiendas sin área válida (legacy: .areaNonShopLabel; nunca llevan disabled ni 「(閉店)」) --}}
	@foreach ($areaShops as $areaShop)
		@if ($areaShop['withoutArea'])
			<div class="areaNonShopLabel"><label for="area_check_btn_{{ $slot['index'] }}_{{ $areaShop['cd'] }}"><input type="checkbox" id="area_check_btn_{{ $slot['index'] }}_{{ $areaShop['cd'] }}" name="mypage_config[{{ $checkKey }}][{{ $slot['index'] }}][{{ $areaShop['cd'] }}]" value="{{ $areaShop['cd'] }}" @if ($filled && ! empty($slot['shopFlags'][$areaShop['cd']])) checked @endif />{{ $areaShop['kaishamei'] }}</label></div>
		@endif
	@endforeach

</div>

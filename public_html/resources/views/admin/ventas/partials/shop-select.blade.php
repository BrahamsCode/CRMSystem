{{--
    Selector de tienda de master (make_shop_options($shop_cd, false) del legacy): se envía al cambiar.
    Datos: $action (URL de la pantalla), $shops (Collection<Shop>), $shopCd (int|null)
    Para no master el legacy imprime solo «&nbsp;<br />».
--}}
@if ($isMaster)
<form action="{{ $action }}" method="get" name="shop_select">
<select name="shop" id="shop" style="margin-bottom: 10px; padding: 1px; border: 1px solid #7F9DB9;">
<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected((string) $shopCd === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>&emsp;
※登録・閲覧したい店舗を選択してください。
</form>
@else
&nbsp;<br />
@endif

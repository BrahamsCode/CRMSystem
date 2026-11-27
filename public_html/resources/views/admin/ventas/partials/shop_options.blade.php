{{-- Legacy: make_shop_options($val, $zen_tenpo_option) de system/inc/kihon.php.
     Variables: $shops (Collection<Shop> cd/kaishamei, orden cd), $selected (valor elegido o null),
                $allShops (bool, opcional: añade 全店舗 = -1). --}}
<option value="">▼店舗選択</option>
@if ($allShops ?? false)
<option value="-1" @if ((string) $selected === '-1') selected="selected" @endif>全店舗</option>
@endif
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @if ((string) $selected === (string) $shop->cd) selected="selected" @endif>{{ $shop->kaishamei }}</option>
@endforeach

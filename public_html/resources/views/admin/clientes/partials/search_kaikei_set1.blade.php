{{-- Legacy: system/inc/search_kaikei_set1.php (会計検索条件テーブル, 2011.08.26).
     Variables:
       $kaikeiResource  system_name de tb_base_system_grandchild ('ポイント管理' o 'スタンプ会計処理', el primero activo por cd); null = no se muestra
       $minYear         año del primer来店 (ver partials/search_rireki_set1)
       $shops           tiendas tb_shop (disp_flg <> '9', orden cd): 'cd', 'kaishamei'
       $filters, $hiddenSearchMenus, $cellTag
--}}
@if (! empty($kaikeiResource))
@php
    $cellTag = $cellTag ?? 'th';
    $hiddenSearchMenus = $hiddenSearchMenus ?? [];
    $filters = $filters ?? [];
    $v = fn (string $key) => old($key, $filters[$key] ?? null);
    $thisYear = now('Asia/Tokyo')->year;
    $minYear = (int) ($minYear ?? $thisYear);
@endphp
<fieldset class="MT10">
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array('kaikei', $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />会計</legend>
<div class="searchDiv" menu="kaikei"@if (in_array('kaikei', $hiddenSearchMenus)) style="display:none;"@endif>
<table class="tableBox9">

	<tr>
		<{{ $cellTag }}>
			処理店舗<br />
			<select name="search_kaikei_shop_cd" id="search_kaikei_shop_cd">
			<option value="">&nbsp;</option>
			@foreach ($shops as $shop)
<option value="{{ $shop['cd'] }}"@if ($v('search_kaikei_shop_cd') == $shop['cd']) selected @endif>{{ $shop['kaishamei'] }}</option>
			@endforeach
			</select>
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			<span>来店期間</span><br />
			@foreach (['start' => 'から<br />', 'end' => 'まで'] as $edge => $suffix)
			<select name="search_kaikei_{{ $edge }}_year"><option value="" >&nbsp;</option>
@for ($y = $thisYear; $y >= $minYear; $y--)
<option value="{{ $y }}" @selected($v('search_kaikei_' . $edge . '_year') == $y)>{{ $y }}</option>
@endfor
</select>&nbsp;年&nbsp;
			<select name="search_kaikei_{{ $edge }}_month"><option value="" >&nbsp;</option>
@for ($m = 1; $m <= 12; $m++)
<option value="{{ sprintf('%02d', $m) }}" @selected($v('search_kaikei_' . $edge . '_month') == sprintf('%02d', $m))>{{ sprintf('%02d', $m) }}</option>
@endfor
</select>&nbsp;月&nbsp;
			<select name="search_kaikei_{{ $edge }}_day"><option value="" >&nbsp;</option>
@for ($d = 1; $d <= 31; $d++)
<option value="{{ sprintf('%02d', $d) }}" @selected($v('search_kaikei_' . $edge . '_day') == sprintf('%02d', $d))>{{ sprintf('%02d', $d) }}</option>
@endfor
</select>&nbsp;日&nbsp;
			{{-- 開始は 00〜23、終了は 00〜24 (como el legacy) --}}
			<select name="search_kaikei_{{ $edge }}_time"><option value="" >&nbsp;</option>
@for ($h = 0; $h <= ($edge === 'start' ? 23 : 24); $h++)
<option value="{{ sprintf('%02d', $h) }}"@if ((string) $v('search_kaikei_' . $edge . '_time') === sprintf('%02d', $h)) selected @endif>{{ sprintf('%02d', $h) }}</option>
@endfor
</select>&nbsp;時&nbsp;{!! $suffix !!}
			@endforeach
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			<span>平均会計金額</span><br />
			<input type="text" name="search_kaikei_kingaku1" size="15" maxlength="15" value="{{ $v('search_kaikei_kingaku1') }}" style="ime-mode:disabled;" />&nbsp;～&nbsp;
			<input type="text" name="search_kaikei_kingaku2" size="15" maxlength="15" value="{{ $v('search_kaikei_kingaku2') }}" style="ime-mode:disabled;" />&nbsp;円
			<input type="hidden" name="search_kaikei_resource" value="{{ $kaikeiResource }}" />
		</{{ $cellTag }}>
	</tr>
</table>
</div>
</fieldset>
@endif

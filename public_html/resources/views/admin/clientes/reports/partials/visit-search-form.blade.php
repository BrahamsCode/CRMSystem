{{-- Legacy: formulario GET común de system/category1/shukei/model1.php, model2.php, point1.php y stamp1.php
     (集計タイプ, 登録店舗, 来店履歴: 来店店舗 y 来店期間).
     Variables: $in (array, parámetros GET), $shops (cd => kaishamei), $isMaster, $staffShopCd, $staffShopName,
       $selfUrl (action: la misma URL), $years (años del selector, del actual al primer 来店),
       $formCd (?int: hidden f de model1/model2; null en point1/stamp1) --}}
@php
    $types = \App\Services\Customers\Reports\CustomerReportService::TYPES;
    $cond = (string) ($in['cond'] ?? '');
    // El legacy comparaba con «==» (01 == 1)
    $sel = fn (string $name, int|string $value) => (string) ($in[$name] ?? '') !== '' && (int) $in[$name] === (int) $value ? ' selected' : '';
    $pad = fn (int $i) => sprintf('%02d', $i);
@endphp
<form method="get" name="form_search" id="form_search" action="{{ $selfUrl }}">
	<table class="tableBox" style="margin:0;width:500px;">
	<tr>
	<th>集計タイプ</th>
	<td>
		<select name="cond" id="cond">
			<option value="">&nbsp;</option>
			@foreach ($types as $i => $type)
			<option value="{{ $i }}"@if ($cond !== '' && (int) $cond === $i) selected @endif>{{ $type }}別</option>
			@endforeach
		</select>
	</td>
	</tr>

	<tr>
	<th>登録店舗</th>
	<td>
		@if ($isMaster)
		<select name="touroku_shop_cd" @if (count($shops) < 2) class="displayNone" @endif>
		<option value="">▼店舗選択</option>
		@foreach ($shops as $cd => $name)
		<option value="{{ $cd }}"{{ $sel('touroku_shop_cd', $cd) }}>{{ $name }}</option>
		@endforeach
		</select>
		@else
		{{ $staffShopName }}
		<input type="hidden" name="touroku_shop_cd" value="{{ $staffShopCd }}">
		@endif
	</td>
	</tr>

	<tr>
	<th>来店履歴</th>
	<td>
		来店店舗：<br/>
		&emsp;&emsp;<select name="shop_cd" @if (count($shops) < 2) class="displayNone" @endif>
		<option value="">▼店舗選択</option>
		@foreach ($shops as $cd => $name)
		<option value="{{ $cd }}"{{ $sel('shop_cd', $cd) }}>{{ $name }}</option>
		@endforeach
		</select>
		<br/>

		来店期間：<br/>
		@foreach (['start' => 'から', 'end' => 'まで'] as $side => $suffix)
		&emsp;&emsp;<select name="rireki_{{ $side }}_year"><option value="">&nbsp;</option>@foreach ($years as $y)<option value="{{ $y }}"{{ $sel("rireki_{$side}_year", $y) }}>{{ $y }}</option>@endforeach</select>&nbsp;年&nbsp;
		<select name="rireki_{{ $side }}_month"><option value="">&nbsp;</option>@for ($i = 1; $i <= 12; $i++)<option value="{{ $pad($i) }}"{{ $sel("rireki_{$side}_month", $i) }}>{{ $pad($i) }}</option>@endfor</select>&nbsp;月&nbsp;
		<select name="rireki_{{ $side }}_day"><option value="">&nbsp;</option>@for ($i = 1; $i <= 31; $i++)<option value="{{ $pad($i) }}"{{ $sel("rireki_{$side}_day", $i) }}>{{ $pad($i) }}</option>@endfor</select>&nbsp;日&nbsp;
		<select name="rireki_{{ $side }}_time"><option value="">&nbsp;</option>@for ($i = 0; $i < 24; $i++)<option value="{{ $pad($i) }}"{{ $sel("rireki_{$side}_time", $i) }}>{{ $pad($i) }}</option>@endfor</select>&nbsp;時&nbsp;{{ $suffix }}
		@if ($side === 'start')<br />@endif

		@endforeach
	</td>
	</tr>

	<tr>
	<td colspan="2" style="text-align:center;">
		<input type="submit" value="　　表示　　" />
	</td>
	</tr>
	</table>
@if ($formCd !== null)
<input type="hidden" name="f" value="{{ $formCd }}" />
@endif
</form>

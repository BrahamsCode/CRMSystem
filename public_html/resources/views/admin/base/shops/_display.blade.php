{{-- Tabla de solo lectura de una tienda (shop_detail / shop_new_confirm / shop_new_complete).
     $variant: detail | confirm | complete. $d = columnas de tb_shop (o datos del formulario), $areaName, $categories, $admin, $extShop --}}
@php
    $hide = $extShop ? " style='display: none'" : '';
    $isNew = (int) ($d['is_new_store'] ?? 0) === 1;
    $start = (string) ($d['new_store_start_date'] ?? '');
    $end = (string) ($d['new_store_end_date'] ?? '');
    $category = isset($d['shop_category_cd'], $categories[$d['shop_category_cd']]) ? $categories[$d['shop_category_cd']] : '';
    $beacon = fn ($v) => (int) $v > 0 ? ((int) $v / 100) . 'm以内' : '至近距離';
@endphp
<fieldset>
<legend>{{ $variant === 'detail' ? '店舗・営業所 情報' : '店舗情報' }}</legend>
<table class="tableBox">
	@if ($variant === 'detail')
	<tr>
		<th style="width: 210px;"><label for="kaishamei_kana"><small>会社名/屋号(カナ)</small></label></th>
		<td colspan="3"><small>{{ $d['kaishamei_kana'] ?? '' }}</small></td>
	</tr>
	<tr>
		<th><label for="kaishamei">会社名/屋号</label></th>
		<td colspan="3">{{ $d['kaishamei'] ?? '' }}</td>
	</tr>
	@else
	<tr>
		<th style="width: 210px;">会社名/屋号</th>
		<td colspan="3">{{ $d['kaishamei'] ?? '' }}</td>
	</tr>
	<tr>
		<th><small>会社名/屋号（カナ）</small></th>
		<td colspan="3"><small>{{ $d['kaishamei_kana'] ?? '' }}</small></td>
	</tr>
	@endif
	<tr>
		<th><label for="zip">@if ($variant === 'detail')<small>店舗グループ</small>@else店舗グループ@endif</label></th>
		<td colspan="3">@if ($variant === 'detail')<small>{{ $category }}</small>@else{{ $category }}@endif</td>
	</tr>
	<tr>
		<th><label for="zip">エリア情報</label></th>
		<td colspan="3">{{ $areaName ?? ($variant === 'confirm' ? '' : '---') }}</td>
	</tr>
	<tr>
		<th><label for="zip">ステイタス</label></th>
		<td colspan="3">
			<div style="display: inline-block; margin-right: 20px;">
				@if ((int) ($d['is_closed'] ?? 0) === 1)
					閉店済み
				@else
					営業中
				@endif
			</div>
			新店フラグ : {{ $isNew ? 'あり' : 'なし' }}
			@if ($isNew)
				新店期間 :
				@if ($start !== '' && $end !== '')
					{{ $start }} から {{ $end }} まで
				@elseif ($start !== '')
					{{ $start }} から
				@elseif ($end !== '')
					 {{ $end }} まで
				@else
					設定無し
				@endif
			@endif
		</td>
	</tr>
	<tr>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="3">{{ $d['zip'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="pref">都道府県</label></th>
		<td colspan="3">{{ $d['pref'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="add_kana"><small>市区町村(カナ)</small></label></th>
		<td colspan="3"><small>{{ $d['add_kana'] ?? '' }}</small></td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="3">{{ $d['add'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="add_number">番地</label></th>
		<td colspan="3">{{ $d['add_number'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>マンション・ビル名(カナ)</small></label></th>
		<td colspan="3"><small>{{ $d['add_build_kana'] ?? '' }}</small></td>
	</tr>
	<tr>
		<th><label for="add_build">マンション・ビル名</label></th>
		<td colspan="3">{{ $d['add_build'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="tel">電話番号</label></th>
		<td>{{ $d['tel'] ?? '' }}</td>

		<th><label for="fax">{{ $variant === 'detail' ? 'ファックス番号' : 'ファックス' }}</label></th>
		<td>{{ $d['fax'] ?? '' }}</td>
	</tr>
	@if ((string) $admin->application_use_flg === '1')
	<tr>
		<th><label for="mycolle_tel">マイコレ検索用電話番号</label></th>
		<td colspan="3">{{ $d['mycolle_tel'] ?? '' }}</td>
	</tr>
	@endif
	<tr>
		<th><label for="shop_url">店舗ホームページアドレス</label></th>
		<td colspan="3">{{ $d['shop_url'] ?? '' }}</td>
	</tr>
	<tr>
		<th><label for="email">お問い合わせ用メールアドレス</label></th>
		<td colspan="3">{{ $d['email'] ?? '' }}</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="tantou_kana"><small>責任者(カナ)</small></label></th>
		<td colspan="3"><small>{{ $d['tantou_kana'] ?? '' }}</small></td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="tantou">責任者</label></th>
		<td colspan="3">{{ $d['tantou'] ?? '' }}</td>
	</tr>
	<tr{!! $hide !!}>
	<th><label for="kyuyo_marume">給与丸め</label></th>
	<td colspan="3">{{ $d['kyuyo_marume'] ?? '' }}分</td>
	</tr>
	<tr{!! $hide !!}>
	 <th>始業時間</th>
	 @if ($variant === 'detail')
	 <td>{{ ($d['start_work_time1'] ?? '') !== '' && $d['start_work_time1'] !== '0' ? $d['start_work_time1'] . '時 ' : '' }}{{ ($d['start_work_time2'] ?? '') !== '' && $d['start_work_time2'] !== '0' ? $d['start_work_time2'] . '分' : '' }}</td>
	 @else
	 <td>{{ $d['start_work_time1'] ?? '' }}時&nbsp;{{ $d['start_work_time2'] ?? '' }}分</td>
	 @endif

	 <th>休憩時間</th>
	 <td>{{ ($d['break_time'] ?? '') !== '' ? $d['break_time'] . '分' : '' }}</td>
	</tr>
	<tr{!! $hide !!}>
		<th>仕入れID</th>
		<td>{{ $d['shiire_id'] ?? '' }}</td>
		<th>仕入れパスワード</th>
		<td>{{ $d['shiire_pass'] ?? '' }}</td>
	</tr>
	<tr{!! $hide !!}>
		<th>緯度</th>
		<td>{{ $d['latitude'] ?? '' }}</td>
		<th>経度</th>
		<td>{{ $d['longitude'] ?? '' }}</td>
	</tr>
	@if ((string) $admin->application_use_flg === '1' && (string) $admin->beacon_use_flg === '1')
	<tr>
		<th><label for="beacon_visiting_area">来店用ビーコン距離</label></th>
		<td>{{ $beacon($d['beacon_visiting_area'] ?? 0) }}</td>
		<th><label for="beacon_accounting_area">会計用ビーコン距離</label></th>
		<td>{{ $beacon($d['beacon_accounting_area'] ?? 0) }}</td>
	</tr>
	@endif
</table>
</fieldset>

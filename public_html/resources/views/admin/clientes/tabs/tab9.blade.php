{{-- Legacy: system/category1/inc/detail_tab9.php (pestaña カルテ / 関連情報 de la ficha; parcial sin @extends).
     El legacy hace exit si admin_master.related_features no tiene categoría ($relatedFeaturesConfig[2] vacío):
     la ficha no debe incluir esta pestaña en ese caso.
     Variables:
       $customer               (modelo tb_customer: cd, id, name)
       $relatedFeaturesConfig  (array: explode('<>', admin_master.related_features); [1] = '1' ポイント / '2' スタンプ, [2] = tb_search_cate.cd)
       $searchCateData         (tb_search_cate de $relatedFeaturesConfig[2]: cd, name, disp_column, disp_flg; null si no existe)
       $karuteLabel            (string: manageWord('カルテ', 'c1_customer_detail[カルテ]'))
       $packName, $packTab     (string|null, int|null: 'ポイント'/8 o 'スタンプ'/10 si el paquete está activo en tb_base_system_*; null si no)
       $relatedFeature         (fila tb_raiten_rireki seleccionada (?related_features_cd o la última): cd, shop_cd, raiten_time; null si no hay)
       $relatedLog             (fila tb_point_log o tb_stamp_log enlazada a $relatedFeature + staff_name; null si no hay)
       $relatedFields          (campos de la categoría para $relatedFeature, ver tabs/_custom-fields: name, form_type, unit, comment, html)
       $shopList               (array cd => kaishamei de tb_shop)
       $timeline               (LengthAwarePaginator, 15 por página: filas del UNION legacy con claves cd, shori_time, tb_shop_cd,
                                data1, data2, data3, data4, cate ('raiten'|'point'|'stamp'|'karute'))
     JS usados de la ficha: send_tab(), submitChk(). --}}
@php
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
    // Fecha epoch del legacy → Carbon en hora de Japón (null si no hay fecha: no se muestra 1970)
    $toDate = fn ($epoch) => ($epoch !== null && $epoch !== '' && is_numeric($epoch)) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $epoch, 'Asia/Tokyo') : null;
    $packType = $relatedFeaturesConfig[1] ?? null;
@endphp
@if (! $searchCateData || $searchCateData['disp_flg'] != '1')
カルテ：設定されていないか非表示になっています。
@else

<style type="text/css"><!--

.genzaiPoint {
	color: #EE0000;
	font-weight: bold;
	font-size: 18px;
}

.kyouchouRed {
	color: #EE0000;
	font-weight: bold;
	font-size: 14px;
}

.kyouchouBlue {
	color: #336699;
	font-weight: bold;
	font-size: 14px;
}

--></style>


<div class="tabBody">

	<div style="text-align:right">
		<a class="btnStyle" onclick="send_tab('4',''); return false;">来店履歴のみを表示</a>
	@if ($packName && $packType != '')
		<a class="btnStyle" onclick="send_tab('{{ $packTab }}',''); return false;">{{ $packName }}のみを表示</a>
	@endif
		<a class="btnStyle" onclick="send_tab('custom','{{ $searchCateData['cd'] }}'); return false;">{{ $searchCateData['name'] }}のみを表示</a>
	</div>



	<div class="innerBody">
	<div class="innerTabLeft">

	<fieldset style="border: 0;">

	@if (empty($relatedFeature['cd']))
		<p style="margin-left: 5px; font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>{{ $karuteLabel }}データがありません。<br/><br/></p>

	@else

	<div class="btnBox">
	<input type="button" class="btnStyleM" value="{{ $karuteLabel }}を追加" onclick="document.add_record_form.submit();" />
	<input type="button" class="btnStyleM" value="{{ $karuteLabel }}を修正" onclick="document.update_record_form.submit();" />
	<input type="button" class="btnStyleM" value="{{ $karuteLabel }}を削除" onclick="submitChk(document.delete_record_form);" />
	</div>
	<div style="font-size: 10px;">※「{{ $karuteLabel }}を削除」した場合は、<span style="font-weight: bold; font-size: 12px;">来店情報の削除・カルテ情報の削除{{ $packName ? '・' . $packName . '情報の操作履歴の削除(現在の' . $packName . 'は変動しません)' : '' }}</span> が行われます。</div>

		<div class="tabBody2">
			<p class="h2Title" style="margin-bottom: 0;">■顧客情報</p>
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
					<th><label for="user_id">処理店舗</label></th>
					<td>{{ $shopList[$relatedFeature['shop_cd']] ?? '' }}</td>
				</tr>

			</table>
		</div> <!-- tabBody2 -->
		<br/>

		@php $raitenTime = $toDate($relatedFeature['raiten_time']); @endphp
		<div class="tabBody2">
			<p class="h2Title" style="margin-bottom: 0;">■来店履歴情報</p>
			<table class="tableBox">
				<tr>
					<th>来店日時</th>
					<td>{{ $raitenTime?->format('Y/m/d H:i') }}&nbsp;({{ $raitenTime ? $youbi[$raitenTime->dayOfWeek] : '' }})</td>
				</tr>
			</table>

		</div> <!-- tabBody2 -->
		<br/>


		{{-- ★★★★★ スタンプ・ポイント管理 ★★★★★ --}}
		@if ($relatedLog && ($packType == '1' || $packType == '2'))
		@php
			$logBairitsu = '';
			if ($packType == '1') {
				// ポイントの場合
				$logType = 'ポイント';
				$logUnit = 'Pt';
				$logRateUnit = '%';
				$jidouUnits = $relatedLog['jidou_point'];
				$sounyuUnits = $relatedLog['sounyu_point'];
				$pastUnits = (int) $relatedLog['shori_mae_point']; // 処理前ポイント数
			} else {
				// スタンプの場合
				$logType = 'スタンプ';
				$logUnit = '個';
				$logRateUnit = '円で1個';
				$jidouUnits = $relatedLog['jidou_stamp'];
				$tempDiv = ($relatedLog['percentage'] > 0) ? ((int) ($relatedLog['kaikei'] / $relatedLog['percentage'])) : 0;
				if ($tempDiv > 0) {
					$logBairitsu = '(' . (int) ($jidouUnits / $tempDiv) . '倍率)';
				}
				$sounyuUnits = $relatedLog['sounyu_stamp'];
				$pastUnits = (int) $relatedLog['shori_mae_stamp']; // 処理前スタンプ数
			}
			$shoriLabel = ((int) $sounyuUnits >= 0) ? '追加' : '削減';
			$shoriColor = ((int) $sounyuUnits >= 0) ? 'kyouchouBlue' : 'kyouchouRed';
		@endphp
		<div class="tabBody2">
			<p class="h2Title" style="margin-bottom: 0;">■{{ $logType }}情報</p>
			<table class="tableBox">

				<tr>
					<th style="width: auto;">処理日</th>
					<th style="width: auto;">処理内容</th>

					<th style="width: auto;">処理詳細</th>
				</tr>

				<tr>
					<td>{{ $toDate($relatedLog['touroku_date'])?->format('Y/m/d H:i') }}</td>
					<td>

						<table>

						<tr>
						<th style="width: auto;">会計</th>
						<td><span class="kyouchouBlue">{{ number_format((int) $relatedLog['kaikei']) }}</span> 円</td>
						</tr>

						@if ($packType == '1')
						<tr>
						<th style="width: auto;">{{ $logType }}</th>
						<td><span class="kyouchouRed">{{ $relatedLog['shouhi_point'] }}</span> {{ $logUnit }} 使用</td>
						</tr>

						<tr>
						<th style="width: auto;">差引会計</th>
						<td><span class="kyouchouBlue">{{ number_format((int) $relatedLog['sashihiki_kaikei']) }}</span> 円</td>
						</tr>
						@endif

						</table>


						自動計算で <span class="kyouchouBlue">{{ $jidouUnits }}</span> {{ $logUnit }} 追加<br />
						{{ $shoriLabel }}処理で <span class="{{ $shoriColor }}">{{ abs((int) $sounyuUnits) }}</span> {{ $logUnit }} {{ $shoriLabel }}

					</td>

					<td>
						<table>

						<tr>
						<th style="width: auto;">支払方法</th>
						<td>{{ ((float) $relatedLog['shiharai_houhou'] == 2) ? 'カード' : '現金' }}</td>
						</tr>

						<tr>
						<th style="width: auto;">還元率</th>
						<td>{{ $relatedLog['percentage'] . $logRateUnit }}@if ($logBairitsu !== '')<br/>{{ $logBairitsu }}@endif</td>
						</tr>

						<tr>
						<th style="width: auto;">処理前{{ $logType }}</th>
						<td>{{ number_format($pastUnits) . $logUnit }}</td>
						</tr>

						<tr>
						<th style="width: auto;">明細通知</th>
						<td>{{ ((float) $relatedLog['meisai_mail'] == 1) ? '通知済' : '未' }}</td>
						</tr>

						<tr>
						<th style="width: auto;">端末No</th>
						<td>{{ ($relatedLog['tb_terminal_cd'] == '-1') ? '管理画面' : $relatedLog['tb_terminal_cd'] }}</td>
						</tr>

						<tr>
						<th style="width: auto;">処理スタッフ</th>
						<td>{{ $relatedLog['staff_name'] }}</td>
						</tr>

						</table>

					</td>
				</tr>

			</table>
		</div> <!-- tabBody2 -->
		<br />

		@endif

		{{-- ★★★★★ 追加情報（カスタマイズフォーム） ★★★★★ --}}
		<div class="tabBody2">
			<p class="h2Title" style="margin-bottom: 0;">■{{ $searchCateData['name'] }}情報</p>
			<table class="tableBox">
@include('admin.clientes.tabs._custom-fields', ['fields' => $relatedFields, 'baseCnt' => $searchCateData['disp_column'], 'thStyle' => null])
			</table>

		</div> <!-- tabBody2 -->

	@endif




	</fieldset>

	<!-- / div.innerTabLeft END -->
	</div>


	<div class="innerTabRight">
@if ($timeline->count() > 0)
		<p class="h2Title" style="margin-bottom: 0;">■タイムライン</p>

		<div class="form">

		<p><span class="attention">{{ $timeline->firstItem() }}件～{{ $timeline->lastItem() }}件目/{{ $timeline->total() }}件中</span></p>

		<table class="tableBox2">
		<tr>

		<th style="width:150px;">処理日時</th>

		<th>説明</th>
		<th style="width: 50px;"></th>
		</tr>
		@foreach ($timeline as $timelineRow)
		@php
			$trAdds = '';
			if ($timelineRow['cd'] == ($relatedFeature['cd'] ?? null)) {
				$trAdds = ' style="background-color: #FFEEAA;"';
			} elseif ($loop->index % 2 != 0) {
				$trAdds = ' class="tableBg"';
			}
			$shoriTime = $toDate($timelineRow['shori_time']);
			$isSelected = $timelineRow['cate'] == 'raiten' && $timelineRow['cd'] == ($relatedFeature['cd'] ?? null);
		@endphp
		{{-- $trAdds: atributo fijo generado aquí --}}
		<tr{!! $trAdds !!}>
		<td>{{ $shoriTime?->format('Y/m/d H:i') }}&nbsp;({{ $shoriTime ? $youbi[$shoriTime->dayOfWeek] : '' }})&nbsp;{{ $timelineRow['cate'] == 'raiten' ? '来店' : '処理' }}</td>
		<td>
		@switch ($timelineRow['cate'])
			@case ('raiten')
				「{{ $shopList[$timelineRow['tb_shop_cd']] ?? '' }}」の{{ $karuteLabel }}
				@break
			@case ('point')
			@case ('stamp')
				@php
					$unitName = $timelineRow['cate'] == 'point' ? 'ポイント' : 'スタンプ';
					$totalStamp = (int) $timelineRow['data1'] + (int) $timelineRow['data2'] - (int) $timelineRow['data3'];
				@endphp
				@if ($timelineRow['data4'] == '7')
{{ $unitName }}失効にて<span class="kyouchouRed">{{ number_format((int) $timelineRow['data3']) }}</span>{{ $unitName }}失効
				@elseif ($totalStamp >= 0)
<span class="kyouchouBlue">{{ number_format($totalStamp) }}</span>{{ $unitName }}加算
				@else
<span class="kyouchouRed">{{ number_format(abs($totalStamp)) }}</span>{{ $unitName }}削減
				@endif
				@break
			@case ('karute')
				<span class="kyouchouBlue">{{ $karuteLabel }}</span>を追加
				@break
		@endswitch
		</td>
		<td style="text-align: center;">
			@if ($timelineRow['cate'] == 'raiten')
			@if ($isSelected)
			<span style="padding: 2px; border: 1px solid #FF9955; color: #EE6622;">選択中</span>
			@else
			<a class="btnStyle" href="{{ route('admin.clientes.show', [$customer->cd, 'page' => request('page'), 'kensu' => $timeline->perPage(), 'related_features_cd' => $timelineRow['cd'], 'tab' => '9', 'kaiin_cd' => $customer->cd]) }}">詳細</a>
			@endif
			@endif
		</td>
		</tr>
		@endforeach

		</table>

		<!-- / div.form END -->
		</div>

		<ul class="btnBox2">
		@include('admin.clientes.partials.page-control', ['paginator' => $timeline, 'params' => ['tab' => '9', 'kaiin_cd' => $customer->cd]])
		</ul>
@endif
	<!-- / div.innerTabRight END --></div>
	<!-- / div.innerBody END --></div>



	<div class="btnBox">

		<form method="post" name="add_record_form" id="add_record_form" class="form" action="{{ $pendingRoute('admin.clientes.visit-records.create') }}">
		@csrf
		<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $karuteLabel }}を追加" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="9">
		<input type="hidden" name="act" id="act" value="new">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		@if (! empty($relatedFeature['cd']))
		<form method="post" name="update_record_form" id="add_record_form" class="form" action="{{ $pendingRoute('admin.clientes.visit-records.create') }}">
		@csrf
		<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $karuteLabel }}を修正" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="9">
		<input type="hidden" name="act" id="act" value="up">
		<input type="hidden" name="related_features_cd" id="related_features_cd" value="{{ $relatedFeature['cd'] }}">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		<form method="post" name="delete_record_form" id="delete_record_form" class="form" action="{{ $pendingRoute('admin.clientes.visit-records.destroy') }}">
		@csrf
		<input type="button" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $karuteLabel }}を削除" onclick="submitChk(document.delete_record_form);" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="9">
		<input type="hidden" name="related_features_cd" id="related_features_cd" value="{{ $relatedFeature['cd'] }}">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		@endif

	<!-- / div.btnBox END -->
	</div>
</div>

@endif

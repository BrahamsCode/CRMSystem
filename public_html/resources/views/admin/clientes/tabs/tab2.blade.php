{{-- Legacy: system/category1/inc/detail_tab2.php (個人情報 / 法人情報; la incluye tabs/tab1 y también se abre sola con ?tab=2).
     Variables:
       $customer  modelo tb_customer (columnas legacy)
       $showImei  bool: checkImeiItem() → existe el項目 imei en tb_koumoku (system_cate = 1)
     Los campos enmascarados usan $kengen->myKengen[N][1] → @can('kengen', [N, 1]). --}}
<div class="tabBody">

@if ($customer->type != '法人')
	<!--個人情報 start-->
	<div id="kojinOption" style="display: block;">

	<table class="tableBox" style="margin-bottom: 10px;">

		<tr>
			<th style="width:87px;">個人メールアドレス</th>
			<td>@can('kengen', [27, 1]){{ $customer->kojin_mail }}@else**********@endcan</td>
			<th style="width:87px;">携帯電話番号</th>
			<td colspan="3">@can('kengen', [26, 1]){{ $customer->kojin_mobile }}@else**********@endcan</td>
		</tr>
		<tr>
			<th>勤務先 名前</th>
			<td>
			<span title="@can('kengen', [29, 1]){{ $customer->kinmusaki_name_kana }}@else**********@endcan">
			@can('kengen', [30, 1]){{ $customer->kinmusaki_name }}@else**********@endcan</span></td>
			<th>勤務先 業種</th>
			<td colspan="3">@can('kengen', [31, 1]){{ $customer->kinmusaki_gyoushu }}@else*****@endcan</td>
		</tr>
		<tr>
			<th>勤務先 電話番号</th>
			<td>@can('kengen', [32, 1]){{ $customer->kinmusaki_tel }}@else**********@endcan</td>
			<th>勤務先 ファックス番号</th>
			<td colspan="3">@can('kengen', [33, 1]){{ $customer->kinmusaki_fax }}@else**********@endcan</td>
		</tr>
		@if ($showImei ?? false)
		<tr>
			<th>IMEI</th>
			<td colspan="3">@can('kengen', [51, 1]){{ $customer->imei }}@else**********@endcan</td>
		</tr>
		@endif
	</table>

	</div>
	<!--個人情報 end-->

@else

	<!--法人情報 start-->
	<div id="houjinOption" style="display: block;">

	<table class="tableBox" style="margin-bottom: 10px;">
		<tr>
			<th>設立年月日</th>
			<td>{{ $customer->houjin_setsuritsu }}</td>
			<th>資本金</th>
			<td colspan="3">{{ $customer->houjin_shihon }}</td>
		</tr>
		<tr>
			<th>代表者 名前</th>
			<td colspan="3"><span title="{{ $customer->daihyousha_name_kana }}">{{ $customer->daihyousha_name }}</span></td>
		</tr>
		<tr>
			<th>代表者 生年月日</th>
			<td>{{ $customer->daihyousha_birth }}</td>
			<th>代表者 性別</th>
			<td colspan="3">{{ $customer->daihyousha_sex }}</td>
		</tr>
		<tr>
			<th>業種</th>
			<td>{{ $customer->houjin_gyoushu }}</td>
			<th>部署</th>
			<td>{{ $customer->houjin_busho }}</td>
		</tr>
		<tr>
			<th>担当者 名前</th>
			<td colspan="3"><span title="{{ $customer->tantou_name_kana }}">{{ $customer->tantou_name }}</span></td>
		</tr>
		<tr>
			<th>担当者 電話番号</th>
			<td>{{ $customer->tantou_tel }}</td>
			<th>担当者 ファックス番号</th>
			<td>{{ $customer->tantou_fax }}</td>
		</tr>
		<tr>
			<th>担当者 メールアドレス</th>
			<td colspan="3">{{ $customer->tantou_mail }}</td>
		</tr>
	</table>
	</div>
	<!--法人情報 end-->
@endif

</div>

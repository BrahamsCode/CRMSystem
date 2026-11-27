{{-- Legacy: system/category1/inc/detail_tab3.php (販促用情報; la incluye tabs/tab1 y también se abre sola con ?tab=3).
     Variables:
       $customer           modelo tb_customer (columnas legacy)
       $showReminderMail   bool: tb_base_system cd IN (9, 11) con system_flg = 1 (fila 予約リマインダーメール配信有無)
       $customerGroupName  string|null: make_customer_group_hyouji(tb_customer_group_cd) → tb_customer_group.name
       $raitenDoukiName    string|null: make_raiten_douki_hyouji(tb_raiten_douki_master_cd) → tb_raiten_douki_master.name --}}
<!--販促用情報table start-->
<div class="tabBody">
<table class="tableBox">
	<tr>
		<th style="width:87px;">メルマガ配信有無</th>
		<td>@if ($customer->mailmaga_flg == '1')配信する@elseif ($customer->mailmaga_flg == '9')不達配信@else配信しない@endif</td>
		<th style="width:87px;">アドレス区分</th>
		<td>{{ $customer->mailmaga_email_type }}</td>
	</tr>
	<tr>
		<th>不達配信数</th>
		<td>
		<span class="text3L">{{ $customer->error_send_cnt ?: '0' }}</span> 回<br />
		送信エラーが3回になると、配信不能アドレスと認識されて、「不達配信」扱いになります。<br />
		<small>不達配信回数は、ネットワーク状況やキャリア都合にも影響されます。<br />
		正確に配信エラーを表示するものではありません。<br />
		ひとつの目安としてご確認ください。</small>
		</td>
		<th>機種情報</th>
		<td>
		{{ $customer->mobile_kishu }}<br />
		<small>機種情報はお客様による登録時・情報変更時・マイページログイン時に更新されます。</small>

		<div style="margin-top: 5px;">かんたんログイン ： <strong>
		@if ($customer->easy_login)設定済み@else未設定@endif</strong>
		</div>
		</td>
	</tr>

	@if ($showReminderMail ?? false)
	<tr>
		<th>予約リマインダーメール配信有無</th>
		<td colspan="3">{{ $customer->stop_yoyaku_reminder_mail_flg == '1' ? '配信しない' : '配信する' }}</td>
	</tr>
	@endif

	<tr>
		<th>顧客グループ</th>
		<td>{{ $customerGroupName ?? '' }}</td>
		<th>新規来店動機</th>
		<td>{{ $raitenDoukiName ?? '' }}</td>
	</tr>
	<tr>
		<th>備考</th>
		<td colspan="3">{{ $customer->bikou }}</td>
	</tr>
</table>
</div>
<!--販促用情報table end-->

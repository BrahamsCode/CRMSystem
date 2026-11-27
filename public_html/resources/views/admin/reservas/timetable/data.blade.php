{{--
    Legacy: system/category9/yoyaku/ajax_time_table.php (fragmento HTML del popup de edición de una reserva del 予約表;
    lo carga timetable_js con $("#popFormWrap").load(...)).
    Variables:
      $cd            int     tb_yoyaku.cd.
      $yoyaku        object  tb_yoyaku.* + tb_staff_cd, moushikomi_date, staff_start_time, staff_end_time, shop_cd.
      $customer      ?object tb_customer (id, name, tel, email1, kanri_id) si la reserva es de un socio.
      $karte         null|false|object  null: カルテ desactivada; false: sin 来店 ese día; object: tb_raiten_rireki (cd, tb_search_cate_cd).
      $karteWord     string  manageWord('カルテ', 'c1_customer_detail[カルテ]').
      $candidate     ?object もしかして: socio (cd, name) con el mismo correo / teléfono / kana / nombre (solo no socios).
      $shows         Closure(string): bool  campo del formulario de reservas activo (tb_koumoku.koumoku_disp).
      $freeFields    list<array{n: int, label: string, value: string}> campos libres visibles con su valor.
      $hourRange     list<string> horas 'HH' del 予約受付可能時間; $startHour, $startMin, $endHour, $endMin string.
      $staffStart, $staffEnd string 'H:M' del 予約受付可能時間.
      $myMenu        list<string> cd de menús de la reserva (base y opción).
      $baseMenus, $optionMenus Collection menús del empleado (cd, shoyou_time, name, cate_name).
      $mailSendStatus ?string contents_master tb_c9_yoyaku / mail_send_status ('1' oculta 「メールを配信する」).
      $receiptDay    string  'Y-m-d' del día de la franja (iPad).
      $staffList     Collection personal (cd, name) para el iPad.
      $isIpad, $isTablet bool.
    Permisos: myKengen[1001][1] o master → @can('kengen', [1001, 1]) (botones 顧客詳細 / カルテ).
--}}
@php
    // fnc_popup_script(): abre la URL en otra ventana (roid.popUp en tablet) y cierra el popup
    $popupScript = fn (string $url) => $isTablet
        ? "roid.popUp('".url($url)."'); $('#yoyakuCustomerPopCloseBtn').click();"
        : "javascript:window.open('".$url."', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes'); $('#yoyakuCustomerPopCloseBtn').click();";
    $customerCd = (string) $yoyaku->tb_customer_cd;
    $tableHeadStyle = 'margin: 0; padding: 0 3px; background:#AACCFF; font-weight: normal;';
@endphp
<div id="yoyakuCustomerInfo" style="max-height:200px; overflow-y: scroll; overflow-x: hidden;">
@if ($customerCd !== '')
<div style="background:#FFBBBB;border:1px solid blue;padding:2px;margin:5px 0;">

	<div style="display: inline-block; margin-right: 5px;">
	<small>会員ID：</small><span style="font-weight: bold;">{{ $customer->id ?? '' }}</span>
	</div>

	@if ($customer->kanri_id ?? null)<div style="display: inline-block; margin-right: 5px;"><small>管理番号：</small><span style="font-weight: bold;">{{ $customer->kanri_id }}</span></div>@endif

	<div style="display: inline-block; margin-right: 5px;">
	<small>名前：</small><span style="font-weight: bold;">{{ $customer->name ?? '' }}</span>
	</div>

@can('kengen', [1001, 1])
	<div style="display: inline-block;text-align: right;">
	<input type="button" class="btnStyle" value="顧客詳細" onclick="{{ $popupScript('/system/category1/inc/customer_detail.php?kaiin_cd='.$customerCd.'&wintype=no_headers') }}" />
@if (! is_null($karte))
@if ($karte)
@php
    if ((string) $karte->tb_search_cate_cd !== '') {
        $karteLabel = $karteWord.'入力済';
        $karteUrl = '/system/category1/inc/customer_detail.php?kaiin_cd='.$customerCd.'&wintype=no_headers&related_features_cd='.$karte->cd.'&tab=9';
    } else {
        $karteLabel = '来店済・'.$karteWord.'未入力';
        $karteUrl = '/system/category1/inc/kanren_record_new.php?kaiin_cd='.$customerCd.'&wintype=no_headers&related_features_cd='.$karte->cd.'&tab=9';
    }
@endphp
				<input type="button" class="btnStyle" value="{{ $karteWord }}[{{ $karteLabel }}]" onclick="{{ $popupScript($karteUrl) }}" />
@else
				<input type="button" class="btnStyle" value="{{ $karteWord }}参照" onclick="{{ $popupScript('/system/category1/inc/customer_detail.php?kaiin_cd='.$customerCd.'&tab=9&wintype=no_headers') }}" />

				<input type="button" class="btnStyle" value="{{ $karteWord }}データ入力" onclick="{{ $popupScript('/system/category1/inc/kanren_record_new.php?kaiin_cd='.$customerCd.'&tab=9&wintype=no_headers') }}" />
@endif
@endif
	</div>
@endcan
</div>
@else
<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px;">
	<tr>
	<th colspan="2" style="margin: 0; padding: 0 3px; background:#AACCFF; font-weight: normal;">顧客情報</th>
	</tr>

	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="padding:0 3px;background: #EDF6FB; width: 5em; font-weight: normal; color: #333333;">名前</th>
	<td><input type="text" name="yoyakuName" id="yoyakuName" value="{{ $yoyaku->name }}" class="inputM NoMember" style="width:96%;" /></td>
	</tr>

@if ($shows('name_kana'))
	<tr style="border-bottom: 1px solid #CCCCCC;">
		<th style="padding:0 3px;background: #EDF6FB; font-weight: normal; color: #333333;">名前(カナ)</th>
		<td><input type="text" name="name_kana" id="name_kana" value="{{ $yoyaku->name_kana }}" class="inputM NoMember" style="width:96%;" /></td>
	</tr>
@endif
@if ($shows('mail'))
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="padding:0 3px;background: #EDF6FB; font-weight: normal; color: #333333;">メール</th>
	<td><input type="text" name="yoyakuMail" id="yoyakuMail" value="{{ $yoyaku->mail }}" class="inputM NoMember" style="ime-mode:disabled;width:96%;" /></td>
	</tr>
@endif

</table>

@endif

<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px;">
@if ($shows('tel'))
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="padding:0 3px;background: #EDF6FB; width: 5em; font-weight: normal; color: #333333;">電話番号</th>
	<td><input type="text" name="yoyakuTel" id="yoyakuTel" value="{{ $yoyaku->tel }}" class="inputM NoMember" style="ime-mode:disabled;width:96%;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif
@if ($shows('people'))
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="padding:0 3px;background: #EDF6FB; font-weight: normal; color: #333333;">予約人数</th>
	<td><input type="number" min="1" name="yoyakuPeople" id="yoyakuPeople" value="{{ (int) $yoyaku->people }}" class="inputM" style="ime-mode:disabled;width:96%;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif
@if ($shows('mark'))
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="padding:0 3px;background: #EDF6FB; font-weight: normal; color: #333333;">マーク</th>
	<td><input type="text" name="yoyakuMark" id="yoyakuMark" value="{{ $yoyaku->mark }}" class="inputM" style="ime-mode:disabled;width:96%;"/></td>
	</tr>
@endif


@foreach ($freeFields as $field)
	<tr style="border-bottom: 1px solid #CCCCCC;">
		<th style="padding:0 3px;background: #EDF6FB; font-weight: normal; color: #333333;">{{ $field['label'] }}</th>
		<td>
			<input type="text" name="free_koumoku[{{ $field['n'] }}]" value="{{ $field['value'] }}" class="inputM free_field" ref="{{ $field['label'] }}" />
		</td>
	</tr>
@endforeach

</table>

@if ($customerCd === '')
@if ($candidate)
@php
    $candidateUrl = '/system/category1/inc/customer_detail.php?kaiin_cd='.$candidate->cd.'&wintype=no_headers';
    $candidateJs = $isTablet
        ? "roid.popUp('".url($candidateUrl)."');"
        : "javascript:window.open('".$candidateUrl."', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');";
@endphp
登録済みの、「<a onclick="{{ $candidateJs }}">{{ $candidate->name }}</a>」さんですか？<div style="text-align:right"><a onclick="relatedCustomer('{{ $cd }}','{{ $candidate->cd }}')">会員「{{ $candidate->name }}」さんとして扱う</a></div>
@endif
@php
    $relatedUrl = route('admin.reservas.related-customer', ['yoyaku_cd' => $cd]);
    $relatedJs = $isTablet ? "roid.popUp('".$relatedUrl."');" : "openPopWindow('".$relatedUrl."', '600', '');";
@endphp
<div style="text-align:right"><a class="btnStyle" onclick="{{ $relatedJs }}">詳細検索から登録会員を探す</a></div>
@endif

<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px;">

@if ($isIpad)
	<tr>
		<th style="{{ $tableHeadStyle }}">
			営業日
		</th>
	</tr>

	<tr>
		<td>
			<input type="date" name="edit_moushikomi_date" id="edit_moushikomi_date" value="{{ $receiptDay }}">
		</td>
	</tr>

	<tr>
		<th style="{{ $tableHeadStyle }}">
			担当者
		</th>
	</tr>

	<tr>
		<td>
@if ($staffList->count() > 0)
<ul class="reserveStaffEditArea">
@foreach ($staffList as $member)
						<li
							cd="{{ $member->cd }}"
							class="{{ (int) $member->cd === (int) $yoyaku->tb_staff_cd ? 'reserveStaffEditSelected' : '' }}"
							style="
								margin:5px;
								padding:5px;
								border:1px solid #333333;
								border-radius:5px;
								width:6em;
								overflow:hidden;
								text-overflow:ellipsis;
								white-space:nowrap;
								text-align:center;
								display: inline-block;
								"
								onclick="
									$('.reserveStaffEditArea li').removeClass('reserveStaffEditSelected');
									$(this).addClass('reserveStaffEditSelected');
								"
							>
							{{ $member->name }}
						</li>
@endforeach
</ul>
@endif
		</td>
	</tr>
@endif


	<tr style="border-bottom: 1px solid #CCCCCC;">
		<th style="{{ $tableHeadStyle }}">
			目安時間
		</th>
	</tr>

	<tr style="border-bottom: 1px solid #CCCCCC;">
		<td style="padding: 5px 0;">
			<div>
				<select name="start_hour" id="start_hour">
@foreach ($hourRange as $hour)
<option value="{{ $hour }}" @selected($hour == $startHour)>{{ $hour }}</option>
@endforeach
				</select>時
				<select name="start_min" id="start_min">
@for ($i = 0; $i <= 55; $i += 5)
@php $min = sprintf('%02d', $i); @endphp
<option value="{{ $min }}" @selected($min == $startMin)>{{ $min }}</option>
@endfor
				</select>分～
				<select name="end_hour" id="end_hour">
@foreach ($hourRange as $hour)
<option value="{{ $hour }}" @selected($hour == $endHour)>{{ $hour }}</option>
@endforeach
				</select>時
				<select name="end_min" id="end_min">
@for ($i = 0; $i <= 55; $i += 5)
@php $min = sprintf('%02d', $i); @endphp
<option value="{{ $min }}" @selected($min == $endMin)>{{ $min }}</option>
@endfor
				</select>分
			</div>
			<span style="color:red;font-size:90%;">※予約受付可能時間：{{ $staffStart }} ～ {{ $staffEnd }}</span>

		</td>
	</tr>
</table>


<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px; display:none;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="margin: 0; padding: 0 3px; background:#AACCFF; font-weight: normal;">予約人数</th>
	</tr>

	<tr style="border-bottom: 1px solid #CCCCCC;">
	<td>
	<select name="people" id="people">
@for ($i = 1; $i <= 20; $i++)
<option value="{{ $i }}" @selected($i == $yoyaku->people)>{{ $i }}人</option>
@endfor
	</select>
	</td>
	</tr>
</table>


@if ($baseMenus->count() > 0)

	<span style="color:red;font-size:90%;">※メニュー再選択で目安時間の再計算はなされません。<br />時間変更を行う場合は手動で時間を再設定してください。</span>
	<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC;">
		<tr style="background:#AACCFF;">
			<th colspan="2" style="margin: 0; padding: 0 3px; font-weight: normal;">ベースメニュー</th>
			<th style="width:6em; margin: 0; padding: 0 3px; font-weight: normal;">目安時間</th>
		</tr>
@php $prevCate = ''; @endphp
@foreach ($baseMenus as $i => $menu)
@if ($prevCate != $menu->cate_name)
<tr style="background:#CCCCCC;"><th colspan="3" style="margin: 0; padding: 0 3px; font-weight: normal;">{{ $menu->cate_name }}</th></tr>
@php $prevCate = $menu->cate_name; @endphp
@endif
		<tr @if ($i % 2 == 1) class="tableBg" @endif>
			<td style="width: 20px;text-align:center;">
				<input
					class="base_menu_cd menuBtn"
					type="checkbox"
					name="menu[{{ $menu->cd }}]" id="checkbox_menu_{{ $menu->cd }}" value="{{ $menu->cd }}"
					time="{{ $menu->shoyou_time }}"
					@if (in_array((string) $menu->cd, $myMenu, true)) checked @endif />
			</td>
			<td><label for="checkbox_menu_{{ $menu->cd }}">{{ $menu->name }}</label></td>
		<td>{{ $menu->shoyou_time }}分</td>
		</tr>
@endforeach
	</table>

@if ($optionMenus->count() > 0)
	<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC;">
		<tr style="background:#AACCFF;">
			<th colspan="2" style="margin: 0; padding: 0 3px; font-weight: normal;">オプションメニュー</th>
			<th style="width:6em; margin: 0; padding: 0 3px; font-weight: normal;">目安時間</th>
		</tr>
@php $prevCate = ''; @endphp
@foreach ($optionMenus as $i => $menu)
@if ($prevCate != $menu->cate_name)
<tr style="background:#CCCCCC;"><th colspan="3" style="margin: 0; padding: 0 3px; font-weight: normal;">{{ $menu->cate_name }}</th></tr>
@php $prevCate = $menu->cate_name; @endphp
@endif
		<tr @if ($i % 2 == 1) class="tableBg" @endif>
			<td style="width: 20px;text-align:center;">
				<input
					class="option_menu_cd menuBtn"
					type="checkbox"
					name="option_menu[{{ $menu->cd }}]"
					value="{{ $menu->cd }}"
					time="{{ $menu->shoyou_time }}"
					@if (in_array((string) $menu->cd, $myMenu, true)) checked @endif />
			</td>
			<td>{{ $menu->name }}</td>
			<td>{{ $menu->shoyou_time }}分</td>
		</tr>
@endforeach
	</table>
@endif

@else
	<p>メニューデータはありません</p>
@endif


<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th style="margin: 0; padding: 0 3px; background:#AACCFF; font-weight: normal;">備考</th>
	</tr>

	<tr style="border-bottom: 1px solid #CCCCCC;">
	<td>
	<textarea name="yoyaku_comment" id="yoyaku_comment" style="width:99%;" rows="5">{{ $yoyaku->comment }}</textarea>
	</td>
	</tr>
</table>
</div>

<div style="text-align:center;margin:3px auto;padding:5px;border-top: 1px solid #AAAAAA;border-bottom: 1px solid #AAAAAA;">
@if ($mailSendStatus != 1)
	<p><input type="checkbox" name="sendMailFlg" id="sendMailFlg" value="1" style="vertical-align:middle;" />&nbsp;メールを配信する</p>
@else
	<p style="display:none"><input type="checkbox" name="sendMailFlg" id="sendMailFlg" value="1" style="vertical-align:middle;" />&nbsp;メールを配信する</p>
@endif
	<div>
		<label for="recalculation_check" style="color:red; font-size:10px;">
			<input type="checkbox" name="recalculation_check" id="recalculation_check" value="1" style="vertical-align: middle;" />
			チェックを入れて更新した場合、受付バーは施術メニューの合計時間に合わせて伸縮します。
		</label>
	</div>
	<input type="button" class="btnStyleM" value="更新" onclick="saveData('{{ $cd }}')" />
</div>
<div style="width:50%;float:left;text-align:left;">
	<input type="button" class="btnStyle" style="color:#FF0000 !important;" value="削除" onclick="deleteData('{{ $cd }}')" />
</div>
<div style="width:50%;float:left;text-align:right;">
	<input type="button" id="yoyakuCustomerPopCloseBtn" class="btnStyle" value="閉じる" onclick="closePopForm('popFormWrap')" />
</div>

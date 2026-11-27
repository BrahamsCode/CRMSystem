{{-- Legacy: system/category1/inc/detail_tab10.php (pestaña スタンプ de la ficha; parcial sin @extends).
     Variables:
       $customer               (modelo tb_customer: cd)
       $stampInfo              (array de cStamp::getStampData(cd): stamp_count, last_stamp_time, stamp_yukoukigen, kaikei_sum, kaikei_avg)
       $stampConf              (array de cStamp::getStampConfig(1): stamp_max)
       $stampShikkouSousa      (string|null: admin_master.stamp_shikkou_sousa; null si no hay fila; 1 = se muestra el vencimiento)
       $stampLogs              (LengthAwarePaginator, 20 por página por defecto (?kensu): filas de tb_stamp_log con las columnas del
                                $list_sql legacy: cd, tb_shop_cd, tb_staff_cd, tb_terminal_cd, touroku_date, shori_mae_stamp, kaikei,
                                shiharai_houhou, percentage, jidou_stamp, sounyu_stamp, meisai_mail, raiten_shori, shouhi_stamp,
                                action_flg, staff_name, terminal_no)
       $karuteLabel            (string: manageWord('カルテ', 'c1_customer_detail[カルテ]'))
       $relatedFeaturesConfig  (array: explode('<>', admin_master.related_features), lo define la ficha)
     JS usados de la ficha: stamp_submitChk2(), numbersonly(), replace_value(), send_tab(), $.fn.myFlexSelect. --}}
@php
    // Fecha epoch del legacy → Carbon en hora de Japón (null si no hay fecha: no se muestra 1970)
    $toDate = fn ($epoch) => ($epoch !== null && $epoch !== '') ? \Illuminate\Support\Carbon::createFromTimestamp((int) $epoch, 'Asia/Tokyo') : null;
    $actionList = [
        1 => '端末にかざして',
        2 => '新規登録ボーナス',
        3 => '端末ひもづけにて',
        4 => '管理画面にて',
        5 => 'スタンプアプリにて',
        6 => 'クーポン交換にて',
        7 => 'スタンプ失効にて',
        8 => $karuteLabel . '入力にて',
    ];
@endphp
<style type="text/css"><!--

.genzaiStamp {
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



<table class="tableBox">
<tr>
<th>現在の所有スタンプ</th>
<td>
	<script type="text/javascript">
	<!-- // 2011.10.24 ohno 入力補助プラグイン設定追加
	$(document).ready(function(){

		var options = ['1','2','3','4','5','6','7','8','9'];
		$('#stamp_qty').myFlexSelect({ options: options });

	});
	//-->
	</script>
	<span class="genzaiStamp">{{ number_format((int) $stampInfo['stamp_count']) }}</span> スタンプ / {{ ($stampConf['stamp_max'] == 999999999) ? '無制限' : 'MAX' . $stampConf['stamp_max'] . 'スタンプ' }}&emsp;&emsp;&emsp;
	<input type="text" name="stamp_qty" id="stamp_qty" maxlength="4" style="ime-mode:disabled;width:30px;" onkeypress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />&nbsp;スタンプを&nbsp;
	<select name="stamp_action_type" id="stamp_action_type">
		<!--
		<option value="">&nbsp;</option>
		-->
		<option value="1">追加</option>
		<option value="2">削減</option>
	</select>する
	<input type="button" name="stamp_action" id="stamp_action" class="btnStyle" value="スタンプ処理実行" onclick="stamp_submitChk2();" />
	<input type="hidden" name="stamp_kaiin_cd" id="stamp_kaiin_cd" value="{{ $customer->cd }}" /><br />
	<span style="color:red;">※スタンプ数を直接入力する際は半角数字で入力してください。</span><br/>
	<input type="checkbox" name="oshirase_mail" id="oshirase_mail" class="checkBtn" value="1" /><label for="oshirase_mail"><small>この処理に合わせて、加算後スタンプ数に合わせたクーポンお知らせメールを送信する。</small></label>
</td>
</tr>

<tr>
<th>最終スタンプ押印</th>
<td>{{ ($stampInfo['last_stamp_time'] ?? '') != '' ? $toDate($stampInfo['last_stamp_time'])->format('Y年m月d日 H時i分s秒') : '--' }}</td>
</tr>


@if ($stampShikkouSousa !== null && (float) $stampShikkouSousa == 1)
<tr>
<th>スタンプ有効期限</th>
<td>
<span style="font-size: 16px; font-weight: bold; color: #336699;">
{{ ((int) $stampInfo['stamp_count'] > 0 && ($stampInfo['stamp_yukoukigen'] ?? '') != '') ? $toDate((int) $stampInfo['stamp_yukoukigen'] + 1)->format('Y/m/d') : '期限なし' }}
</span>
<br /><small>スタンプ有効期限による失効処理は稼働中です。</small>
</td>
</tr>
@endif

@if (((int) $stampInfo['kaikei_sum'] + (int) $stampInfo['kaikei_avg']) > 0)
<tr>
<th>総会計額</th>
<td>{{ $stampInfo['kaikei_sum'] }} 円</td>
</tr>

<tr>
<th>平均単価</th>
<td>{{ $stampInfo['kaikei_avg'] }} 円</td>
</tr>
@endif
</table>

<div>※以下のログは、ユーザーに対してのスタンプ操作履歴です。ユーザーに対して、操作のログは公開されていません。</div>


@if ($stampLogs->count() > 0)
<table class="tableBox">
<tr>
<th style="width: auto;">処理日</th>
<th style="width: auto;">処理内容</th>


<th style="width: auto;"><small>処理前スタンプ</small></th>
<th style="width: auto;"><small>同時来店処理</small></th>
<th style="width: auto;">端末No</th>
<th style="width: auto;"><small>処理スタッフ</small></th>
</tr>
@foreach ($stampLogs as $stampLog)
@php
    $tourokuDate = $toDate($stampLog['touroku_date']);
    $actionFlg = (string) $stampLog['action_flg'];
@endphp
<tr>
<td>{{ $tourokuDate?->format('Y/m/d') }}<br />{{ $tourokuDate?->format('H:i') }}</td>
<td>
@switch ($actionFlg)
	@case ('4')
		@if ((string) $stampLog['sounyu_stamp'] !== '')
{{ $actionList[4] }}<span class="kyouchouBlue">{{ number_format((int) $stampLog['sounyu_stamp'] + (int) $stampLog['jidou_stamp']) }}</span>スタンプ加算
		@else
{{ $actionList[4] }}<span class="kyouchouRed">{{ number_format((int) $stampLog['shouhi_stamp']) }}</span>スタンプ削減
		@endif
		@break

	@case ('5')
	@case ('8')
@php
    $shoriLabel = ((int) $stampLog['sounyu_stamp'] >= 0) ? '追加' : '削減';
    $shoriColor = ((int) $stampLog['sounyu_stamp'] >= 0) ? 'kyouchouBlue' : 'kyouchouRed';
@endphp


	<table>
		<tr>
		<th style="width: auto;">会計内容</th>
		<th style="width: auto;">支払方法</th>
		<th style="width: auto;">還元率</th>
		<th style="width: auto;">明細通知</th>
		</tr>

		<tr>
		<td>
			<table>

			<tr>
			<th style="width: auto;">会計</th>
			<td><span class="kyouchouBlue">{{ number_format((int) $stampLog['kaikei']) }}</span> 円</td>
			</tr>
			</table>

			自動計算で <span class="kyouchouBlue">{{ $stampLog['jidou_stamp'] }}</span> 個 追加<br />
			{{ $shoriLabel }}処理で <span class="{{ $shoriColor }}">{{ abs((int) $stampLog['sounyu_stamp']) }}</span> 個 {{ $shoriLabel }}
		{{-- El legacy no cierra este <td> (HTML idéntico) --}}
		<td>{{ ((float) $stampLog['shiharai_houhou'] == 2) ? 'カード' : '現金' }}</td>
		<td>{{ $stampLog['percentage'] }}円で1個</td>
		<td>{{ ((float) $stampLog['meisai_mail'] == 1) ? '通知済' : '未' }}</td>
		</tr>
	</table>
	<small>
	※上限を超える場合は、所持スタンプはまるめられます。
	</small>

		@break

	@case ('6')
{{ $actionList[6] }}<span class="kyouchouRed">{{ number_format((int) $stampLog['shouhi_stamp']) }}</span>スタンプ消費
		@break

	@case ('7')
{{ $actionList[7] }}<span class="kyouchouRed">{{ number_format((int) $stampLog['shouhi_stamp']) }}</span>スタンプ失効
		@break

	@default
{{ $actionList[(int) $actionFlg] ?? '' }}<span class="kyouchouBlue">{{ number_format((int) $stampLog['sounyu_stamp'] + (int) $stampLog['jidou_stamp']) }}</span>スタンプ加算
@endswitch

</td>
<td>{{ (int) $stampLog['shori_mae_stamp'] }}<small>スタンプ</small></td>
<td>{{ ((float) $stampLog['raiten_shori'] == 1) ? '処理済' : '未' }}</td>
<td>
{{ $stampLog['tb_terminal_cd'] == '-1' ? '管理画面' : $stampLog['terminal_no'] }}
</td>

<td>{{ $stampLog['staff_name'] }}</td>
</tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.clientes.partials.page-control', ['paginator' => $stampLogs, 'params' => ['tab' => '10', 'kaiin_cd' => $customer->cd]])
</ul>
@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>操作履歴がありません。<br/><br/></p>
@endif

@if (isset($relatedFeaturesConfig[2]) && $relatedFeaturesConfig[2] != '' && ($relatedFeaturesConfig[1] ?? null) == '2')
	<a class="backBtnStyle" onclick="send_tab('9',''); return false;">　戻る　</a>
@endif

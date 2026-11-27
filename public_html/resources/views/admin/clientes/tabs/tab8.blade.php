{{-- Legacy: system/category1/inc/detail_tab8.php (pestaña ポイント de la ficha; parcial sin @extends).
     Variables:
       $customer                (modelo tb_customer: cd)
       $customerPoint           (int: service_point actual de Point::fetchCurrentPoint, 0 si no hay)
       $pointYukoukigen         (int|null: point_yukoukigen epoch de la misma consulta)
       $pointYukoukigenFlg      (string|null: admin_master.point_yukoukigen_flg; '2' = no se muestra el vencimiento)
       $kaikeiSum, $kaikeiAvg   (int: SUM/AVG(kaikei) de tb_point_log del cliente, disp_flg <> '9')
       $pointLogs               (LengthAwarePaginator, 20 por página por defecto (?kensu): filas de tb_point_log con las columnas
                                 del $list_sql legacy: cd, tb_shop_cd, tb_staff_cd, tb_terminal_cd, touroku_date, shori_mae_point,
                                 kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point,
                                 meisai_mail, raiten_shori, action_flg, staff_name, terminal_no, kaishamei, use_point)
       $relatedFeaturesConfig   (array: explode('<>', admin_master.related_features), lo define la ficha)
     JS usados de la ficha: point_submitChk(), numbersonly(), replace_value(), send_tab(), $.fn.myFlexSelect. --}}
@php
    // Fecha epoch del legacy → Carbon en hora de Japón (vacío si no hay fecha: no se muestra 1970)
    $toDate = fn ($epoch) => ($epoch !== null && $epoch !== '') ? \Illuminate\Support\Carbon::createFromTimestamp((int) $epoch, 'Asia/Tokyo') : null;
@endphp
<style type="text/css">

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

</style>

	<fieldset style="margin:10px;padding:10px;border:1px dotted">

<table class="tableBox">
<tr>
<th>現在の所有ポイント</th>
<td>
	<script type="text/javascript">
	$(document).ready(function(){

		var options = ['1','2','3','4','5','6','7','8','9','10'];
		$('#point_qty').myFlexSelect({ options: options });

	});

	</script>
	<span class="genzaiPoint">{{ number_format((int) $customerPoint) }}</span> pt&emsp;&emsp;&emsp;
	<input type="text" name="point_qty" id="point_qty" maxlength="6" style="ime-mode:disabled;" onkeypress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />&nbsp;ポイントを&nbsp;
	<select name="point_action_type" id="point_action_type">
		<option value="1">追加</option>
		<option value="2">削減</option>
	</select>する
	<input type="button" name="point_action" id="point_action" class="btnStyle" value="ポイント処理実行" onclick="point_submitChk();" />
	<input type="hidden" name="point_kaiin_cd" id="point_kaiin_cd" value="{{ $customer->cd }}" /><br />
	<span style="color:red;">※ポイント数を直接入力する際は半角数字で入力してください。</span><br/>
</td>
</tr>


@if ($pointYukoukigenFlg != '2')
<tr>
<th>ポイント有効期限</th>
<td>{{ ((int) $customerPoint > 0 && $pointYukoukigen) ? $toDate((int) $pointYukoukigen + 1)->format('Y/m/d') : '--' }}</td>
</tr>
@endif

<tr>
<th>総会計額</th>
<td>{{ number_format((int) $kaikeiSum) }} 円</td>
</tr>

<tr>
<th>平均単価</th>
<td>{{ number_format((int) $kaikeiAvg) }} 円</td>
</tr>

</table>

	</fieldset>

<div>※以下のログは、ユーザーに対してのポイント操作履歴です。ユーザーに対して公表されるのは、会計処理時の履歴のみです。</div>

@if ($pointLogs->count() > 0)
<table class="tableBox">
<tr>
<th style="width: auto;">処理日</th>
<th style="width: auto;">処理内容</th>

<th style="width: auto;">支払方法</th>
<th style="width: auto;">還元率</th>
<th style="width: auto;">明細通知</th>
<th style="width: auto;">同時来店処理</th>
<th style="width: auto;">端末No</th>
<th style="width: auto;">処理スタッフ</th>
</tr>
@foreach ($pointLogs as $pointLog)
@php
    $tourokuDate = $toDate($pointLog['touroku_date']);
    $staffName = $pointLog['staff_name'];
    $actionFlg = (string) $pointLog['action_flg'];
@endphp
<tr>
<td>{{ $tourokuDate?->format('Y/m/d') }}<br/>{{ $tourokuDate?->format('H:i') }}</td>
@if ($actionFlg == 'api:point_use')
<td colspan="6">API連携によって<span class="kyouchouRed">{{ number_format((int) $pointLog['shouhi_point']) }}</span>ポイント消費。交換店舗名:{{ $pointLog['kaishamei'] }}</td>
@elseif (str_starts_with($actionFlg, 'exchangeSalesItem:'))
@php
    // action_flg = exchangeSalesItem:<sales_items_cd>:<sales_items_name>
    [, , $salesItemsName] = array_pad(explode(':', $actionFlg), 3, null);
@endphp
<td colspan="6">API連携によって商品交換されました。<span class="kyouchouRed">{{ number_format((int) $pointLog['shouhi_point']) }}</span>ポイント消費。交換商品名:{{ $salesItemsName }}。交換店舗名:{{ $pointLog['kaishamei'] }}</td>
@elseif (str_starts_with($actionFlg, 'mycolle:'))
@php
    // 2016.08.26 マイコレでポイント獲得を追加
    [, $mycolleAct] = array_pad(explode(':', $actionFlg), 2, null);
    $mycolleMsg = 'マイコレの' . match ($mycolleAct) {
        'introduce' => 'お友達紹介で',
        'signUp', 'newRegist' => '新規会員登録で',
        'pointUse' => 'ポイント利用で',
        'couponChange' => 'クーポン交換で',
        'checkIn' => 'チェックインで',
        default => 'プッシュ配信で',
    };
    $staffName = 'マイコレ';
@endphp
<td colspan="6">{{ $mycolleMsg }}@if ($mycolleAct == 'pointUse' || $mycolleAct == 'couponChange')<span class="kyouchouRed">{{ number_format((int) $pointLog['use_point']) }}</span>ポイントを利用@else<span class="kyouchouBlue">{{ number_format((int) $pointLog['sounyu_point']) }}</span>ポイントを追加@endif</td>
@elseif ($actionFlg == '7')
<td colspan="6">失効処理で<span class="kyouchouBlue">{{ number_format((int) $pointLog['shouhi_point']) }}</span>ポイントを失効</td>
@elseif (($pointLog['tb_terminal_cd'] == '-1' && (string) $pointLog['kaikei'] === '') || $actionFlg == 'pos')
<td colspan="6">@if ((string) $pointLog['sounyu_point'] !== ''){{ $actionFlg == 'pos' ? 'POS' : '管理' }}画面のポイント処理で<span class="kyouchouBlue">{{ number_format((int) $pointLog['sounyu_point']) }}</span>ポイントを追加@else{{ $actionFlg == 'pos' ? 'POS' : '管理' }}画面のポイント処理で<span class="kyouchouRed">{{ number_format((int) $pointLog['shouhi_point']) }}</span>ポイントを削減@endif</td>
@else
<td>

	<table style="width: 85%;">

	<tr>
	<th style="width: auto;">会計</th>
	<td><span class="kyouchouBlue">{{ number_format((int) $pointLog['kaikei']) }}</span> 円</td>
	</tr>

	<tr>
	<th style="width: auto;">使用ポイント</th>
	<td><span class="kyouchouRed">{{ number_format((int) $pointLog['shouhi_point']) }}</span> pt</td>
	</tr>

	<tr>
	<th style="width: auto;">差引会計</th>
	<td><span class="kyouchouBlue">{{ number_format((int) $pointLog['sashihiki_kaikei']) }}</span> 円</td>
	</tr>

	</table>


自動計算で <span class="kyouchouBlue">{{ number_format((int) $pointLog['jidou_point']) }}</span> pt 追加<br />
@php
    $shoriLabel = ((int) $pointLog['sounyu_point'] >= 0) ? '追加' : '削減';
    $shoriColor = ((int) $pointLog['sounyu_point'] >= 0) ? 'kyouchouBlue' : 'kyouchouRed';
@endphp
{{ $shoriLabel }}処理で <span class="{{ $shoriColor }}">{{ number_format(abs((int) $pointLog['sounyu_point'])) }}</span> pt {{ $shoriLabel }}

</td>

<td>{{ ((float) $pointLog['shiharai_houhou'] == 2) ? 'カード' : '現金' }}</td>
<td>{{ $pointLog['percentage'] }}%</td>
<td>{{ ((float) $pointLog['meisai_mail'] == 1) ? '通知済' : '未' }}</td>
<td>{{ ((float) $pointLog['raiten_shori'] == 1) ? '処理済' : '未' }}</td>
<td>
{{ $pointLog['tb_terminal_cd'] == '-1' ? '管理画面' : $pointLog['terminal_no'] }}
</td>
@endif
<td>{{ $staffName }}</td>
</tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.clientes.partials.page-control', ['paginator' => $pointLogs, 'params' => ['tab' => '8', 'kaiin_cd' => $customer->cd]])
</ul>
@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>操作履歴がありません。<br/><br/></p>
@endif

@if (isset($relatedFeaturesConfig[2]) && $relatedFeaturesConfig[2] != '' && ($relatedFeaturesConfig[1] ?? null) == '1')
	<a class="backBtnStyle" onclick="send_tab('9',''); return false;">　戻る　</a>
@endif

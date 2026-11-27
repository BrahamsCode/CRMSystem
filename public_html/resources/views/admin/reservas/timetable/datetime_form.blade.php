{{--
    Legacy: system/category9/yoyaku/ajax_getYoyakuDatetimeForm.php (fragmento: nueva fecha y hora de inicio de una reserva).
    Variables:
      $cd        ?string `cd` de la petición (onclick changeReserveDatetime).
      $years     list<int> años: del de la reserva al siguiente al actual.
      $year, $month, $day string fecha de la franja ('Y', 'm', 'd').
      $hourRange list<string> horas 'HH' del horario de tb_yoyaku_config ([] sin configuración).
      $startHour, $startMin string hora de inicio actual ('HH' +24 si pasa de medianoche, 'MM').
--}}
<div id="yoyakuDatetimeInfo">

	<table style="width:100%; margin-bottom: 5px;border: 1px solid #CCCCCC; border-bottom: 0px;">
		<tr style="border-bottom: 1px solid #CCCCCC;">
			<th style="margin: 0; padding: 0 3px; background:#AACCFF; font-weight: normal;">開始日時を選択してください</th>
		</tr>

		<tr style="border-bottom: 1px solid #CCCCCC;">
			<td style="padding: 5px 0;">
				<select name="new_reserve_year" id="new_reserve_year">
@foreach ($years as $y)
<option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
@endforeach
				</select>年
				<select name="new_reserve_month" id="new_reserve_month">
@for ($i = 1; $i <= 12; $i++)
@php $v = sprintf('%02d', $i); @endphp
<option value="{{ $v }}" @selected($v == $month)>{{ $v }}</option>
@endfor
				</select>月
				<select name="new_reserve_day" id="new_reserve_day">
@for ($i = 1; $i <= 31; $i++)
@php $v = sprintf('%02d', $i); @endphp
<option value="{{ $v }}" @selected($v == $day)>{{ $v }}</option>
@endfor
				</select>日&emsp;
				<select name="new_start_hour" id="new_start_hour">
@foreach ($hourRange as $hour)
<option value="{{ $hour }}" @selected($hour == $startHour)>{{ $hour }}</option>
@endforeach
				</select>時
				<select name="new_start_min" id="new_start_min">
@for ($i = 0; $i <= 55; $i += 5)
@php $v = sprintf('%02d', $i); @endphp
<option value="{{ $v }}" @selected($v == $startMin)>{{ $v }}</option>
@endfor
				</select>分
			</td>
		</tr>
	</table>

	<div style="text-align:center;margin:3px auto;padding:5px;border-top: 1px solid #AAAAAA;border-bottom: 1px solid #AAAAAA;">
		<p><input type="checkbox" name="sendMailFlg" id="sendMailFlg" value="1" style="vertical-align:middle;" />&nbsp;メールを配信する</p>
		<input type="button" class="btnStyleM" value="予約日時を変更する" onclick="changeReserveDatetime('{{ $cd }}')" />
	</div>
</div>

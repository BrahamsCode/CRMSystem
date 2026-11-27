{{-- Legacy: campos de cada bloque de system/category1/rank/customer_rank_schedule.php (指定期間, tipo, 指定月/指定日,
     指定曜日 y 指定時刻). Variables: $type ('kingaku' | 'raiten'), $s (RankScheduleService::settings: type_flg, month[],
     day[], week[], hour, minute, kikan) --}}
@php
    $weekLabelFor = $type === 'kingaku' ? 'kingaku_week_1' : 'week_1';
    $weekNames = ['日', '月', '火', '水', '木', '金', '土'];
@endphp
<div class="pB10">
<table class="tableBox">

<tr>
<th><label for="{{ $type }}_rank_shori_kikan">指定期間</label></th>
<td>過去
<input type="text" name="rank_shori_kikan" id="{{ $type }}_rank_shori_kikan" class="input2S" value="{{ $s['kikan'] }}" maxlength="4" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />日間で振り分け
<div><small>※下部で指定する処理実行のタイミングから計って、過去何日を振り分けするかの値です。</small></div>
</td>
</tr>

</table>
</div>

<table id="{{ $type }}_typeFlgTbl" class="tableBox bgHover">

<tr>
<td>
<label for="{{ $type }}_type_flg_1" style="display: block;">
<input type="radio" name="type_flg" id="{{ $type }}_type_flg_1" class="mR5" value="1" @checked($s['type_flg'] == '' || $s['type_flg'] == '1') />
<span class="text2L bold">月日を指定して振り分けの実行日時を設定する</span><br />
設定した月・日に振り分けを行い、お客様のランクを決定します。</label>
</td>
</tr>

<tr>
<td>
<label for="{{ $type }}_type_flg_2" style="display: block;">
<input type="radio" name="type_flg" id="{{ $type }}_type_flg_2" class="mR5" value="2" @checked($s['type_flg'] == '2') />
<span class="text2L bold">曜日を指定して振り分けの実行日時を設定する</span><br />
毎週、設定した曜日に振り分けを行い、お客様のランクを決定します。</label>
</td>
</tr>

</table>

<div id="{{ $type }}_month_set" class="mT10" style="display:none;">
<table class="tableBox">

<tr>
<th><label for="{{ $type }}_month_1">指定月</label></th>
<td>

<table class="calenderTable">
<tr>
@for ($i = 1; $i < 13; $i++)

<td style="width: 8%;">
<label for="{{ $type }}_month_{{ $i }}">
<span style="margin-bottom: 5px;">{{ $i }}月</span>
<input type="checkbox" name="month[]" id="{{ $type }}_month_{{ $i }}" class="chkbox1" value="{{ $i }}" @checked(in_array((string) $i, $s['month'], true)) />
</label>
</td>
@endfor
</tr>
</table>

</td>
</tr>

<tr>
<th><label for="{{ $type }}_day_1">指定日</label></th>
<td>

<table class="calenderTable">
@for ($row = 0; $row < 5; $row++)
<tr>
@for ($num = $row * 7 + 1; $num <= min($row * 7 + 7, 31); $num++)
<td style="width: 14%;">
<label for="{{ $type }}_day_{{ $num }}">
<span style="margin-bottom: 5px;">{{ $num }}日</span>
<input type="checkbox" name="day[]" id="{{ $type }}_day_{{ $num }}" class="chkbox2" value="{{ $num }}" @checked(in_array((string) $num, $s['day'], true)) />
</label>
</td>
@endfor
@if ($row === 4)

<td style="width: 14%;">
<label for="{{ $type }}_day_last"><span style="margin-bottom: 5px;">月末</span>
<input type="checkbox" name="day[]" id="{{ $type }}_day_last" class="chkbox2" value="32" @checked(in_array('32', $s['day'], true)) />
</label>
</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
@endif
</tr>
@endfor
</table>

</td>
</tr>

</table>
</div>




<div id="{{ $type }}_week_set" class="mT10" style="display:none;">
<table class="tableBox">

<tr>
<th><label for="{{ $weekLabelFor }}">指定曜日</label></th>
<td>

<table class="calenderTable">
<tr>

@foreach ($weekNames as $i => $weekName)
<td style="width: 8%;">
<label for="{{ $type }}_week_{{ $i }}">
<span style="margin-bottom: 5px;">{{ $weekName }}</span>
<input type="checkbox" name="week[]" id="{{ $type }}_week_{{ $i }}" class="chkbox3" value="{{ $i }}" @checked(in_array((string) $i, $s['week'], true)) />
</label>
</td>

@endforeach
</tr>
</table>

</td>
</tr>
</table>
</div>



<table class="tableBox">

<tr>
<th style="border-top:none;"><label for="{{ $type }}_hour">指定時刻</label></th>
<td style="border-top:none;">
<select name="hour" id="{{ $type }}_hour" class="selectBox">
@for ($i = 0; $i <= 23; $i++)
<option value="{{ sprintf('%02d', $i) }}" @selected($s['hour'] !== '' && is_numeric($s['hour']) && (int) $s['hour'] === $i)>{{ sprintf('%02d', $i) }}時</option>
@endfor
</select>
<select name="minute" id="{{ $type }}_minute" class="selectBox">
@for ($i = 0; $i <= 55; $i += 5)
<option value="{{ sprintf('%02d', $i) }}" @selected($s['minute'] !== '' && is_numeric($s['minute']) && (int) $s['minute'] === $i)>{{ sprintf('%02d', $i) }}分</option>
@endfor
</select>
に振り分けを実行します。
</td>
</tr>
</table>

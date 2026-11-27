{{-- Legacy: system/category1/inc/ajax_raiten_time_select.php (fragmento HTML que se carga en #raiten_timestamp).
     Variables:
       $raitenYear, $raitenMonth, $raitenDay, $raitenHour, $raitenMin  string: valores seleccionados (mes…minuto con 2 cifras; '' = vacío)
       $currentYear  int: año actual
       $minYear      int: el selector de años va de $currentYear hasta $minYear + 1 --}}
<select class="raitenTimeSelect" name="raiten_y" id="raiten_y">
<option value="">&nbsp;</option>
@for ($i = $currentYear; $i > $minYear; $i--)
	<option value="{{ $i }}" @selected($raitenYear == $i)>{{ $i }}</option>
@endfor
</select>年
<select class="raitenTimeSelect" name="raiten_m" id="raiten_m">
<option value="">&nbsp;</option>
@for ($i = 1; $i <= 12; $i++)
<option value="{{ sprintf('%02d', $i) }}" @selected($raitenMonth === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>月
<select class="raitenTimeSelect" name="raiten_d" id="raiten_d">
<option value="">&nbsp;</option>
@for ($i = 1; $i <= 31; $i++)
<option value="{{ sprintf('%02d', $i) }}" @selected($raitenDay === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>日
&emsp;
<select class="raitenTimeSelect" name="raiten_h" id="raiten_h">
<option value="">&nbsp;</option>
@for ($i = 0; $i <= 23; $i++)
<option value="{{ sprintf('%02d', $i) }}" @selected($raitenHour === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>時
<select class="raitenTimeSelect" name="raiten_i" id="raiten_i">
<option value="">&nbsp;</option>
@for ($i = 0; $i < 60; $i++)
<option value="{{ sprintf('%02d', $i) }}" @selected($raitenMin === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>分

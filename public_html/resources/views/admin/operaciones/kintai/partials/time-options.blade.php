{{-- <option> de hora («05»…«28», se muestra 00-23) o de minuto (00-59) de los formularios de kintai.
     Variables: $type ('hour'|'minute'), $hours ([inicio kijun, fin exclusivo]) para las horas, $selected (valor), $blank (bool: primera opción vacía) --}}
@if ($blank ?? true)
<option value=""></option>
@endif
@if ($type === 'hour')
@for ($i = $hours[0]; $i < $hours[1]; $i++)
<option value="{{ sprintf('%02d', $i) }}"@if ((string) ($selected ?? '') === sprintf('%02d', $i)) selected="selected"@endif>{{ sprintf('%02d', $i > 23 ? $i - 24 : $i) }}</option>
@endfor
@else
@for ($i = 0; $i < 60; $i++)
<option value="{{ sprintf('%02d', $i) }}"@if ((string) ($selected ?? '') === sprintf('%02d', $i)) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
@endfor
@endif

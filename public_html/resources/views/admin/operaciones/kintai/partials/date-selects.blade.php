{{-- Selectores 年/月/日 de kintai (años 1990…el actual, meses y días a 2 cifras).
     Variables: $yy, $mm, $dd (int) --}}
<select id="yy" name="yy">
@for ($i = 1990; $i <= (int) now(\App\Services\Operaciones\LegacyMonth::TIMEZONE)->format('Y'); $i++)
<option value="{{ $i }}"@if ($yy === $i) selected="selected"@endif>{{ $i }}</option>
@endfor
</select>&nbsp;年&nbsp;

<select id="mm" name="mm">
@for ($i = 1; $i <= 12; $i++)
<option value="{{ sprintf('%02d', $i) }}"@if ($mm === $i) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>&nbsp;月&nbsp;

<select id="dd" name="dd">
@for ($i = 1; $i <= 31; $i++)
<option value="{{ sprintf('%02d', $i) }}"@if ($dd === $i) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
@endfor
</select>&nbsp;日&nbsp;

{{--
    Legacy: filas 開始時間 / 終了時間 / 休憩・外出時間 / 最大受付人数 de yoyaku_uketsuke_day.php y yoyaku_multi_uketsuke_day.php.
    Variables:
      $config  ?ReservationConfig  tb_yoyaku_config de la tienda (start_time/end_time 'HH:MM'); null = sin 予約設定
      $start   ?array              ['HH', 'MM'] del inicio de la franja que se edita (null en el alta)
      $end     ?array              ['HH', 'MM'] del fin (la hora pasa de 23 si termina después de medianoche)
      $isNew   bool                alta ($_REQUEST['cd'] == ''): preselecciona el horario de la tienda
      $people  ?int                最大受付人数 guardado (1 por defecto)
--}}
@php
    $startWorkTime = $config ? explode(':', (string) $config->start_time) : null;
    $endWorkTime = $config ? explode(':', (string) $config->end_time) : null;
    $hourFrom = (int) ($startWorkTime[0] ?? 0);
    $hourTo = (int) ($endWorkTime[0] ?? 0);
    // Comparación suelta del legacy ('09' == 9)
    $same = fn ($a, $b) => $a !== null && $b !== null && $a !== '' && $b !== '' && (int) $a === (int) $b;
@endphp
	<tr>
		<th>開始時間</th>
		<td>

			<select name="start_hour">
@for ($i = $hourFrom; $i <= $hourTo; $i++)
@php $dispI = sprintf('%02d', $i); @endphp
<option value="{{ $dispI }}"{{ (($isNew && $same($dispI, $startWorkTime[0] ?? null)) || $same($start[0] ?? null, $dispI)) ? ' selected' : '' }}>{{ $dispI }}</option>
@endfor
			</select>時
			<select name="start_min">
@for ($i = 0; $i < 2; $i++)
@php $dispI = sprintf('%02d', $i * 30); @endphp
<option value="{{ $dispI }}"{{ (($isNew && $same($dispI, $startWorkTime[1] ?? null)) || $same($start[1] ?? null, $dispI)) ? ' selected' : '' }}>{{ $dispI }}</option>
@endfor
			</select>分

			<input type="hidden" id="shop_start_time" value="{{ $config?->start_time }}" />
		</td>
	</tr>

	<tr>
		<th>終了時間</th>
		<td>

			<select name="end_hour">
@for ($i = $hourFrom; $i <= $hourTo; $i++)
@php $dispI = sprintf('%02d', $i); @endphp
<option value="{{ $dispI }}"{{ (($isNew && $same($dispI, $endWorkTime[0] ?? null)) || $same($end[0] ?? null, $dispI)) ? ' selected' : '' }}>{{ $dispI }}</option>
@endfor
			</select>時
			<select name="end_min">
@for ($i = 0; $i < 2; $i++)
@php $dispI = sprintf('%02d', $i * 30); @endphp
<option value="{{ $dispI }}"{{ (($isNew && $same($dispI, $endWorkTime[1] ?? null)) || $same($end[1] ?? null, $dispI)) ? ' selected' : '' }}>{{ $dispI }}</option>
@endfor
			</select>分

			<input type="hidden" id="shop_end_time" value="{{ $config?->end_time }}" />
		</td>
	</tr>

	<tr>
		<th>休憩・外出時間</th>
		<td id="selectBreakTime">
		<a class="btn btnStyle" onclick="addBreakTime('','');">休憩・外出時間を追加</a>
		</td>
	</tr>

	<tr>
	<th>最大受付人数</th>
	<td><input type="text" name="people" class="input2S" value="{{ old('people', $people ?: 1) }}" style="ime-mode:disabled" />人</td>
	</tr>

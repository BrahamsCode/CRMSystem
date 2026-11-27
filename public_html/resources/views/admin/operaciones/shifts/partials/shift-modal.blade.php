{{-- Modal スケジュール設定 de staff_shift.php: por empleado (#modal<cd>, botón .setShift con data-staff_cd) o el
     de edición (#modalEdit, botón .setShiftEdit, con hidden key/staff_cd y el nombre en .staffName).
     Variables: $id, $staffName (null en #modalEdit), $button, $staffCd, $timeStr, $kinds --}}
<div id="{{ $id }}" class="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
@if ($staffName === null)
	<input type="hidden" name="key" value="">
	<input type="hidden" name="staff_cd" value="">
@endif
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">スケジュール設定</h3>
	</div>
	<div class="modal-body">
		<span>スタッフ名 : @if ($staffName === null)<span class="staffName"></span>@else{!! $staffName !!}@endif</span>
		<br><br>
		<span>シフト時間 : </span>
		<select name="sk" class="sk_select">
@foreach ($timeStr as $time)
			<option value="{{ $time }}">{{ $time }}</option>
@endforeach
		</select> ～
		<select name="ek" class="ek_select">
@foreach ($timeStr as $time)
			<option value="{{ $time }}">{{ $time }}</option>
@endforeach
		</select>
@if ($kinds->isNotEmpty())
		<br><br>
		<span>役職 : </span>
		<select name="kubun" class="kubun_select">
@foreach ($kinds as $kind)
			<option value="{{ $kind->cd }}">{{ $kind->name }}</option>
@endforeach
		</select>
@endif
		<br><br>
		<span>メモ : </span>
		<div class="form-group">
			<textarea name="komment" class="form-control komment_textarea"></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn btn-default" data-dismiss="modal" aria-hidden="true">閉じる</button>
		<button class="btn btn-primary {{ $button }}" data-dismiss="modal" @if ($staffCd !== null) data-staff_cd="{{ $staffCd }}" @endif>設定</button>
	</div>
</div>

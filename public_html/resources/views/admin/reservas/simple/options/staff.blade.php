{{-- Legacy: system/category3/reservation/ajax_staff.php (<option> de 指名 para jQuery .load).
     Variables: $staffList Collection (cd, name) del personal con reserve_flg = '1' de la tienda --}}
@if ($staffList->isEmpty())
<option value="">登録されていません</option>
@else

<option value="">選択して下さい</option>

@foreach ($staffList as $member)
<option value="{{ $member->cd }}">{{ $member->name }}</option>
@endforeach
@endif

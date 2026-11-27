{{--
    <option> de スタッフ de una tienda (legacy: karute_menu/ajax_staff.php), para staff_total.php.
    Datos: $staff (Collection<Staff>), $staffWord (manageWord «スタッフ»)
--}}
@if ($staff->isEmpty())
<option value="">登録されていません</option>
@else
<option value="">▼{{ $staffWord }}選択</option>
@foreach ($staff as $member)
<option value="{{ $member->cd }}">{{ $member->name }}</option>
@endforeach
@endif

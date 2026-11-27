{{--
    Legacy: system/category1/karute_menu/ajax_staff.php (<option> de スタッフ para スタッフ別集計).
    Variables: $staff (Collection<Staff> cd/name), $staffWord (?string: tb_manage_words 'c9_yoyaku[スタッフ]').
--}}
@if ($staff->isEmpty())
<option value="">登録されていません</option>
@else
<option value="">▼{{ filled($staffWord) ? $staffWord : 'スタッフ' }}選択</option>
@foreach ($staff as $row)
<option value="{{ $row->cd }}">{{ $row->name }}</option>
@endforeach
@endif

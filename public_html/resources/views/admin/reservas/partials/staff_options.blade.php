{{--
    Legacy: system/category9/yoyaku/ajax_staff.php (<option> de los empleados activos de la tienda; lo cargan yoyaku.php y スタッフ施術管理).
    Variables:
      $staff      Collection  tb_staff (cd, name) por common_flg, rank, cd
      $staffWord  string      manageWord('スタッフ', 'c9_yoyaku[スタッフ]')
    Sin empleados el controlador responde <option value="">登録されていません</option>.
--}}
<option value="">▼{{ $staffWord }}選択</option>

@foreach ($staff as $row)
<option value="{{ $row->cd }}">{{ $row->name }}</option>
@endforeach

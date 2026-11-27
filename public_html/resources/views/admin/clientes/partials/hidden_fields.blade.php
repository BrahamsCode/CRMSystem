{{-- Legacy: bucle "POSTデータを全て返す" de system/category1/inc/customer_new_confirm.php.
     Variables: $fields (array|Collection nombre => valor).
     El legacy solo reenviaba los valores escalares; los arrays (yoyaku_menu_cd[cd] de los campos 'メニュー項目')
     se perdían al registrar. Aquí se reenvían también (un nivel) para no perder esa selección. --}}
@foreach ($fields as $key => $val)
@if (is_array($val))
@foreach ($val as $subKey => $subVal)
@if (! is_array($subVal))
<input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subVal }}" />
@endif
@endforeach
@else
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endif
@endforeach

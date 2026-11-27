{{-- <option> como los make_*_options() del legacy (system/inc/kihon.php): primera opción vacía con el
     texto «▼…選択» y luego cada valor; el valor y el texto son el mismo nombre salvo que $options sea
     [valor => texto].
     Variables: $placeholder (p. ej. '▼雇用形態選択'), $options (lista o mapa), $selected (valor actual) --}}
<option value="">{{ $placeholder }}</option>
@foreach ($options as $value => $label)
@php($value = array_is_list(is_array($options) ? $options : $options->all()) ? $label : $value)
<option value="{{ $value }}" @selected((string) $value === (string) ($selected ?? ''))>{{ $label }}</option>
@endforeach

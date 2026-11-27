{{-- Devuelve los datos del formulario (como el «POSTデータを全て返す» del legacy) --}}
@foreach ($data as $key => $value)
<input type="hidden" name="{{ $key }}" value="{{ $value }}" />
@endforeach

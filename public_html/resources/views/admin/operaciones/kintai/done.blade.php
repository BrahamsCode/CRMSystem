{{-- Legacy: kintai_new_up.php / kintai_update_up.php (pantalla de terminado con 「戻る」 a la lista del día).
     Variables: $title, $bodyClass, $message, $query (yy/mm/dd/shop) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', $title.' - '.($siteName ?? '').' - ')

@section('body_class', $bodyClass)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $title }}</p>


<p>{{ $message }}</p>


<form class="form" method="get" action="{{ route('admin.operaciones.kintai.index') }}">
<div class="btnBox">
<input type="submit" name="act" id="act" class="btnStyleM" value="　　戻る　　" />
<input type="hidden" name="yy" value="{{ $query['yy'] }}" />
<input type="hidden" name="mm" value="{{ $query['mm'] }}" />
<input type="hidden" name="dd" value="{{ $query['dd'] }}" />
<input type="hidden" name="shop" value="{{ $query['shop'] }}" />
<!-- □□ /.btnBox □□ --></div>
</form>


<!-- / .contents --></div>
@endsection

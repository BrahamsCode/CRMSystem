{{-- Legacy: pantallas intermedias de system/category3/reservation/ con meta Refresh:
     reserve_new_register.php (「登録中です。」 → reserve_new_complete.php), reserve_change_status.php y reserve_delete.php
     (「…中です。」 → reserve_list.php), category_register.php / category_delete.php (→ category_entry.php) y
     config_register.php (→ config.php).
     Variables:
       $title     string <title>
       $heading   string texto de p.h2Title (sin ■)
       $message   string texto bajo el gif
       $alt       string alt del gif
       $boxClass  'textCenter' (<p class="textCenter">, reserve_new_register) | 'center' (<div class="center"><p>)
       $seconds   int segundos del meta Refresh
       $url       string destino --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', $title)

@section('css')
<meta http-equiv="Refresh" content="{{ $seconds }}; URL={{ $url }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $heading }}</p>

@if ($boxClass === 'textCenter')
<p class="textCenter"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $alt }}" /><br />{{ $message }}</p>
@else
<div class="center"><p><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $alt }}" />
<br />{{ $message }}</p></div>
@endif

<!-- / .contents --></div>
@endsection

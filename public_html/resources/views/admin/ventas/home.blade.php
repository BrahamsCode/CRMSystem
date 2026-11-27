{{-- 販売管理トップ: system/category2/index.php --}}
@extends('layouts.legacy')

{{-- El legacy no define $base_title_system_array aquí (quedaba «トップ - …»): se usa el nombre del módulo, como category0 --}}
@section('title', $systemName . 'トップ - ' . $siteName)

@section('menu', 'ventas')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents cover"><img src="/system/img/cover2.gif" width="595" height="220" alt="{{ $systemName }}" />
<!-- / .contents --></div>

<div class="contents">


<!-- / .contents --></div>
@endsection

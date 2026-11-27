{{--
    Legacy: system/category2/index.php (販売管理トップ). Sin variables propias ($siteName la pone el núcleo).
    El legacy imprime $base_title_system_array['system_name'], que en category2 no se define: título 「トップ - …」 y alt vacío.
--}}
@extends('layouts.legacy')

@section('title', 'トップ - ' . $siteName)

@section('menu', 'ventas')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents cover"><img src="{{ asset('legacy/system/img/cover2.gif') }}" width="595" height="220" alt="" />
<!-- / .contents --></div>

<div class="contents">

<!-- / .contents --></div>
@endsection

{{-- 基本情報管理トップ: system/category0/index.php --}}
@extends('layouts.legacy')

@section('title', $systemName . 'トップ -総合業務管理システム-')

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents cover"><img src="/system/img/cover0.gif" width="595" height="220" alt="{{ $systemName }}" />
<!-- / .contents --></div>
@endsection

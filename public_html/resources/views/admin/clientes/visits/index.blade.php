{{-- Legacy: system/category1/customer/customer_search.php (来店処理).
     Variables:
       $errorMsg    string: error de validación ('' si no hay)
       $registered  bool|null: null = sin envío; true = visita registrada; false = no registrada (intervalo)
       $registId    string: 会員番号 enviado --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '来店処理 - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@section('css')
<style type="text/css">

input[name=regist],
input[name=regist_id]{
	font-size: 30px;
}

</style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function(){

	setTimeout(function() {
		$("input[name=regist_id]").focus();
	}, 0);

});
</script>
@endsection

@section('content')
<div class="contents pB20" style="overflow:hidden;">

<p class="h2Title">■来店処理</p>

@php $style = 'padding: 10px; margin-bottom: 10px; font-size: 14px;'; @endphp
@if ($errorMsg !== '')
<div style="{{ $style }}background: #ffcece;  color: #F00; border: 1px solid #F00;">{{ $errorMsg }}</div>
@elseif ($registered === true)
<div style="{{ $style }}background: #ccc6ff;  color: #0002ff; border: 1px solid #5859ff;">来店処理しました。</div>
@elseif ($registered === false)
<div style="{{ $style }}background: #ffcece;  color: #F00; border: 1px solid #F00;">来店処理できませんでした。インターバルなどをご確認ください。</div>
@endif

<form action="{{ route('admin.clientes.visits.store') }}" method="post" class="form">
	@csrf

	<small>会員番号</small><br>
	<input type="text" name="regist_id" value="{{ $registId }}" autofocus>
	<input type="submit" name="regist" value="登録">

</form>

<!-- / .contents --></div>
@endsection

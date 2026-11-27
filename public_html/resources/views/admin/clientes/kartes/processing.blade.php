{{-- Legacy: system/category1/inc/karute_register.php: pantalla 「登録中です。」 que vuelve a la ficha (pestaña カルテ,
     karute_cd de la カルテ registrada) con meta Refresh de 1 s.
     Variables:
       $popup      bool: ventana emergente (sin menú), versión inc/ del legacy
       $detailUrl  string: ficha a la que vuelve (kaiin_cd, tab, from=reg, karute_cd) --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('title', 'カルテ 新規登録')

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $detailUrl }}">
@endsection

@section('js')
@include('admin.clientes.partials.ac')

<script type="text/javascript">
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■カルテ 新規登録</p>



<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="登録中です。" />
<br />登録中です。</p>




<!-- / .contents --></div>
@endsection

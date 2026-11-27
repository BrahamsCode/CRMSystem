{{-- Legacy: system/category1/inc/kanren_record_register.php (「登録中です。」) y kanren_del_record.php (「削除中です。」):
     pantalla intermedia que vuelve a la ficha con meta Refresh de 1 s.
     Variables:
       $mode       'register' | 'delete'
       $popup      bool: ventana emergente (sin menú), versión inc/ del legacy
       $detailUrl  string: ficha a la que vuelve (tab, page, related_features_cd) --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客登録情報 - ' . config('app.name'))

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $detailUrl }}">
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■顧客登録情報</p>


<div class="center"><p><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="登録中です。" />
<br />{{ $mode === 'delete' ? '削除中です。' : '登録中です。' }}</p></div>


<!-- / .contents --></div>
@endsection

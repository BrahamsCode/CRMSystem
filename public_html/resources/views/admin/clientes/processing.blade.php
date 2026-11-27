{{-- Legacy: system/category1/inc/customer_delete_register.php (削除 / 復元) e inc/customer_card_delete.php (UID関連付け解除):
     pantalla intermedia con 「削除中です。」…, recarga la lista de la ventana padre y vuelve a la ficha (meta Refresh).
     Variables:
       $mode       'delete' | 'recover' | 'felica'
       $popup      bool: ventana emergente (sin menú), como la versión inc/ del legacy
       $detailUrl  string: ficha a la que vuelve --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('title', ($mode === 'felica' ? '顧客登録情報 UID関連付け解除' : '顧客登録情報 - ') . config('app.name'))

@section('css')
<meta http-equiv="Refresh" content="{{ $mode === 'felica' ? 2 : 1 }}; URL={{ $detailUrl }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	if (window.opener && !window.opener.closed) {
		var temp = window.opener.location.pathname;
		if (temp.search('customer_list.php') >= 0 || temp.search('/clientes') >= 0) window.opener.location.reload(true);
	}
});
</script>
@endsection

@section('content')
<div class="contents">
@if ($mode === 'delete')
<p class="h2Title">■顧客情報 削除</p>
<div class="center"><p><img src="/system/img/loading.gif" alt="削除中です。" />
<br />削除中です。</p></div>
@elseif ($mode === 'recover')
<p class="h2Title">■顧客情報 復元</p>
<div class="center"><p><img src="/system/img/loading.gif" alt="復元中です。" />
<br />復元中です。</p></div>
@else
<p class="h2Title">■顧客登録情報 UID関連付け解除</p>
<div class="center"><p><img src="/system/img/loading.gif" alt="解除中です。" />
<br />解除中です。</p></div>
@endif
<!-- / .contents --></div>
@endsection

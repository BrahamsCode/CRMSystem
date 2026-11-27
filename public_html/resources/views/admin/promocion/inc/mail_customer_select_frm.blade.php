{{--
	発行条件選択 (振分配信条件抽出) común a todas las familias de envío
	— legacy: system/category3/inc/mail_customer_select_frm.php
	(envoltorios: mail_mag|decomail|auto|follow|reminder|push/*_customer_select.php,
	 anketo/anketo_form_issue.php, mycoupon/mycoupon_mail_customer_select.php)

	Variables:
	  $menu, $mailType, $title, $mode, $activeAc       las del envoltorio legacy
	  $selectAction                                    URL de esta misma pantalla (form_search)
	  $issueAction                                     URL del paso siguiente (*_new o push_new)
	  $searchFormPartial                               parcial de S2 con los bloques de búsqueda de clientes
	                                                   (search_set1, カスタマイズ検索, search_rireki_set1,
	                                                    search_kaikei_set1, search_method_set1); POS fuera de alcance
	  $searchUniqid, $result (null o counts: total, pc, docomo, au, softbank, other)
	  $carry                                           campos ocultos que se arrastran (coupon_cd, hakkou_cd, back_flg…)
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title . ' ' . $mode . ' - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/css/popupwindow.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/category3/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
	//$active_ac: mailmag=1, anketo=8, mycoupon=7
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8.js') }}"></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■振分配信条件抽出</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ $selectAction }}">
@csrf

@include($searchFormPartial)

<div class="btnBox">
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="se_flg" value="1" />
@foreach ($carry as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
<input type="submit" name="enter" class="btn btnStyleM" value="この条件で検索する" />
</div>

</form>

@if ($result)
<div class="btnBox">
<span class="text3L attention">{{ $result['total'] }}</span>件見つかりました。<br />
パソコン：<span class="text3L attention">{{ $result['pc'] }}</span>件　DoCoMo：<span class="text3L attention">{{ $result['docomo'] }}</span>件　AU：<span class="text3L attention">{{ $result['au'] }}</span>件　Softbank：<span class="text3L attention">{{ $result['softbank'] }}</span>件　その他：<span class="text3L attention">{{ $result['other'] }}</span>件
</div>

<br />

	<div class="btnBox">
	<form name="form_issue" action="{{ $issueAction }}" method="post">
	@csrf
	@foreach ($carry as $name => $value)
	<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
	@endforeach
	<input type="hidden" name="act" value="up" />
	<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
	<input type="submit" name="enter" class="btn btnStyleM" value="この条件で配信する" />
	</form>
	&nbsp;&nbsp;
	@if ($result['total'] > 0)
	<input type="button" name="enter" class="btn btnStyleM" value="この条件の会員一覧を見る" onclick="new $pop('{{ route('admin.promocion.mail.customers', ['search_uniqid' => $searchUniqid]) }}', { type: 'iframe', title: '顧客一覧', width: 800, height: 650, windowmode:true, resize: true } ); return false;"/>
	@endif
	</div>

@endif

<!-- / .contents --></div>
@endsection

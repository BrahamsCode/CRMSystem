{{-- Legacy: system/category3/reservation/reserve_list.php (予約一覧 del día; shop_cd, dd 'Y/n/j', kensu, page).
     Variables:
       $rows           Collection SimpleReservation de la página (ORDER BY reserve_time DESC) con `customer_id` (tb_customer.id o null);
                       user_name con entidades HTML ({!! !!})
       $total          reservas del día
       $date           ?Carbon día (para el título)
       $kensu, $page, $maxPage
       $dd, $shopCd    filtros actuales (no master: la tienda de la sesión)
       $searchParams   dd / shop_cd / kensu de los enlaces de página y del detalle
       $isTablet       bool: terminal CRMRW107 / CRMH580VT_A (roid.popUp)
       $canSeeCustomer bool: permiso 1001-1 o master (botón 顧客情報)
       $siteName       nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	$('body, html').animate({ scrollTop: 0 }, 0);
});
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8.js') }}"></script>

<script type="text/javascript">
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@php
    $pageUrl = fn (int $p) => route('admin.reservas.simple.index', ['page' => $p] + $searchParams);
    $statusIcons = [
        '1' => ['icon_reserve1.gif', '仮予約'],
        '2' => ['icon_reserve2.gif', '予約確定'],
        '9' => ['icon_reserve9.gif', '取消済'],
    ];
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■予約一覧 ({{ $date?->format('Y/m/d') }})</p>

<p class="maxListLength">表示件数<select name="kensu" id="kensu" class="selectBox" onchange="kensuSet(this)"><option value="" ></option>
@for ($i = 1; $i <= 6; $i++)
<option value="{{ $i * 5 }}" @selected($kensu == $i * 5)>{{ $i * 5 }}</option>
@endfor
</select></p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.reservas.simple.index') }}">
@csrf

<div class="btnBox">
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="dd" value="{{ $dd }}" />
<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
</div>

</form>

@if ($rows->isNotEmpty())
<p><span class="attention">{{ $total }}件</span>の該当結果がありました。</p>

<table class="tableBox2">
  <tr>
    <th>会員ID</th>
    <th>名前</th>
    <th>予約日時</th>
    <th style="width: 7em;">来店状況</th>
    <th style="width: 7em;">予約状況</th>
    <th style="width: 11em;">&nbsp;</th>
  </tr>

@foreach ($rows as $row)
@php
    $status = $statusIcons[(string) $row->reserve_flg] ?? null;
    $popupUrl = url('system/category1/inc/customer_detail.php') . '?kaiin_cd=' . rawurlencode((string) $row->user_cd) . '&wintype=no_headers';
    $shousaiPopJs = $isTablet
        ? "roid.popUp('" . $popupUrl . "');"
        : "javascript:window.open('" . $popupUrl . "', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');";
@endphp
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>
@if ((string) $row->user_cd !== '' && $row->customer_id !== null)
{{ $row->customer_id }}
@else
非会員
@endif
    </td>
    <td>{!! $row->user_name !!}</td>
    <td>{{ $row->reservedAt()?->format('Y/m/d H:i') }}</td>
    <td align="center">@if ((string) $row->raiten_flg === '1')<img src="{{ asset('legacy/hp/inc_m/img/icon_reserve_raiten.gif') }}" height="13" alt="来店済" />@else　@endif</td>
    <td align="center">@if ($status)<img src="{{ asset('legacy/hp/inc_m/img/' . $status[0]) }}" height="13" alt="{{ $status[1] }}" />@endif</td>
    <td align="center">
    @if ($canSeeCustomer && (string) $row->user_cd !== '')<input type="button" value="顧客情報" onclick="{{ $shousaiPopJs }}" />@endif
    <input type="button" value="予約詳細" onclick="location.href='{{ route('admin.reservas.simple.show', ['reservation' => $row->cd, 'page' => $page] + $searchParams) }}';" />
    </td>
  </tr>
@endforeach
</table>
<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $pageUrl($page - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $pageUrl($page + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
現在予約はありません
@endif

<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ route('admin.reservas.simple.calendar') }}">
@csrf
<input type="image" name="reserve_list_btn" id="reserve_list_btn" src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" value="戻る" />
<input type="hidden" name="dd" id="dd" value="{{ $dd }}" />
<input type="hidden" name="se_flg" id="se_flg" value="1" />
<input type="hidden" name="act" id="act" value="up" />
</form>


<!-- / .contents --></div>
@endsection

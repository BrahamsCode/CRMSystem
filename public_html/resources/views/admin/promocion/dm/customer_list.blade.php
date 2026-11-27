{{--
	DM発行 — legacy: system/category3/dm/customer_list.php
	Variables: las de CustomerSearchForm::viewData() (parcial search_set1 de S2) + $kensu, $searchUniqid,
	$result (null o DmCustomerList::page(): total, maxPage, page, from, last, rows)
	La búsqueda viaja como `search_uniqid` (antes `wherestr` con SQL en la sesión y en el formulario).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'DM発行 - ' . $siteName)

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
{{-- El buscador de clientes (search_set1) usa datepicker y myFlexSelect (en el legacy venían de inc/head.php) --}}
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript">
//表示件数の制御
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

$(document).ready(function() {
	$(".ac3").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
@php
	$self = route('admin.promocion.dm.index');
	$pageUrl = fn ($p) => $self.'?'.http_build_query(['page' => $p, 'kensu' => $kensu, 'se_flg' => 1, 'act' => 'up', 'search_uniqid' => $searchUniqid]);
@endphp
<div class="contents">
<p class="h2Title">■DM発行顧客検索条件</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ $self }}">

@include('admin.clientes.partials.search_set1')

<div class="btnBox">
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="submit" name="enter" id="enter" class="btn" value="この条件で検索する" />
</div>

</form>

@if ($result !== null)
<table class="tableBox7">
<thead>
<tr>
<th colspan="10"><span class="text3L attention">{{ $result['total'] }}</span>件見つかりました。

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.dm.labels') }}" target="_blank" style="display: inline;">
<input type="submit" name="dm_submit" id="dm_submit" value="この内容で宛名出力する" />
<input type="hidden" name="search_uniqid" id="search_uniqid" value="{{ $searchUniqid }}" />
</form>

</th>
</tr>
</thead>


</table>

<!-- / .contents --></div>


<div class="contents">
<p class="h2Title">■顧客一覧</p>

@if ($result['total'] > 0)
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
<option value="{{ $kensu }}" selected>{{ $kensu }}</option>
<option value="" ></option>
@for ($i = 1; $i <= $result['maxPage']; $i++)
<option value="{{ $i * 10 }}" @selected($kensu === $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $result['from'] + 1 }}件～{{ $result['last'] }}件目/{{ $result['total'] }}件中</span></p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th></th>
    <th>ID</th>
    <th>名前<br />生年月日</th>
    <th>郵便番号<br />住所</th>
    <th>電話番号<br />メールアドレス</th>
    <th>メールマガジン配信有無</th>
  </tr>
@foreach ($result['rows'] as $n => $row)
@if ($n % 2 <> 0)
  <tr class="tableBg">
@else
  <tr>
@endif
<td>{{ $result['from'] + 1 + $n }}</td>
    <td>{{ $row->id }}</td>

    <td>{{ $row->name }}<br />
    @if ($row->kojin_birth)<small>{{ $row->kojin_birth }}</small>@else---@endif</td>

    <td>{{ $row->zip }}<br />{{ $row->add }}</td>

    <td>@if ($row->tel){{ $row->tel }}@else---@endif<br />
    {{ explode('@', (string) $row->email1)[0] }}@...</td>

    <td>{{ $row->mailmaga_flg == '1' ? '○' : '×' }}<br />送信エラー：{{ $row->error_send_cnt }}回</td>
  </tr>
@endforeach
</table>

</div>



@else
該当する顧客がみつかりません。
@endif



<ul class="btnBox2">
@if ($result['page'] != 1 && $kensu < $result['total'])
<li class="btn"><a href="{{ $pageUrl($result['page'] - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($result['maxPage'] > 1)
@for ($l = 1; $l <= $result['maxPage']; $l++)
@if ($l == $result['page'])
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($result['page'] < $result['maxPage'])
<li class="btn"><a href="{{ $pageUrl($result['page'] + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>

@endif

<!-- / .contents --></div>
@endsection

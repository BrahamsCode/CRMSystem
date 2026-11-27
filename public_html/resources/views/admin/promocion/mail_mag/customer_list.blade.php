{{--
	この条件の会員一覧を見る (popup) — legacy: system/category3/inc/customer_list.php
	Variables: $rows, $page, $maxPage, $total, $searchUniqid
--}}
@extends('layouts.legacy-popup')

@section('title', '顧客一覧')

@php
	$perPage = 20;
	$from = ($page - 1) * $perPage;
	$visitsEnabled = \Illuminate\Support\Facades\DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists();
	$staff = app(\App\Services\Promotions\StaffContext::class);
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■顧客一覧</p>
@if ($total > 0)
<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $perPage, $total) }}件目/{{ $total }}件中</span></p>
<div class="form">
<table class="tableBox2">
  <tr>
    <th></th>
    <th>会員番号</th>
    <th>管理番号</th>
    <th>名前</th>
@if ($visitsEnabled)
    <th>来店回数</th>
@endif
  </tr>
@foreach ($rows as $i => $row)
  <tr @if ($i % 2 !== 0) class="tableBg" @endif>
    <td>{{ $from + 1 + $i }}</td>
    <td>{{ $row->id }}</td>
    <td>{{ $row->kanri_id ?? '' }}</td>
    <td>{{ $row->name }}</td>
@if ($visitsEnabled)
    @php
        $visits = \Illuminate\Support\Facades\DB::table('tb_raiten_rireki')->where('user_cd', $row->cd)->whereRaw("coalesce(disp_flg,'') <> '9'")
            ->when(! $staff->isMaster(), fn ($q) => $q->where('shop_cd', $staff->shopCd()))->count();
    @endphp
    <td>@if ($visits > 0){{ $visits }} 回@endif</td>
@endif
  </tr>
@endforeach
</table>
</div>
<ul class="btnBox2">
@if ($page > 1)
<li class="btn"><a href="{{ route('admin.promocion.mail.customers', ['search_uniqid' => $searchUniqid, 'page' => $page - 1]) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l === $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ route('admin.promocion.mail.customers', ['search_uniqid' => $searchUniqid, 'page' => $l]) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ route('admin.promocion.mail.customers', ['search_uniqid' => $searchUniqid, 'page' => $page + 1]) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
<p>データが見つかりませんでした</p>
@endif
<!-- / .contents --></div>
@endsection

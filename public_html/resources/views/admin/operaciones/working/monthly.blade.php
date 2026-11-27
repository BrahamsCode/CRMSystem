{{-- Legacy: system/category5/working/working_list.php (スタッフ一覧). El menú del legacy la tiene comentada.
     Cambios: 休憩回数 cuenta los 外出 reales (el legacy daba 1回 sin ninguno) y 労働時間 usa el redondeo de 15 min
     que el legacy no podía calcular (error fatal en PHP 8). Ver WorkingTotals.
     Variables: $screen, $kensu, $page, $maxPage, $total, $from, $staff, $days [cd => WorkingTotals::dayRow()], $day,
       $fields, $words, $shops, $belongposts --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ一覧 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
<script type="text/javascript">
//表示件数の制御
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.act.value = "kensuSet";
	document.form_search.submit();
}
</script>
@endsection

@section('content')
@include('admin.operaciones.working.partials.summary-search-form')

<div class="contents">
<p class="h2Title">■スタッフ一覧</p>
<div class="form">
@if ($total > 0)

@include('admin.operaciones.working.partials.summary-pager', ['part' => 'kensu'])


<p><span class="attention">{{ $from + 1 }}件～{{ $from + $kensu }}件目/{{ $total }}件中</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $day }}日</p>

<table class="tableBox2">
  <tr>
    <th>スタッフID</th>
    <th>名前</th>
    <th>出勤時間</th>
    <th>退勤時間</th>
    <th>労働時間</th>
    <th>休憩回数</th>
    <th>出勤状況</th>
    <th>出勤区分</th>
    <th>&nbsp;</th>
  </tr>
@foreach ($staff as $i => $member)
@php($d = $days[$member->cd])
@if ($i % 2 !== 0)
	  <tr class="tableBg">
@else
	  <tr>
@endif
    <td>{{ $member->id }}</td>
    <td><span title="{{ $member->name_kana }}">{{ $member->name }}</span></td>
    <td>{{ $d['in'] }}</td>
    <td>{{ $d['out'] }}</td>
    <td>@if ($d['in'] !== null){{ $d['hours'] }}時間{{ $d['mins'] }}分@endif</td>
    <td>@if ($d['in'] !== null){{ $d['breaks'] }}回@endif</td>
    <td>@if ($d['in'] !== null){{ $d['flag'] }}@else<font color="#FF7700">休</font>@endif</td>
    <td>{{ $d['kubun'] ?: '' }}</td>
    <td>編集</td>
  </tr>
@endforeach

</table>
@else
検索条件に合ったスタッフは、見つかりませんでした。再度検索してください
@endif

@include('admin.operaciones.working.partials.summary-pager', ['part' => 'links'])


</div>

<!-- / .contents --></div>
@endsection

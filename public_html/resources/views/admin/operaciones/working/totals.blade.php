{{-- Legacy: system/category5/working/working_total_list.php (スタッフ勤怠集計一覧). El menú del legacy la tiene comentada.
     Las celdas son las del legacy salvo 今月の出勤時間, que aquí suma 労働時間 (el legacy repetía los días):
     普通残業 = 欠勤日数, 休日残業 = 外出 del día y 休日出勤 = 帰社 del día, tal cual (pendiente de Antony).
     Variables: $screen, $kensu, $page, $maxPage, $total, $from, $staff, $totals [cd => WorkingTotals::month()],
       $fields, $words, $shops, $belongposts --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ勤怠集計一覧 - '.($siteName ?? ''))

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
<p class="h2Title">■スタッフ勤怠集計一覧</p>
<div class="form">
@if ($total > 0)

@include('admin.operaciones.working.partials.summary-pager', ['part' => 'kensu'])


<p><span class="attention">{{ $from + 1 }}件～{{ $from + $kensu }}件目/{{ $total }}件中</span></p>

<table class="tableBox2">
  <tr>
    <th>スタッフID</th>
    <th>名前</th>
    <th>今月の出勤日数</th>
    <th>今月の出勤時間</th>
<!--    <th>欠勤日数</th>-->
    <th>今月の普通残業時間</th>
    <th>今月の休日残業時間</th>
    <th>今月の休日出勤時間</th>
  </tr>
@foreach ($staff as $i => $member)
@php($t = $totals[$member->cd])
@if ($i % 2 !== 0)
  <tr class="tableBg">
@else
  <tr>
@endif

    <td>{{ $member->id }}</td>
    <td><span title="{{ $member->name_kana }}">{{ $member->name }}</span></td>
    <td>{{ $t['attendanceDays'] }}</td>
    <td>{{ intdiv($t['workMinutes'], 60) }}時間{{ $t['workMinutes'] % 60 }}分</td>
    <td>{{ $t['absenceDays'] }}</td>
    <td>{{ $t['outTime'] }}</td>
    <td>{{ $t['backTime'] }}</td>
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

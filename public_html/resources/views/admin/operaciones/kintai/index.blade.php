{{-- Legacy: system/category5/kintai/index.php (日付別勤怠データ一覧). Mismo HTML; jquery.alerts para jConfirm
     (en la UI clásica el legacy no lo cargaba y 削除 fallaba). Cambios: textos escapados, 削除 por POST con CSRF,
     sin el botón Flash del cliente AIR, ステータス sin las marcas borradas.
     Variables: $isMaster, $shopCd, $shopName, $shops, $yy/$mm/$dd (int), $day (CarbonImmutable),
     $entries (KintaiService::dayEntries + 'current'), $query (yy/mm/dd/shop de los enlaces) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '日付別勤怠データ一覧 - '.($siteName ?? '').' - ')

@section('body_class', 'kintaiList')

@section('css')
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />
<style type="text/css">
<!--

.nameColumn { width: 16%; }
.dataColumn { width: 11%; }
.timeColumn { width: 10%; }
.btnClumn { width: 8.5%; }

-->
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});

// =============================================================================
// レコード編集
// =============================================================================

function recordUpdate( url ){

	location.href = url;

} // END --- function recordUpdate( url )

// =============================================================================
// レコード削除
// =============================================================================

function recordDelete( url, staffCd ){

	jConfirm("この勤怠データを削除します。\nよろしいですか？", "削除確認", function(ret){

		if(ret == false){
			return false;
		}else{

			var form = document.createElement('form');
			form.method = 'post';
			form.action = url;
			var fields = { _method: 'DELETE', staff: staffCd, yy: '{{ $query['yy'] }}', mm: '{{ $query['mm'] }}', dd: '{{ $query['dd'] }}', shop: '{{ $query['shop'] }}' };
			for (var name in fields) {
				var input = document.createElement('input');
				input.type = 'hidden'; input.name = name; input.value = fields[name];
				form.appendChild(input);
			}
			document.body.appendChild(form);
			form.submit();

		} // if(ret == false) --- END

	});

} // END --- function recordDelete( url, staffCd )
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■日付別勤怠データ一覧</p>


<form class="form" method="post" action="{{ route('admin.operaciones.kintai.index') }}">
@csrf
<div class="dateSelectArea">

<fieldset>
<legend>勤怠データ検索条件</legend>

<table class="tableBox">

<tr>
<th>店舗選択</th>
<td>

@if (! $isMaster)
{{ $shopName }}<input type="hidden" name="shop" id="shop" value="{{ $shopCd }}" />
@else
<select name="shop" id="shop">
@include('admin.operaciones.partials.options', ['placeholder' => '▼店舗選択', 'options' => $shops, 'selected' => $shopCd])
</select>
@endif

</td>
</tr>

<tr>
<th>勤怠データ表示日付選択</th>
<td>

<span class="inputArea">
@include('admin.operaciones.kintai.partials.date-selects')
</span>

</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
<input type="submit" name="act" id="act" class="btnStyleM" value="この条件で検索" />
</div>

<!--■■ /.dateSelectArea ■■--></div>
</form>

<!-- / .contents --></div>





<div class="contents">

<p class="h2Title">■<span>{{ $day->format('Y/m/d') }}</span> 勤怠データ</p>

<div class="linkList pB10">
<a href="{{ route('admin.operaciones.kintai.create', $query) }}" class="btnStyle" style="padding:5px 3px;">　勤怠データを追加する　</a>&nbsp;
<a href="{{ route('admin.operaciones.kintai.csv.daily', $query) }}" class="btnStyle" style="padding:5px 3px;">　CSVとして出力する　</a>
</div>

<table id="scheduleTbl" class="tableBox2">

<tr>
<th align="center" valign="middle" class="nameColumn">名前</th>
<th align="center" valign="middle" class="dataColumn">勤務状況</th>
<th align="center" valign="middle" class="dataColumn">ステータス</th>
<th align="center" valign="middle" class="timeColumn">出勤</th>
<th align="center" valign="middle" class="timeColumn">外出</th>
<th align="center" valign="middle" class="timeColumn">戻り</th>
<th align="center" valign="middle" class="timeColumn">退勤</th>
<th align="center" valign="middle" class="otherColumn">備考</th>
<th align="center" valign="middle" class="btnClumn">&nbsp;</th>
</tr>

@forelse ($entries as $i => $entry)
@php
    $times = fn (string $flag) => array_map(fn ($r) => \App\Services\Operaciones\KintaiService::displayTime((int) $r->kintai_time, $day->day), $entry['records'][$flag] ?? []);
@endphp
<tr{!! $i % 2 !== 0 ? ' class="tableBg"' : '' !!}>

<td align="center" valign="middle">{{ $entry['name'] }}</td>
<td align="center" valign="middle">{{ $entry['status'] }}</td>

@if (\App\Services\Operaciones\KintaiService::isRestDay($entry['status']))
<td align="center" valign="middle" colspan="5">---</td>
@else
<td align="center" valign="middle">{{ $entry['current'] }}</td>

<td align="center" valign="middle">
{{ implode('', $times('1')) }}
</td>

<td align="center" valign="top">
@foreach ($times('3') as $time){{ $time }}<br />
@endforeach
</td>

<td align="center" valign="top">
@foreach ($times('4') as $time){{ $time }}<br />
@endforeach
</td>

<td align="center" valign="middle">
@foreach ($times('2') as $time){{ $time }}<br />
@endforeach
</td>
@endif

<td align="left" valign="middle">{!! nl2br(e($entry['bikou'])) !!}</td>

<td align="center" valign="middle">
@if ($isMaster)
<input type="button" id="update_{{ $entry['staff_cd'] }}" class="btnStyle" name="update_{{ $entry['staff_cd'] }}" value="編集" onclick="recordUpdate('{{ route('admin.operaciones.kintai.edit', ['hiduke' => $entry['hiduke_cd'], 'staff' => $entry['staff_cd']] + $query) }}');" />
<input type="button" id="delete_{{ $entry['staff_cd'] }}" class="btnStyle" name="delete_{{ $entry['staff_cd'] }}" value="削除" onclick="recordDelete('{{ route('admin.operaciones.kintai.destroy', $entry['hiduke_cd']) }}','{{ $entry['staff_cd'] }}');" />
@endif
</td>

</tr>

@empty
<tr>
<td align="center" valign="middle" colspan="9">勤怠データがありません。</td>
</tr>

@endforelse
<!--■■ /#scheduleTbl ■■--></table>

<!-- / .contents --></div>
@endsection

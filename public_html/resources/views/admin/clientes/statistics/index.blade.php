{{--
    Legacy: system/category1/customer/customer_sumup_list.php (+ inc/search_set1_sumup.php). Ventana emergente de 集計ツール.
    Variables: $total (breakdown() de todos los socios), $years (lista [label, stats] de los últimos 5 años),
    $searched (bool: act=up), $kind (種別: '業種別' | '県別' | ''), $result (breakdown() del 検索結果 o null),
    $groups (lista [label, stats] por prefectura u ocupación), $input (sumup_kind, touroku_date_1, touroku_date_2, kensu, coupon_cd).
--}}
@extends('layouts.legacy-popup')

@section('title', '顧客一覧・検索 - ' . $siteName)

@section('js')
<script type="text/javascript">


//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}


</script>
@endsection

@section('css')
<style type="text/css"><!--

.main1 {
	background: none;
}

.perGraphField {
	width: 600px;
	padding: 0;
	background: #CCCCCC;
	text-align: left;
}

.perGraphField li {
	display: -moz-inline-box;
	display: inline-block;
	*display: inline;
	*zoom: 1;
	height: 1.7em;
	overflow: hidden;
	text-align: center;
}

.perGraphField li span {
	display: block;
}



.perGraphField_s {
	width: 100px;
	padding: 0;
	background: #CCCCCC;
	text-align: left;
}
.perGraphField_s li {
	display: -moz-inline-box;
	display: inline-block;
	*display: inline;
	*zoom: 1;
	height: 1.7em;
	overflow: hidden;
	text-align: center;
}

.perGraphField_s li span {
	display: block;
}
.colorExplainField_s {
	padding: 0;
}



.perGraphFieldTate {
	width: 4em;
	height: 600px;
	padding: 0;
	background: #CCCCCC;
	text-align: left;
	display: -moz-inline-box;
	display: inline-block;
	*display: inline;
	*zoom: 1;
	overflow: hidden;
}

.perGraphFieldTate li {
	display: block;
	overflow: hidden;
	text-align: center;
}

.perGraphFieldTate li span {
	display: block;
}




.colorExplainField {
	margin-bottom: 10px;
	padding: 0;
}

.colorExplain {
	display : inline;
	margin-right: 10px;
	padding: 0;
	vertical-align: middle;
}

.colorExplain_s {
	display: -moz-inline-box;
	display: inline-block;
	*display: inline;
	*zoom: 1;
	width: 100%;
	padding: 0;
	vertical-align: middle;
}

.colorExplain span,
.colorExplain_s span {
	display: -moz-inline-box;
	display: inline-block;
	*display: inline;
	*zoom: 1;
	margin: 0 3px 5px 0;
	width: 1em;
}



.contents tr{
	vertical-align:top;
}


.graArea {
	margin-bottom: 10px;
}

.h4Title {
	margin-bottom: 0;
	font-size: 125%;
}

.h4Title span {
	color: #113366;
	font-weight: bold;
	font-size: 120%;
}

.tableBox .h4Title {
	margin-bottom: 0;
	font-size: 115%;
}

.tableBox .h4Title span {
	color: #113366;
	font-weight: bold;
	font-size: 110%;
}

.tableTitleHiduke {
	margin: 0;
	font-weight: bold;
}

--></style>
@endsection

@php
    // Colores del legacy por bloque (10代…80代)
    $maleColors = ['#D5EAFF', '#C0DDFB', '#ACD2FF', '#96C9FF', '#82BBFF', '#6BB3FD', '#52A8FD', '#4F9BEF'];
    $femaleColors = ['#FFD5D7', '#FFBEC4', '#FBACAF', '#FF949A', '#FF808B', '#FA6C78', '#F65A5D', '#EE5253'];
    $resultMaleLegend = ['#CCFFFF', '#D5EAFF', '#C0DDFB', '#ACD2FF', '#96C9FF', '#82BBFF', '#6BB3FD', '#52A8FD'];
    $resultFemaleColors = ['#FFD5D7', '#FFCCCC', '#FFD5D7', '#FFBEC4', '#FBACAF', '#FF949A', '#FF808B', '#FA6C78'];
    $prefFemaleColors = ['#FFD5D7', '#FFCCCC', '#FFBEC4', '#FBACAF', '#FF949A', '#FF808B', '#FA6C78', '#F65A5D'];
@endphp

@section('content')
<div class="contents">

<p class="h2Title">■顧客情報集計</p>


<p class="h3Title">顧客数総計</p>

<div class="graArea">
<p class="h4Title">■総数：<span>{{ $total['all'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.sex-bar', ['stats' => $total, 'small' => false])

</div>

<div class="graArea">
<p class="h4Title">■男性：<span>{{ $total['male'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-bar', ['ages' => $total['male_ages'], 'barColors' => $maleColors, 'legendColors' => $maleColors])

</div>

<div class="graArea">
<p class="h4Title">■女性：<span>{{ $total['female'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-bar', ['ages' => $total['female_ages'], 'barColors' => $femaleColors, 'legendColors' => $femaleColors])

</div>


<!--.contents--></div>


<div class="contents">

<p class="h3Title">過去5年の年間登録者数</p>

<table class="tableBox">
<tr>
@foreach ($years as $year)
<td width="20%">
<p class="tableTitleHiduke">{{ $year['label'] }}</p>

@include('admin.clientes.statistics.partials.cell', ['stats' => $year['stats'], 'totalLabel' => '総数', 'maleColors' => $maleColors, 'femaleColors' => $femaleColors])

</td>
@endforeach
</tr>
</table>

</div>

<div id="searchResult" class="contents">

<p class="h2Title">■顧客検索条件</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.clientes.statistics') }}#searchResult">
@csrf

<fieldset>
<legend>顧客情報項目</legend>

<table class="tableBox">
	<tr>
	<th>

		<small>種別</small><br />
		<select size="1" name="sumup_kind" class="selectBox">
		@if ($input['sumup_kind'] !== '')
		<option value="{{ $input['sumup_kind'] }}">{{ $input['sumup_kind'] }}</option>
		@endif
		<option value="業種別">業種別</option>
		<option value="県別">県別</option>
		</select>


	</th>

	<th>
		<small></small><br />
			<input type="text" name="touroku_date_1" id="touroku_date_1" value="{{ $input['touroku_date_1'] }}" class="inputS" /> ～

		<select size="1" name="touroku_date_2" class="selectBox">
		@if ($input['touroku_date_2'] !== '')
		<option value="{{ $input['touroku_date_2'] }}">{{ $input['touroku_date_2'] }}</option>
		@endif
		<option value="">&nbsp;</option>
		<option value="一週間">一週間</option>
		<option value="一ヶ月間">一ヶ月間</option>
		</select>

<br />例)2009/01/01　～　一週間

	</th>
	</tr>

</table>
</fieldset>
<!--基本情報table end-->

<div class="btnBox">
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $input['kensu'] }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="hidden" name="coupon_cd" value="{{ $input['coupon_cd'] }}" />
<input type="submit" name="enter" id="enter" class="btn" value="この条件で検索する" />
</div>

</form>

<!--.contents--></div>

@if ($searched && $result)

<div class="contents">

<p class="h3Title">検索結果</p>

<p class="h4Title">■検索ヒット：<span>{{ $result['all'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.sex-bar', ['stats' => $result, 'small' => false])

<p class="h4Title">■男性：<span>{{ $result['male'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-bar', ['ages' => $result['male_ages'], 'barColors' => $maleColors, 'legendColors' => $resultMaleLegend])

<p class="h4Title">■女性：<span>{{ $result['female'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-bar', ['ages' => $result['female_ages'], 'barColors' => $resultFemaleColors, 'legendColors' => $resultFemaleColors])

<!--.contents--></div>


<div class="contents">

<p class="h3Title">{{ $kind }}</p>

@if (count($groups) > 0)
<table class="tableBox">
@foreach (array_chunk($groups, 5) as $row)
<tr>
@foreach ($row as $group)
<td width="20%">
<strong>【{{ $group['label'] }}】</strong><br />

@include('admin.clientes.statistics.partials.cell', ['stats' => $group['stats'], 'totalLabel' => '■計', 'maleColors' => $maleColors, 'femaleColors' => $kind === '県別' ? $prefFemaleColors : $resultFemaleColors])

</td>
@endforeach
@for ($i = count($row); $i < 5; $i++)
<td></td>
@endfor
</tr>
@endforeach
</table>
@endif

<!--.contents--></div>

@endif
@endsection

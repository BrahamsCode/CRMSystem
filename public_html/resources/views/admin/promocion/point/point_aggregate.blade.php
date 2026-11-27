{{--
	ポイント集計 — legacy: system/category3/point/point_aggregate.php
	Mismo HTML y JS en línea; el AJAX va a la URL literal del legacy (ajax_point_aggregate.php), con el CSRF del layout.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'ポイント集計 - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" rel="stylesheet" type="text/css" />
<style>
#result .tableWrapper {
	min-width: 660px;
	overflow-x: auto;
	overflow-y: hidden;
}

#result table {
	width: auto;
}

#result table th {
	font-size: 11px;
	min-width: 80px;
	text-align: center;
	width: 80px;
}
#result table td {
	font-size: 11px;
}
</style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript">
<!--
$(document).ready(function() {

	$(".ac13").accordion('option', 'active', 0);

	$("#enter").click(function(){
		getResults();
	});

	$("#startDate").datepicker({
		onSelect: function( selectedDate ) {
			var date = $.datepicker.parseDate("yy/mm/dd", selectedDate);
			$( "#endDate" ).datepicker( "option", "minDate", date );
		}
	});

	$("#endDate").datepicker({
		onSelect: function( selectedDate ) {
			var date = $.datepicker.parseDate("yy/mm/dd", selectedDate);
			$( "#startDate" ).datepicker( "option", "maxDate", date );
		}
	});

	$('#clearBtn').click(function(){
		$('#startDate, #endDate').val('');
	})

});

function getResults() {
	var sendData = $('#form1').serialize();
	$('#result').empty();
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "{{ route('legacy.promocion.point.ajax_point_aggregate') }}",
		data: sendData,
		success: function(resp){
			if ( resp.status == "ok" ) {
				renderAggregate(resp.data);
			} else {
				var errorMsg = (resp.errorMsg) ? resp.errorMsg : '予期しないエラーが発生しました。';
				alert(errorMsg);
			}
		},
		error: function() {
			alert('Error');
		}
	});
}

function renderAggregate(data) {

	var innerHtml = '';

	aggData = data.result;
	innerHtml += '<hr style="border: 1px solid #cccccc;">';
	innerHtml += '<p class="MT10 MB10">集計期間： ';
	if (data.startDate == '' && data.endDate == '') innerHtml += '未設定';
	else innerHtml += data.startDate + ' ～ ' + data.endDate;
	innerHtml += '</p>';

	if ( aggData && typeof(aggData) == 'object' && aggData.length > 0 ) {

		// ----------------------------------------------------------
		// 付与ポイント
		var grandTotal = 0;
		var totals = new Array(0,0,0,0,0,0,0,0,0,0);
		var acquireTable = '';

		acquireTable += '<p class="h3Title">付与ポイント</p>';
		acquireTable += '<div class="tableWrapper MB30">';
		acquireTable += '<table class="tableBox2" style="table-layout:fixed;">';
		acquireTable += '<tr>';
		acquireTable += '<th style="min-width:120px;max-width:none;">店舗名</th>';
		acquireTable += '<th>会員登録(CRM)</th>';
		acquireTable += '<th>会員登録(マイコレ)</th>';
		acquireTable += '<th>会員登録(紹介)</th>';
		acquireTable += '<th>来店</th>';
		acquireTable += '<th>紹介</th>';
		acquireTable += '<th>毎日ログイン</th>';
		acquireTable += '<th>プッシュ</th>';
		acquireTable += '<th>会計</th>';
		acquireTable += '<th>手動調整</th>';
		acquireTable += '<th>その他</th>';
		acquireTable += '<th>小計</th>';
		acquireTable += '</tr>';

		for ( var i = 0; i < aggData.length; i++ ) {
			var shopTotal = 0;
			var t = new Array(0,0,0,0,0,0,0,0,0,0); // デフォルト値
			var shopName = aggData[i].name;

			acquireTable += '<tr>';
			acquireTable += '<td>' + shopName + '</td>';
			if ( aggData[i].point ) {
				if ( aggData[i].point.acquire ) {
					for ( key in aggData[i].point.acquire ) {
						var qty = aggData[i].point.acquire[key];
						if ( key == 'crm:newRegist' ) t[0] += qty;
						else if ( key == 'mycolle:newRegist' ) t[1] += qty;
						else if ( key == 'mycolle:signUp' ) t[2] += qty;
						else if ( key == 'mycolle:checkIn' ) t[3] += qty;
						else if ( key == 'mycolle:introduce' ) t[4] += qty;
						else if ( key == 'mycolle:dailyLogin' ) t[5] += qty;
						else if ( key == 'mycolle:push' ) t[6] += qty;
						else if ( key == 'accounting' ) t[7] += qty;
						else if ( key == 'adjust' ) t[8] += qty;
						else t[9] += qty; // その他

						shopTotal += qty;
					}
				}
			}

			for ( k in t ) {
				totals[k] += t[k];
				grandTotal += t[k];
				acquireTable += '<td style="text-align:right;">' + numberFormat(t[k]) + '</td>';
			}

			acquireTable += '<td style="text-align:right;">' + numberFormat(shopTotal) + '</td>';
			acquireTable += '</tr>';
		}

		acquireTable += '<tr>';
		acquireTable += '<td>合計</td>';
		for ( k in totals ) {
			acquireTable += '<td style="text-align:right;">' + numberFormat(totals[k]) + '</td>';
		}
		acquireTable += '<td style="text-align:right;">' + numberFormat(grandTotal) + '</td>';
		acquireTable += '</tr>';
		acquireTable += '</table>';
		acquireTable += '</div>';
		// ----------------------------------------------------------


		// ----------------------------------------------------------
		// 使用ポイント表
		var grandTotal = 0;
		var totals = new Array(0,0,0,0);
		var useTable = '';
		useTable += '<p class="h3Title">使用ポイント</p>';
		useTable += '<div class="tableWrapper">';
		useTable += '<table class="tableBox2" style="table-layout:fixed;">';
		useTable += '<tr>';
		useTable += '<th style="min-width:120px;max-width:none;">店舗名</th>';
		useTable += '<th>クーポン変換</th>';
		useTable += '<th>会計</th>';
		useTable += '<th>手動調整</th>';
		useTable += '<th>その他</th>';
		useTable += '<th>小計</th>';
		useTable += '</tr>';

		for ( var i = 0; i < aggData.length; i++ ) {
			var shopTotal = 0;
			var t = new Array(0,0,0,0); // デフォルト値
			var shopName = aggData[i].name;

			useTable += '<tr>';
			useTable += '<td>' + shopName + '</td>';
			if ( aggData[i].point ) {
				if ( aggData[i].point.use ) {
					for ( key in aggData[i].point.use ) {
						var qty = aggData[i].point.use[key];
						if ( key == 'mycolle:couponChange' ) t[0] += qty;
						else if ( key == 'accounting' ) t[1] += qty;
						else if ( key == 'adjust' ) t[2] += qty;
						else t[3] += qty; // その他

						shopTotal += qty;
					}
				}
			}

			for ( k in t ) {
				totals[k] += t[k];
				grandTotal += t[k];
				useTable += '<td style="text-align:right;">' + numberFormat(t[k]) + '</td>';
			}

			useTable += '<td style="text-align:right;">' + numberFormat(shopTotal) + '</td>';
			useTable += '</tr>';
		}

		useTable += '<tr>';
		useTable += '<td>合計</td>';
		for ( k in totals ) {
			useTable += '<td style="text-align:right;">' + numberFormat(totals[k]) + '</td>';
		}
		useTable += '<td style="text-align:right;">' + numberFormat(grandTotal) + '</td>';
		useTable += '</tr>';
		useTable += '</table>';
		useTable += '</div>';
		// ----------------------------------------------------------


		innerHtml += acquireTable + useTable;
	} else {
		innerHtml += '<p>該当データがありません。</p>';
	}

	$('#result').html(innerHtml);
}
-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■ポイント集計</p>

<form method="post" name="form1" id="form1" class="form MB20">


<fieldset>

<table class="tableBox">
	<tr>
		<th><label for="data1">集計期間</label></th>
		<td>
			<input type="text" name="startDate" value="" id="startDate" class="inputM" />&nbsp;～&nbsp;<input type="text" name="endDate" value="" id="endDate" class="inputM" />&emsp;
			<input type="button" id="clearBtn" class="btn btnStyleM" style="font-size: 14px;" value="クリア" />
			<br />例)2012/01/01～2012/02/29
		</td>
	</tr>
</table>

</fieldset>

<div class="btnBox">
<input type="button" name="enter" id="enter" class="btn btnStyleM" value="検索する" />
</div>

</form>

<div id="result">
</div>


<!-- / .contents --></div>
@endsection

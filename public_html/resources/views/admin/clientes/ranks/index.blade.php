{{-- Legacy: system/category1/rank/customer_rank_list.php (顧客ランク設定).
     Variables:
       $siteName        nombre del sitio (<title>)
       $types           list<string> tipos activos (tb_base_system_grandchild 顧客ランク%): 'kingaku' y/o 'raiten'
       $ranks           ['kingaku' => Collection, 'raiten' => Collection] rangos disp_flg = '1' por rank, cd:
                        cd, name, jyouken1, jyouken2, hyouji_flg
       $dispFlg         admin_master.customer_rank_disp_flg ('0' 両方, '1' 使用金額, '2' 来店回数, '9' 表示しない)
       $dispFlgUpdated  bool: se acaba de guardar el 表示設定 (mensaje 「表示設定を変更しました。」)
     JS: arrastrar y soltar y casilla 表示 → system/category1/rank/ajax_customer_rank_list.php (legacy.clientes.ranks-ajax);
     削除 → admin.clientes.ranks.destroy (el legacy lo hacía con un GET a la misma URL AJAX).
--}}
@php
    $rankNames = ['kingaku' => '使用金額', 'raiten' => '来店回数'];
    $rankUnits = ['kingaku' => '円', 'raiten' => '回'];
    $hasKingaku = in_array('kingaku', $types, true);
    $hasRaiten = in_array('raiten', $types, true);
@endphp
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '顧客ランク設定 - ' . $siteName)

@section('css')
<style type="text/css">
<!--

.ranlListHeader
{
	overflow: hidden;
}

.resultTxt
{
	position: absolute;
	overflow: hidden;
	color: #FF0000;
}

.dispFlgTbl,
.rankListUl
{
	margin-top: 2.5em;
}

.rankListUl li:nth-child(odd) .rankTbl
{
	background: #FFFFFF;
}

.rankListUl li:nth-child(even) .rankTbl
{
	background: #EFEFEF;
}

.rankTbl
{
	width: 100%;
	margin-top: 2px;
}

.rankTbl th,
.rankTbl td
{
	padding: 10px;
	border: 1px solid #CCCCCC;
	font-weight: normal;
}

.rankTbl .btnStyle
{
	display: inline-block;
	padding: 3px 10px;
}

.ui-state-highlight
{
	height: 5px;
}

.ui-state-highlight,
.ui-widget-content .ui-state-highlight
{
	border: 1px solid #EFDC77;
	background: #FBF9EE;
}

-->
</style>
@endsection

@section('js')
<script type="text/javascript">
<!--

var rankAjaxUrl = @json(route('legacy.clientes.ranks-ajax'));
var rankDestroyUrl = @json(route('admin.clientes.ranks.destroy', ['type' => '__TYPE__', 'cd' => '__CD__']));

// =================================================================================================
// 表示フラグ
// =================================================================================================

function checkHyouji( targetCd, rankType, thisObj ) {

	// El legacy usaba $(thisObj).attr('checked') (jQuery 1.3.2); el estado actual es thisObj.checked
	var checked = thisObj.checked ? 'true' : 'false';

	$.ajax({

		type: "POST",
		url: rankAjaxUrl,
		data: "type=hyouji&cd="+targetCd+"&ranktype="+rankType+"&checked="+checked,

		complete: function(){

			checked = null;

		}

	});

}

// =================================================================================================
// 削除
// =================================================================================================

function deleteBtn( targetCd, rankType ) {

	jConfirm("削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい","削除確認",
	function(ret){

		if (ret) {
			var f = document.getElementById('rank_delete_form');
			f.action = rankDestroyUrl.replace('__TYPE__', rankType).replace('__CD__', targetCd);
			f.submit();
		}

	});

}

// =================================================================================================
// 初期設定
// =================================================================================================

$(document).ready(function(){


	// 並び替え設定 ----------------------------------------------------------------------------

	var baseStr = @json(implode(',', $types));
	var rankKeyArr = baseStr.split(',');
	var rankReturn;

	for ( var i=0,imax=rankKeyArr.length; i<imax; i++ ) {

		sortSetting( rankKeyArr[i] );

	} // for ( var i=0,max=rankKeyArr.length; i<max; i++ ) --- END

	function sortSetting( key ) {

		var rankKey = key;

		$("#"+rankKey+"RankList").sortable({

			axis: 'y',
			containment: '.wrap',
			placeholder: 'ui-state-highlight',

			stop: function(event, ui) {

				rankReturn = "type=rank&ranktype="+rankKey;
				for( var x=0,xmax=$("#"+rankKey+"RankList .ui-state-default").length; x<xmax; x++ ){
					rankReturn += "&r_"+$("#"+rankKey+"RankList .ui-state-default:eq("+x+")").attr('cd')+"="+(x+1);
				}

				$.ajax({

					type: "POST",
					url: rankAjaxUrl,
					data: rankReturn,
					success: function(data){

						$("#"+rankKey+"RankResult").empty().html('<div class="tsuchiArea">表示順を更新しました。</div>');
						$(".tsuchiArea").animate({ opacity: "toggle" }, 1500 , "easeInQuart");

						reloadRankNum(rankKey);

					}

				});

			}

		});

	} // function sortSetting( key ) --- END

	function reloadRankNum( rankType ) {

		$('#'+rankType+'RankList th').each(function(index){

			$(this).empty().html(index+1);

		});

	} // function reloadRankNum( rankType ) --- END


	// ランク表示設定変更 ----------------------------------------------------------------------

	@if ($dispFlgUpdated)
	$("#dispFlgResult").empty().html('表示設定を変更しました。').animate({ opacity: "toggle" }, 1500 , "easeInQuart");
	@endif

});

// =================================================================================================


-->
</script>
@endsection

@section('content')
<div class="contents pB20" style="overflow:hidden;">

<p class="h2Title">■マイページへのランク表示設定</p>

<form method="post" action="{{ route('admin.clientes.ranks.display') }}" class="form">
@csrf

<p style="padding-top:17px;">顧客用マイページに現在の各ランクを表示するかどうかを選択します。</p>
<div id="dispFlgResult" class="resultTxt">&nbsp;</div>

<table class="tableBox dispFlgTbl">
<tr>

<td>
<ul class="textBox mL5">
@if ($hasKingaku && $hasRaiten)
<li><input type="radio" value="0" class="checkBtn" id="rank_disp0" name="customer_rank_disp_flg" @checked($dispFlg == '0')><label for="rank_disp0">両方表示する</label></li>
@endif
@if ($hasKingaku)
<li><input type="radio" value="1" class="checkBtn" id="rank_disp1" name="customer_rank_disp_flg" @checked($dispFlg == '1')><label for="rank_disp1">使用金額ランク</label></li>
@endif
@if ($hasRaiten)
<li><input type="radio" value="2" class="checkBtn" id="rank_disp2" name="customer_rank_disp_flg" @checked($dispFlg == '2')><label for="rank_disp2">来店回数ランク</label></li>
@endif
@if ($hasKingaku || $hasRaiten)
<li><input type="radio" value="9" class="checkBtn" id="rank_disp9" name="customer_rank_disp_flg" @checked($dispFlg == '9')><label for="rank_disp9">ランクを表示しない</label></li>
@endif
</ul>
</td>

<td align="center" style="width:10em;">
<input type="submit" name="act" value="&emsp;表示を変更&emsp;" class="btnStyle" />
</td>

</tr>
</table>

</form>

<!-- / .contents --></div>

@foreach ($types as $type)
<div class="contents pB20">

<p class="h2Title">■{{ $rankNames[$type] }}ランク設定</p>
<div class="ranlListHeader">
<p class="figLeft pT5">ドラッグ アンド ドロップで順番を並び替え出来ます。<br />
<small>※条件が重複した場合は、上に配置してある方が優先されます。</small></p>
<a href="{{ route('admin.clientes.ranks.create', $type) }}" class="btnStyle figRight">　{{ $rankNames[$type] }}ランクを新規追加　</a>
</div>

<div id="{{ $type }}RankResult" class="resultTxt clearBoth">&nbsp;</div>
@if ($ranks[$type]->count() > 0)
<ul id="{{ $type }}RankList" class="rankListUl">
@foreach ($ranks[$type] as $rank)
<li class="ui-state-default" cd="{{ $rank->cd }}">

<table class="rankTbl">
<tr>

<th class="center" style="width:3em;">{{ $loop->iteration }}</th>

<td style="width:20%;">{{ $rank->name }}</td>
<td>期間内の{{ $rankNames[$type] }}が {{-- ランク適用条件 --}}@if ($rank->jyouken1 !== null)<span class="bold attention">{{ number_format($rank->jyouken1) }}{{ $rankUnits[$type] }}</span> 以上@endif{{ $rank->jyouken1 !== null && $rank->jyouken2 !== null ? ' ～ ' : '' }}@if ($rank->jyouken2 !== null)<span class="bold attention">{{ number_format($rank->jyouken2) }}{{ $rankUnits[$type] }}</span> 未満@endif</td>

<td class="center" style="width:5em;">
<input type="checkbox" onclick="checkHyouji('{{ $rank->cd }}','{{ $type }}',this);" name="hyouji_flg_{{ $rank->cd }}" @checked($rank->hyouji_flg != '9') />
</td>

<td class="center" style="width:10em;">
<a href="{{ route('admin.clientes.ranks.edit', [$type, $rank->cd]) }}" class="btnStyle">編集</a>
<a href="javascript:void(0);" onclick="deleteBtn('{{ $rank->cd }}','{{ $type }}'); return false;" class="btnStyle">削除</a>
</td>

</tr>
</table>

</li>
@endforeach
</ul>
@else
<table class="rankTbl" style="margin-top:2.5em;">
<tr><td>登録されているランクがありません。</td></tr>
</table>
@endif
<!-- / .contents --></div>
@endforeach

<form method="post" id="rank_delete_form" action="" style="display: none">
@csrf
@method('DELETE')
</form>
@endsection

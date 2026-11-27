{{-- ショートカット管理 (登録済み一覧, ordenable): system/category0/config/bookmark.php --}}
@extends('layouts.legacy')

@section('title', 'ショートカット管理 - ' . $siteName)

@section('menu', 'base')

@section('css')
<style type="text/css"><!--

.tableBox tr {
	background: #FFFFFF;
	cursor: pointer;
}

.ui-state-highlight {
	height: 40px;
	background: #FFFFCC;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

$(function() {
	$("#bookmarkList").sortable({
		axis: 'y',
		containment: '.wrap',
		placeholder: 'ui-state-highlight',

		stop: function(event, ui) {
			rankReturn = "";
			for(var i=0; i<$("#bookmarkList .ui-state-default").length; i++){
				rankReturn += "bm_"+$("#bookmarkList .ui-state-default:eq("+i+")").attr('cd')+"="+(i+1)+"&";
			}

			$.ajax({
				type: "POST",
				url: "ajax_bookmark.php",
				data: rankReturn,
				success: function(data){
					$("#result").html('<div class="tsuchiArea">表示順を更新しました。</div>');
					$(".tsuchiArea").animate({
						opacity: "toggle"
					}, 1500 , "easeInQuart");
				}
			});
		}
	});
});
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■登録ショートカット一覧</p>
		<div id="result" style="position: absolute; background: #FFFFEE;"></div>
		<div class="right"><a href="bookmark_list.php">ショートカット登録できる機能一覧へ</a></div>

		<div>
			※現在登録してあるショートカットの一覧です。ドラッグして表示順番を入れ替える事が可能です。<br />
			※また順番の変更は、当画面から遷移した時点で入れ替わります。
		</div>

		@if ($rows->isNotEmpty())
		<ul id="bookmarkList">
			@foreach ($rows as $row)
			<li class="ui-state-default" cd="{{ $row->cd }}">
				<table class="tableBox" style="margin-bottom: 0px;">
					<tr>
						<th style="width: 170px;">{{ $row->title }}</th>
						<td>{{ $row->naiyou }}</td>
					</tr>
				</table>
			</li>
			@endforeach
		</ul>
		@else
		登録されているリストが存在しません。
		@endif
	<!-- / .contents --></div>

	<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>
@endsection

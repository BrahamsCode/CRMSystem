{{--
	予定 / フォロー / 自動通知設定一覧 — legacy: system/category3/push/push_follow_list.php?type_flg=…
	Variables: $typeFlg, $rows (PushFollowMaster), $kensu, $page, $maxrows, $schedule (PushSchedule), $canEdit, $activeAc
--}}
@php
	$pageTitle = ['scheduled' => '予定', 'auto' => '自動', 'follow' => 'フォロー'][$typeFlg] . '通知設定';
	$from = ($page - 1) * $kensu;
	$listUrl = route('admin.promocion.push.follow-list');
@endphp
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧 - ' . $siteName)

@section('css')
<style type="text/css">

.naiyouPreviewArea {
	width: 15em;
	max-height: 150px;
	margin: 0;
	overflow-y: scroll;
	background: #D6D5D9;
	border: 1px solid #999;
}

.naiyouPreviewArea span {
	white-space: normal !important;
}

</style>
@endsection

@section('js')
<script type="text/javascript">
function setMailStatus(cd, st) {
	$('<div id="confirmChange" style="padding:10px;">' + st + 'してもよろしいですか？</div>').dialog({
		modal: true,
		title: '確認',
		buttons: {
			"OK": function() {
				$(this).dialog( "close" ).dialog('destroy');
				$('#confirmChange').remove();
				$.ajax({
					type: "POST",
					url: "{{ route('legacy.promocion.push.ajax-status') }}",
					data: "cd="+cd+"&st="+encodeURIComponent(st),
					dataType: "json",
					success: function(res) {
						if (res.status == "ok") {
							location.reload();
						} else {
							jAlert(res.errorMsg, "エラー");
						}
					},
					error: function() {
						jAlert("処理に失敗しました", "エラー");
					}
				});
			},
			"キャンセル": function() {
				$(this).dialog( "close" ).dialog('destroy');
				$('#confirmChange').remove();
			}
		}
	});
}

$(document).ready(function() {
	$.alerts.overlayOpacity = 0.5;
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

//表示件数の制御
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ $listUrl }}?type_flg={{ $typeFlg }}">
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
</form>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>

@if ($rows->isNotEmpty())

<div id="form">
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@include('admin.promocion.push._kensu')</select></p>
</div>

<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $maxrows) }}件目/{{ $maxrows }}件中</span></p>

<table class="tableBox2">
	<tr>
	<th>ID</th>
	<th>配信日時</th>
	<th>通知タイトル</th>
	<th>配信条件</th>
	<th>次回配信日時</th>
	<th>&nbsp;</th>
	</tr>
@foreach ($rows as $i => $row)
	<tr @if ($i % 2 == 1) class="tableBg" @endif>
	<td>{{ $row->cd }}</td>
	<td>
	@if ($row->type_flg === 'scheduled')
	{{ \App\Services\Promotions\PushSchedule::split($row->send_parameter)['send_date'] ?? '' }}<br/>{{ $row->send_time }}
	@else
	{{ $row->send_time }}
	@endif
	</td>
	<td>{{ $row->title }}</td>
	<td>
		<textarea name="naiyou" class="inputM" rows="3" readonly="readonly">{{ $row->search_display }}</textarea>
@if ($row->type_flg === 'auto')
		<br/>{{ $schedule->describe($row->send_parameter) }}
@endif
	</td>

	<td>
		@if ($row->next_send_date){{ \Illuminate\Support\Carbon::createFromTimestamp($row->next_send_date, \App\Services\Promotions\LegacyTime::ZONE)->format('Y-m-d H:i') }}@endif
	</td>

	<td align="center" style="width: 6em;">

	@if ((string) $row->disp_flg === '1')
		<span style="color:blue;font-weight:bold;font-size:110%;">起動中</span><br/>
		@if ($canEdit)<input type="button" class="btn btnStyleS" onclick="setMailStatus('{{ $row->cd }}', '停止')" value="停止する" style="color: #EE0000 !important;">@endif
	@else
		<span style="color:red;font-weight:bold;font-size:110%;">停止中</span><br/>
		@if ($canEdit)<input type="button" class="btn btnStyleS" onclick="setMailStatus('{{ $row->cd }}', '起動')" value="起動する" style="color: #EE0000 !important;">@endif
	@endif

	@if ($canEdit)
	<input type="button" class="btn btnStyleS" value="修正する" onclick="javascript:location.href='{{ route('admin.promocion.push.create', ['type_flg' => $row->type_flg, 'cd' => $row->cd]) }}';" />
	@endif
	</td>

  </tr>
@endforeach

</table>

<ul class="btnBox2">
@include('admin.promocion.push._pager', ['url' => $listUrl, 'params' => ['type_flg' => $typeFlg]])
</ul>

@else
<p>{{ $pageTitle }}はありません</p>
@endif

<!-- / .contents --></div>
@endsection

{{-- 権限項目管理 (lista): system/category0/config/authority_contents_list.php --}}
@extends('layouts.legacy')

@section('title', '権限項目管理 - ' . $siteName)

@section('menu', 'base')

@section('css')
<link rel="stylesheet" type="text/css" href="/system/css/popInline.css">
@endsection

@section('js')
<script type="text/javascript" src="/system/js/popInline.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);

	//ポップアップ用
	popIni();
});
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■権限項目一覧</p>
		<p><a href="javascript:openPopWindow('authority_contents.php', '600', '500');">新規登録</a></p>
		@if ($rows->isNotEmpty())
		<div class="form">
			<form name="hyouji_flg" method="post" action="/system/category0/config/authority_contents_list.php">
				<table class="tableBox2">
					<tr>
						<th>No</th>
						<th>項目名</th>
						<th>大カテゴリー</th>
						<th>説明</th>
						<th width="50px">表示</th>
						<th>修正</th>
					</tr>
					@foreach ($rows as $i => $row)
					<tr{!! $i % 2 !== 0 ? ' class="tableBg"' : '' !!}>
						<td align="top">{{ $row->cd }}</td>

						<td align="top">
							{{ $row->name }}
						</td>

						<td align="top">{{ $baseSystems[$row->tb_base_system_cd] ?? '' }}</td>

						<td align="top" style="line-height: 1.1em;">
							{!! nl2br(e((string) $row->comment)) !!}
						</td>

						<td align="center">
							<input type="checkbox" name="hyouji_flg_array[{{ $row->cd }}]" value="1" {!! $row->hyouji_flg === '1' ? ' checked="checked"' : '' !!} />
							<input type="hidden" name="update_cd[]" value="{{ $row->cd }}" />
						</td>

						<td align="top" class="textCenter" width="100">
							<a href="javascript:openPopWindow('authority_contents.php?cd={{ $row->cd }}', '600', '');"><img src="/system/img/btn_revise_off.gif" width="40" height="20" alt="修正" /></a>
						</td>
					</tr>
					@endforeach
				</table>
				<div style="text-align:right;padding-right:90px;">
					<input type="submit" value="表示情報を更新"/>
				</div>

				<input type="hidden" name="update_hyouji_flg" id="update_hyouji_flg" value="update" />
			</form>
		<!--/.form--></div>
		@else
		<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>データがありません。<br/><br/></p>
		@endif
	<!-- / .contents --></div>
@endsection

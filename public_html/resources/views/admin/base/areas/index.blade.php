{{-- エリア管理: system/category0/config/area.php --}}
@extends('layouts.legacy')

@section('title', 'エリア管理一覧 - ' . $siteName)

@section('menu', 'base')

@section('css')
<link rel="stylesheet" href="/system/category0/config/css/dtree.css" type="text/css" />
<link href="/system/category0/config/css/fukidashi.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="/system/category0/config/js/dtree.js"></script>
<script type="text/javascript" src="/system/category0/config/js/jquery.betterTooltip.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
	$('.tTip').betterTooltip({speed: 150, delay: 300});
});
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■エリア管理</p>
		@include('admin.base.areas._tree', ['treeUrl' => 'area.php'])
		<div style="width: 70%; float: right;">
			@if ($above && $above->name)
			<h5>
				<a href="area.php?pid={{ $above->pid }}">
					<img src="/system/category0/config/img/folderopen.gif" alt="" border="0" style="vertical-align:middle">
				</a>
				{{ $above->name }}
				<br />
				<br />
			</h5>
			@endif
			<a href="area_edit.php?pid={{ $pid ?: '' }}">この階層にエリアを追加する</a><br /><br />
			@if ($max > 0)
			<!-- フォーム -->
			<div class="btnBox">
				<form action="area.php" method="post" class="form">
					<table class="tableBox">
						<tbody>
							<tr align="center">
								<th width="50">ID</th>
								<th width="220" align="left">エリア名</th>
								<th width="50">更新</th>
								<th width="80">並び</th>
								<th width="50">削除</th>
								<th width="50">表示</th>
							</tr>
							@foreach ($rows as $i => $row)
							<tr align="center">
								<td width="50">{{ $row->cd }}</td>
								<td width="220" style="text-align:left">
									<div class="tTip" id="cloud1" title="「{{ $row->name }}」に登録されているカテゴリ一覧へ">
										<img src="/system/category0/config/img/folder.gif" alt="">
										<a href="area.php?pid={{ $row->cd }}">{{ $row->name }}</a>
									</div>
								</td>
								<td width="50">
									<a href="area_edit.php?area_cd={{ $row->cd }}&page={{ $page }}&pid={{ $pid ?: '' }}">
										<input type="button" class="btn" value="詳細" complete="complete" />
									</a>
								</td>
								<td width="80">
									<input type="text" name="narabi{{ $i }}" class="input3S" value="{{ $row->row_lis }}" />
									<ul style="padding:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
										@if ($i > 0 || $page !== 1)
										<li>
											<a href="#" onclick="location.href='area.php?page={{ $page }}&kensu=&thisCd={{ $row->cd }}&thisRow={{ $row->row_lis }}&kind=up&page={{ $page }}&pid={{ $pid ?: '' }}'">▲</a>
										</li>
										@else
&nbsp;
										@endif
										@if ($page !== $maxpage || $i < $rows->count() - 1)
										<li>
											<a href="#" onclick="location.href='area.php?page={{ $page }}&kensu=&thisCd={{ $row->cd }}&thisRow={{ $row->row_lis }}&kind=down&page={{ $page }}&pid={{ $pid ?: '' }}'">▼</a>
										</li>
										@endif
									</ul>
								</td>
								<td width="50">
									<a href="area_confirm.php?del_cd={{ $row->cd }}&page={{ $page }}&pid={{ $pid ?: '' }}">
										<input type="button" class="btn" value="削除" complete="complete" />
									</a>
								</td>
								<td width="50" bgcolor="{{ (string) $row->flg === '1' ? '#F6F6F6' : '#666666' }}">
									<input type="checkbox" name="flg{{ $i }}" value="1"@if ((string) $row->flg === '1') checked @endif>
									<input type="hidden" name="cd{{ $i }}" value="{{ $row->cd }}" >
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
					<input type="hidden" name="pid" value="{{ $pid ?: '' }}" >
					<input type="hidden" name="page" value="{{ $page }}" >
					<input type="hidden" name="update" value="update" >
					<input type="hidden" name="update_max" value="{{ $rows->count() }}" >
					<br />
					<input type="submit" value="更新する" class="tTip" id="cloud1" title="「並び順」と「表示」(ホームページ)を更新します。" />
				</form>
			</div>
			<br />
			@if ($pid > 0)
			<form action="area.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="pid" value="{{ $above?->pid }}" />
				<input type="submit" value="上の階層へ" />
			</form>
			@endif
			<!--　ネクストバック　-->
			<table style="width:440px;font-size:12px;text-align:center">
				<tr>
					<td style="border:0" align="right">
						@if ($page !== 1)
						<a href="area.php?page={{ $page - 1 }}" style="color:#346699;">前へ</a>
						@endif
					</td>
					<td style="border:0" style="color:#346699;text-align:center">
						<strong>{{ $max }}件ヒット {{ $from + 1 }}件目～{{ $from + $rows->count() }}件目表示</strong>
					</td>
					<td style="border:0" align="left">
						@if ($page !== $maxpage)
						<a href="area.php?page={{ $page + 1 }}" class="f14" style="color:#346699;">次へ</a>
						@endif
					</td>
				</tr>
			</table>
			@else
			<table style="font-size:12px;width:440px">
				<tbody>
					<tr>
						<td align="center">
							登録されているデータはありません
						</td>
					</tr>
				</tbody>
			</table>
			<br />
			@if ($pid > 0)
			<form action="area.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="pid" value="{{ $above?->pid }}" />
				<input type="submit" value="上の階層へ" />
			</form>
			@endif
			@endif
		</div>
	<!-- / .contents --></div>
@endsection

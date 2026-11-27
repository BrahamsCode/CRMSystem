{{-- エリア 新規登録 / 情報更新: system/category0/config/area_edit.php --}}
@php $whichone = $upflg === 'update' ? '情報更新' : '新規登録'; @endphp
@extends('layouts.legacy')

@section('title', 'エリア' . $whichone . ' - ' . $siteName)

@section('menu', 'base')

@section('css')
<link href="/system/category0/config/css/fukidashi.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" href="/system/category0/config/css/dtree.css" type="text/css" />
@endsection

@section('js')
<script type="text/javascript" src="/system/category0/config/js/dtree.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■エリア{{ $whichone }}</p>
		@include('admin.base.areas._tree', ['treeUrl' => 'area_edit.php', 'treeAreaCd' => $data['area_cd'] ?? ''])
		<div style="width: 70%; float: right;">
			<div class="btnBox">
				<!-- フォーム -->
				<form action="area_edit.php" method="post" enctype="multipart/form-data">
					<table class="tableBox">
						<tbody>
							<tr>
								<th align="center" style="background-color:#cfdbe5; width:70px; max-width: 70px;">エリア名</th>
								<td align="left">
									<input name="name" style="padding:0px;width: 95%; ime-mode: active;" type="text" value="{{ $data['name'] ?? '' }}" />
									{!! $error !!}
								</td>
							</tr>
							<tr>
								<th align="center" style="background-color:#cfdbe5;width:150px;">備考</th>
								<td align="left">
									<textarea name="comment" style="width: 95%;height:60px; ime-mode: active;">{{ $data['comment'] ?? '' }}</textarea>
								</td>
							</tr>
						</tbody>
					</table>
					<br />
					<input type="hidden" name="pid" value="{{ request('pid') }}" />
					<input type="hidden" name="area_cd" value="{{ $data['area_cd'] ?? '' }}" />
					<input type="hidden" name="upflg" value="{{ $upflg }}" />
					<input type="button" value="一覧へ"  class="existBtn" onclick="document.ichiran_form.submit()"/>
					<input type="submit" value="確認画面へ"  class="existBtn" />
				</form>
				<form name="ichiran_form" action="area.php" method="post" enctype="multipart/form-data">
					<input type="hidden" name="pid" value="{{ request('pid') }}" />
					<input type="hidden" name="page" value="{{ request('page') }}" />
				</form>
				@if ($pidChanged)
				<p class="m0" style="color:red">※親エリアが変更されます。よろしければ「確認画面へ」をクリックして下さい。</p>
				@endif
			</div>
		</div>
	<!-- / .contents --></div>
@endsection

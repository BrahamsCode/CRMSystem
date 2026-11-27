{{-- エリア 確認 (alta / edición / borrado): system/category0/config/area_confirm.php --}}
@php
    $btn = ['new' => '登録', 'update' => '更新', 'del' => '削除'][$mode];
    $whichone = ['new' => '新規登録', 'update' => '情報更新', 'del' => '情報削除'][$mode];
@endphp
@extends('layouts.legacy')

@section('title', 'エリア' . $whichone . ' - ' . $siteName)

@section('menu', 'base')

@section('css')
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
		<p class="h2Title">■エリア{{ $whichone }} 確認</p>
		@include('admin.base.areas._tree', ['treeUrl' => null])
		<div style="width: 70%; float: right;">
			<table class="tableBox">
				<tbody>
					<tr>
						<th align="center" style="background-color:#cfdbe5;width:150px;">エリア名</th>
						<td align="left">{{ $data['name'] ?? '' }}</td>
					</tr>
					<tr>
						<th align="center" style="background-color:#cfdbe5;width:150px;">備考</th>
						<td align="left">{!! nl2br(e($data['comment'] ?? '')) !!}</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="btnBox" style="padding-top: 20px; clear:both;">
			@if ($mode !== 'del')
			<form method="post" action="area_edit.php">
				<input type="submit" name="modoru" value="書き直す" class="existBtn" />
				@foreach ($data as $key => $val)<input type="hidden" name="{{ $key }}" value="{{ $val }}">@endforeach
				<input type="hidden" name="upflg" value="">
			</form>
			<form method="post" action="area_register.php">
				<input type="submit" name="act" value="この内容で{{ $btn }}する" class="existBtn" />
				@foreach ($data as $key => $val)<input type="hidden" name="{{ $key }}" value="{{ $val }}">@endforeach
				<input type="hidden" name="upflg" value="{{ $mode }}">
			</form>
			@else
			<form method="post" action="area.php">
				<input type="hidden" name="pid" value="{{ request('pid') }}">
				<input type="submit" name="modoru" value="一覧へ戻る" class="existBtn" />
			</form>
			<form method="post" action="area_register.php">
				<input type="submit" name="act" value="削除する" class="existBtn" />
				@foreach ($data as $key => $val)<input type="hidden" name="{{ $key }}" value="{{ $val }}">@endforeach
				<input type="hidden" name="upflg" value="del">
				<input type="hidden" name="del_cd" value="{{ $data['cd'] ?? '' }}">
			</form>
			@endif
		</div>
	<!-- / .contents --></div>
@endsection

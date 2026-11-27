{{-- 権限管理: system/category0/config/authority.php --}}
@extends('layouts.legacy')

@section('title', '権限管理 - ' . $siteName)

@section('menu', 'base')

@section('css')
<style type="text/css"><!--
.tipTitle {
	position: relative;
}
.tipComment {
	display: block;
	position: absolute;
	bottom: 0;
	width: 300px;
	padding: 5px;
	background: #FFFFFF;
	border: 1px solid #999999;
}
.tipTitle .tipComment {
	display: none;
}
.tipTitle:hover .tipComment {
	display: block;
}
--></style>
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

function getGroupData() {
	var gcd = $("#staff_group").val();
	if (gcd == "") {
		$("#kengen_data").empty();
	} else {
		$.get("ajax_group_authority.php",{
			gcd:gcd
		}, function(resp){
			if (resp != '') {
				$("#kengen_data").html(resp);
			}
		});
	}
}

function submitForm() {
	var data = $("#form1").serialize();
	$.ajax({
		url: "ajax_group_authority_register.php",
		data: data,
		success: function(resp){
			if (resp == '') {
				$("#mess").html('登録しました。').show();
				setTimeout('hideMess()', 2000);
			}
		}
	});
}

function hideMess() {
	$("#mess").hide().html('');
}
</script>
@endsection

@section('content')
<form name="form1" id="form1" action="authority.php">

<div class="contents">
<p class="h2Title">■権限一覧</p>
<select name="staff_group" id="staff_group" onchange="getGroupData();">
<option value="">▼グループ選択</option>
@foreach ($groups as $group)
<option value="{{ $group->cd }}" >{{ $group->name }}</option>
@endforeach
</select>
<!-- / .contents --></div>

<div id="kengen_data" class="contents">
<!-- / .contents --></div>

<input type="hidden" name="act" value="update">
</form>

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>
@endsection

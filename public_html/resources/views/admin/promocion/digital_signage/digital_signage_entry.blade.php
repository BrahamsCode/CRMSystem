{{--
	新着情報管理 — legacy: system/category3/digital_signage/digital_signage_entry.php
	Variables: $news (10 últimos), $editing (?DigitalSignageNews), $requestedCd, $picName, $previews (cd => URL|null)
	La subida (digital_signage.js + inc/upload.php + inc/disp_pic.php) se configura aquí con las rutas de Laravel.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '新着情報管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
function submitChk () {
	if (document.form1.news_naiyou && document.form1.news_naiyou.value == "") {
		alert("内容が空白です。\n\n内容をご記入下さい。");
		return false;
	} else {
		var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
		return flg;
	}
}

function submitChk2 (val) {
	var element = document.forms["form_" + val];
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
	if (flg) {
		element.submit();
	}
}

$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript" src="/system/js/ajaxupload-3.5.js"></script>
<script type="text/javascript">
// digital_signage.js con las rutas de Laravel y el token CSRF
var picExt;
var piclastExt = '';
var mylastTime;
var myTime = 0;

$(document).ready(function(){
	if (!document.getElementById('photo')) {
		return;
	}
	new AjaxUpload('#photo', {
		action: '{{ route('admin.promocion.news.upload') }}',
		onSubmit : function(file , ext){
			if (ext && /^(jpg|png|jpeg|gif|swf)$/i.test(ext)){
				mylastTime = myTime;
				piclastExt = picExt;
				picExt = ext;
				myTime = new Date().getTime();
				this.setData({
					'_token': '{{ csrf_token() }}',
					'ext': picExt,
					'time' : myTime,
					'lastTime' : mylastTime,
					'lastExt' : piclastExt,
					'title' : $("#title").val()
				});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, ext){
			$("#pic_name").remove();
			$("#form1").append("<input type=\"hidden\" name=\"pic_name\" id=\"pic_name\" value=\""+myTime+"\" />");
			$('#gazou').load("{{ route('admin.promocion.news.picture') }}?ext=" + picExt + "&t="+myTime);
		}
	});
});
</script>
@endsection

@section('content')
@if ($requestedCd)
<div class="contents">

<p class="h2Title">■新着情報管理</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.news.store') }}" onsubmit="return submitChk()">
@csrf

<fieldset>
<legend>新着管理情報</legend>

<table class="tableBox">
<tr>
<th>タイトル</th>
<td>
<input type="text" name="title" id="title" class="writeArea" maxlength="200" value="{{ $editing?->title }}" />
</td>
</tr>

<tr>
<th>画像</th>
<td>
	<div id="gazou">アップロード画像サイズは横幅75ピクセルに最適化されます。</div>
		<p id="photo">&nbsp;</p><input type="hidden" name="pic_name" id="pic_name" value="{{ $picName }}" />
		{{ \App\Support\LegacyHtml::tooltip(13) }}
</td>
</tr>
</table>

</fieldset>

<div class="btnBox">
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $editing?->cd }}" />
</div>

</form>

<!-- / .contents --></div>
@endif

<div class="contents">
<p class="h2Title">■新着情報一覧</p>
<div class="form">
@if ($news->isNotEmpty())

<p class="textCenter">最新から{{ $news->count() }}件の該当結果を表示しています。</p>

<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>タイトル</th>
    <th>プレビュー</th>
    <th>修正・削除</th>
  </tr>
@foreach ($news as $row)
  <tr @if ($loop->index % 2 !== 0) class="tableBg" @endif>
    <td>{{ $row->cd }}</td>
    <td>{{ $row->title }}</td>
    <td>@if ($previews[$row->cd])<a href="{{ $previews[$row->cd] }}">--</a>@endif</td>
    <td class="textCenter" width="100"><a href="{{ route('admin.promocion.news.index', ['cd' => $row->cd]) }}"><img src="/system/img/btn_revise_off.gif" width="40" height="20" alt="修正" /></a>
<a href="javascript:void(0)" onclick="submitChk2({{ $loop->iteration }}); return false;"><img src="/system/img/btn_delete_off.gif" width="40" height="20" alt="削除" /></a>
<form method="post" name="form_{{ $loop->iteration }}" id="form_{{ $loop->iteration }}" class="displayNone" action="{{ route('admin.promocion.news.destroy') }}">
@csrf
<input type="hidden" name="delete_flg" value="del">
<input type="hidden" name="cd" value="{{ $row->cd }}">
<input type="submit" name="delete" class="btn" value="削除する">
</form></td>
  </tr>
@endforeach
</table>
@else
新着情報は登録されていません。
@endif

</div>

<!-- / .contents --></div>
@endsection

{{-- Legacy: system/category1/customer/point_setting_frm.php (ポイント管理): imágenes 『ポイントについて』 (AJAX a
     ajax_apliFileUpload.php, URL relativa desde customer/), alta/edición y lista con ▲▼ y 削除.
     Variables:
       $siteName  nombre del sitio (<title>)
       $item      ?PointSetting fila que se edita (?cd=) o null en el alta: cd, name, point
       $items     Collection de PointSetting activas (disp_flg = '1') por rank: cd, name, point, rank
       $frames    array de huecos de imagen (PointSettingImageStore::frames()): index (0 = 全店舗用), shop (?Shop:
                  cd, kaishamei), picCd (cd de la tienda o 0), image (?string URL), useAllShopImage (bool)
--}}
@php
    $pageTitle = 'ポイント管理';
    $frameCount = count($frames);
@endphp
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', $pageTitle . '管理 - ' . $siteName)

{{-- El legacy usa $useLatestJquery = true (inc/head.php): jQuery 1.11.2 + jQuery UI 1.11.4 --}}
@section('jquery', 'jquery-1.11.2.min.js')
@section('jquery_ui', 'jquery-ui-1.11.4.min.js')

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	//$(".ac1").accordion('option', 'active', 0);
	$(".ac1").accordion({active: 0});

});
</script>

<script type="text/javascript">

function submitChk () {

	if(document.form1.name.value == ""){
		alert("ポイント名が空白です。\n\nポイント名をご記入下さい。");
		return false;
	}

	// El legacy leía document.form1.name.point (undefined) y el error cortaba la función; se lee el campo point
	if(document.form1.point.value == ""){
		alert("ポイント数が空白です。\n\nポイント数をご記入下さい。");
		return false;
	}

	else if( document.form1.point.value.match(/[^0-9]+/) ){
		alert("ポイント数は半角数字のみでご入力ください。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}


function submitChk2 (val) {
	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	if(flg){
		element.submit();
	}
}

// ▲▼: el legacy cambiaba el orden con un GET (?thisCd=&thisRow=&kind=); ahora POST a admin.clientes.point-settings.sort
function sortRow (url) {
	var f = document.getElementById("sort_form");
	f.action = url;
	f.submit();
}

</script>
@endsection

@section('content')
<div class="contents">


<!-- ママイページアプリのHOMEに表示されるメインイメージ↓ -->
<style type="text/css">
.imageDisplay {
	display: inline-block;
	overflow: hidden;
	width: 200px;
	margin-bottom: 10px;
	vertical-align: top;
}
.imageFrame {
	min-height: 140px;
	padding: 5px;
	background-color: #EEEEEE;
	position: relative;
}
.bannerFrame.loading:after,
.imageFrame.loading:after {
	content: " ";
	display: block;
	background: url({{ asset('legacy/system/img/loading.gif') }});
	background-size: 60px;
	width: 60px;
	height: 60px;
	position: absolute;
	top: 0;
	bottom: 0;
	right: 0;
	left: 0;
	margin: auto;
}
</style>

<div class="contents">
	<p class="h2Title">『ポイントについて』用画像</p>

	<form name="uploadMainPicForm">
		<input type="hidden" name="mypage_config[home_image_flg][-1]" value="1">
		<div class="imageList">
		@foreach ($frames as $frame)
			@php $i = $frame['index']; @endphp
			@if ($i == 0)
				<table class="tableBox MB10">
					<tr>
						<th>全店舗用</th>
						<th>各店舗</th>
					</tr>
					<tr>
						<td style="vertical-align: top;     padding-right: 5px;">
			@elseif ($i == 1)
					</td><td style="vertical-align: top;">
			@endif
				<div class="imageDisplay">
					@if ($i > 0)
						@if ($frame['shop'])
							<div>【{{ $frame['shop']->kaishamei }}】</div>
						@endif
					@else
						<div>【全店舗用】</div>
					@endif
					<div class="imageFrame">@if ($frame['image'])<img src="{{ $frame['image'] }}" style="width:100%;"><input type="hidden" name="pic[{{ $frame['picCd'] }}]" value="def">@endif</div>
					<div><input type="file" name="image{{ $i }}" class="addMainImage" /></div>
					@if ($i > 0)
						<label for="mypage_config_home_image_flg_{{ $frame['shop']->cd }}">
							<input type="checkbox" name="mypage_config[home_image_flg][{{ $frame['shop']->cd }}]" id="mypage_config_home_image_flg_{{ $frame['shop']->cd }}" value="1" @checked($frame['useAllShopImage'])> 全店舗画像を利用する
						</label>
					@endif
					@if ($frame['image'])
					{{-- target: el legacy ponía el cd de la tienda, pero los archivos se numeran por posición (image<i>) --}}
					<div class="del_pic_wrap right"><input type="button" class="deleteMainPic" target="{{ $i }}" value="画像を削除する" style="font-size:80%;color:red; margin-top:5px;"></div>
					@endif
				</div>
			@if ($i == $frameCount - 1)
				</td></tr>
				</table>
			@endif
		@endforeach
		</div>

		<div class="center MB10">
			<input type="button" id="picMainUploadBtn" class="btnStyle FS20 WD140" value="アップロード"/>
			<input type="hidden" name="type" value="sendMainData" />
			<input type="hidden" name="dir" value="point_setting" />
		</div>
	</form>

	<div class="MB10">
		アップロードされた画像は、横幅800px基準でリサイズされ、中央基準に4:3の比率でトリミング(横800px 縦600px)されます。
	</div>

</div>

<div id="uploadDialog"></div>

<script type="text/javascript">
// URL de ajax_apliFileUpload.php y de ./uploads/temp/ (en el legacy, relativas a category1/customer/): absolutas para que
// funcionen también desde /admin/clientes/point-settings
var apliFileUploadUrl = "{{ route('legacy.clientes.apli-file-upload') }}";
var apliTempUrl = "{{ url('system/category1/customer/uploads/temp') }}/";
$(function(){
	$("#uploadDialog").dialog({
		autoOpen: false,
		open: function(event, ui){ $(".ui-dialog-titlebar-close").hide(); },
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: 'Infomation',
		width: 300,
		height: 300,
		buttons: {}
	});


	$(".addMainImage").on("change", function(){
		var fd = new FormData();
		var tg = $(this);
		var shop_cd = $(this).attr("name").split("image").join("");

		if ( $(this).val()!== '' ) {
			fd.append( "file", tg.prop("files")[0] );
			fd.append( "type", 'picMainUpload' );
		}

		var loader = '<div class="loader"></div>';
		tg.parent().prev().html(loader);

		var postData = {
			type : "POST",
			dataType : "json",
			data : fd,
			processData : false,
			contentType : false
		};
		$.ajax(
			apliFileUploadUrl, postData
		).done(function( json ){
			if(json.status == 'ok'){

				tg.parent().parent().find(".del_pic_wrap").remove();

				var html= '<input type="hidden" name="pic[' + shop_cd + ']" value="'+json.imageName+'">';

				tg.parent().prev().html('<img src="'+apliTempUrl+json.imageName+'" style="width:100%;">' + html);
				$('#picMainUploadBtn').show();
			} else {
				tg.parent().prev().html('');
				alert( json.errMsg );
			}
		});
	});

	$(".deleteMainPic").on("click", function(){
		var t = $(this);
		$("#uploadDialog").html('削除しますか？');
		$("#uploadDialog").dialog({
			autoOpen: true,
			buttons: {
				'削除する': function(){
					$.ajax({
						url: apliFileUploadUrl,
						method: 'post',
						dataType: "json",
						data: "type=delMain&dir=point_setting&num="+t.attr("target")
					}).done(function( res ) {
						if(res.status == "ok"){
							$("#uploadDialog").html(res.res + "\n\n画面をリロードします。");
							location.reload(false);
						}
					}).fail(function( jqXHR, textStatus, errorThrown ) {
						console.log( 'ERROR', jqXHR, textStatus, errorThrown );
					});
				},
				'キャンセル': function(){
					$(this).dialog('close');
				}
			}
		});
	});

	$("#picMainUploadBtn").on("click", function(){
		$("#uploadDialog").text('更新中です');
		$("#uploadDialog").dialog({
			autoOpen: true,
			buttons: {}
		});

		$.ajax({
			url: apliFileUploadUrl,
			method: 'post',
			dataType: "json",
			data: $("form[name='uploadMainPicForm']").serialize()
		}).done(function( res ) {
			if(res.status == 'ok'){
				$("#uploadDialog").text('アップロードが完了しました。');
				$("#uploadDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
				location.reload(false);
			}else{
				$("#uploadDialog").text( res.errMsg );
				$("#uploadDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
			}
		}).fail(function( jqXHR, textStatus, errorThrown ) {
			//console.log( 'ERROR', jqXHR, textStatus, errorThrown );

			$("#uploadDialog").text(jqXHR + textStatus + errorThrown);
			$("#uploadDialog").dialog({
				buttons: {
					'閉じる': function(){
						$(this).dialog('close');
					}
				}
			});
		});
	});
});
</script>
<!-- マイページアプリのHOMEに表示されるメインイメージ↑ -->

<p class="h2Title">■{{ $pageTitle }} 登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ $item ? route('admin.clientes.point-settings.update', $item->cd) : route('admin.clientes.point-settings.store') }}" onsubmit="return submitChk()">
@csrf
@if ($item)
@method('PUT')
@endif


<fieldset>
<legend>{{ $pageTitle }}情報</legend>

<table class="tableBox">

<tr>
<th>ポイント名</th>
<td>
<input type="text" name="name" id="name" class="writeArea" value="{{ old('name', $item?->name) }}" />
</td>
</tr>

<tr>
<th>ポイント数</th>
<td>
<input type="text" name="point" id="point" class="writeArea" value="{{ old('point', $item?->point) }}" style="width: 200px" /> pt
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($item)
<input type="submit" name="enter" id="revise" class="btnStyleM" value="この内容で修正する" />
<input type="hidden" name="act" value="up" />
<input type="hidden" name="cd" value="{{ $item->cd }}" />
@else
<input type="submit" name="enter" id="reivse" class="btnStyleM" value="この内容で登録する" />
<input type="hidden" name="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>
<div class="form">
@if ($items->count() > 0)

<p class="textCenter">{{ $items->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
	<tr>
		<th>ポイント名</th>
		<th>ポイント数</th>
		<th>修正・削除</th>
		<th>並び</th>
	</tr>


@foreach ($items as $row)
@if ($loop->index % 2 != 0)
	<tr class="tableBg">
@else
	<tr>
@endif
	<td>{{ $row->name }}</td>
	<td>{{ $row->point }}</td>
	<td class="textCenter" width="100">
	<a href="{{ route('admin.clientes.point-settings', ['cd' => $row->cd]) }}" class="btnStyle">修正</a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $loop->iteration }}); return false;" class="btnStyle">削除</a>

	<form method="post" name="form_{{ $loop->iteration }}" id="form_{{ $loop->iteration }}" class="displayNone" action="{{ route('admin.clientes.point-settings.destroy', $row->cd) }}">
	@csrf
	@method('DELETE')
	<input type="hidden" name="act" class="btn" value="del">
	<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
	<input type="submit" name="delete" class="btn" value="削除する">
	</form>
	</td>

	<td style="width: 3em;">{{ $loop->iteration }}
	<ul style="padding:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
	<li><a href="#" onclick="sortRow('{{ route('admin.clientes.point-settings.sort', ['cd' => $row->cd, 'kind' => 'up']) }}'); return false;">▲</a></li>

	<li><a href="#" onclick="sortRow('{{ route('admin.clientes.point-settings.sort', ['cd' => $row->cd, 'kind' => 'down']) }}'); return false;">▼</a></li>
	</ul>

	</td>
  </tr>
@endforeach

</table>
@else
{{ $pageTitle }}は登録されていません。
@endif

<ul class="btnBox2">
</ul>

</div>

<!-- / .contents --></div>

<form method="post" id="sort_form" action="" style="display:none">
@csrf
</form>
@endsection

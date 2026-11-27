{{--
	プッシュ通知新規作成 / 修正 — legacy: system/category3/push/push_new.php + push_new.js
	Variables: $formValues, $blocks ([['img', nombre, '1'|''], ['text', texto]]), $typeFlg, $cd, $searchUniqid,
	  $recurring (auto|follow), $autoDescription, $tags, $survey, $toolName, $canCancel, $activeAc
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'プッシュ通知 - ' . $siteName)

@section('css')
<link href="/system/css/popupwindow.css" rel="stylesheet" type="text/css" media="all" />
<link href="/system/category3/css/colorbox.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="/system/plugin/ui-datepicker/ui.datepicker.css" />
<link rel="stylesheet" type="text/css" href="/system/plugin/myflexselect/myFlexSelect.css" />
<style>
label[for=post_type_1],
label[for=post_type_2]{
	position: relative;
	top: -2px;
	left: -15px;
}
.well_over{
    overflow: scroll;
    height: 393px;
}
.well {
    min-height: 20px;
    padding: 19px;
    margin-bottom: 20px;
    background-color: #fafafa;
    border: 1px solid #e8e8e8;
    border-radius: 0;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.05);
    box-shadow: inset 0 1px 1px rgba(0,0,0,0.05);
}
.preview_title {
	font-size: 26px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-top: 0px;
}
.well img{
	width: 100%;
}
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/category3/js/jquery.colorbox.js"></script>
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript" src="/system/plugin/ui-datepicker/gcalendar-holidays.js"></script>
<script type="text/javascript" src="/system/plugin/myflexselect/myFlexSelect.js"></script>
<script type="text/javascript" src="/system/js/popupwindow-1.8.js"></script>
<script type="text/javascript" src="/system/js/ajaxupload-3.5.js"></script>
<script type="text/javascript" src="/system/category3/push/push_new.js"></script>
<script type="text/javascript">
var max_pics = 1;
var keep_pics_extension = 1; // 2015.11.24 マルティン 画像の拡張子を変換しない

//処理開始時の定義関数済み関数の呼び出し  2016.02.09 イケダ
var ajaxUploadWillStartLoad = function(){
	$("#sendBtn").attr("disabled", "disabled");
};
//処理終了時の定義関数済み関数の呼び出し 2016.02.09 イケダ
var ajaxUploadDidEndLoad = function(){
	$("#sendBtn").removeAttr("disabled");
};

var pushUrls = {
	save: "{{ route('legacy.promocion.push.ajax-save') }}",
	member: "{{ route('legacy.promocion.push.ajax-member') }}",
	status: "{{ route('legacy.promocion.push.ajax-status') }}",
	followList: "{{ route('admin.promocion.push.follow-list') }}",
	index: "/system/category3/index.php"
};

$(document).ready(function() {

	$.alerts.overlayOpacity = 0.5;

	togglePointTr();

	function togglePointTr() {
		if ( $(".post_type:checked").val() == 1 ) {
			$(".point_toggle_target").hide();
		} else {
			$(".point_toggle_target").show();
		}
	}

	$(".post_type").click(function(){
		togglePointTr();
	});

	$( "#datepicker, #sendDate" ).datepicker({
		minDate: '0day'
	});

	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);

	function checkForm() {
		var error = '';

		if ( $(".post_type:checked").val() == 2 ) {

			if ( $.trim($("input[name=post_point]").val()) == '' || !$.trim($("input[name=post_point]").val()).match(/^[0-9]{0,6}$/) ) {
				error += 'ポイント数は半角数字、0～999999で入力してください。\n';
			}

			var pointExpDate = ($('#point_expiration_days').length > 0) ? $.trim($('#point_expiration_days').val()) : $.trim($("input[name=point_expiration_date]").val());
			if ( pointExpDate == '' ||
				( $('#point_expiration_days').length > 0 && !pointExpDate.match(/^[0-9]{0,3}$/) ) ||
				( $('#point_expiration_days').length == 0 && !pointExpDate.match(/^\d{4}\/\d{2}\/\d{2}$/) )
			) {
				error += '正しいポイント獲得の有効期限を入力してください。\n';
			}

		}

		if ( $.trim($('#title').val()) == '' ) error += '通知タイトルを入力してください。\n';

		return error;
	}

	$('#sendBtn').click(function(e){
		var error = checkForm();
		if ( error ) {
			jAlert(error, "警告");
			return false;
		} else {
			$( "#show_dialog" ).dialog( "open" );
		}
	});

	ShowJQueryConfirmDialog();

	// 確認ダイアログの表示(JQuery)
	function ShowJQueryConfirmDialog() {
		$( "#show_dialog" ).html( "本当に送信されますか？" );
		$( "#show_dialog" ).dialog({
			modal: true,
			title: "確認ダイアログ",
			autoOpen:false,
			buttons: {
				"OK": function() {
					$( this ).dialog( "close" );
					sendData();
				},
				"キャンセル": function() {
					$( this ).dialog( "close" );
				}
			}
		});
	}

@if ($blocks !== [])
@foreach ($blocks as $block)
@if ($block[0] === 'img')
	setImgTag({!! json_encode($block[1], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}, {!! json_encode($block[2]) !!});
@else
	setTextTag({!! json_encode(e($block[1]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!});
@endif
@endforeach
@else
	setTextTag();
	setImgTag();
@endif

	var nl2br = function (str) {
		str = str.replace(/\r\n/g, "<br />");
		str = str.replace(/(\n|\r)/g, "<br />");
		return str;
	};

	var escapeHtml = function (str) {
		return $('<div/>').text(str).html();
	};

	var previewDialog = function() {

		var preview_title = escapeHtml($("#title").val());
		var preview_body = "<br>";

		$("*[name^=contents\\[]").each(function(){

			if ( $(this).attr("id").indexOf("text") != -1 ) {
				preview_body += nl2br( escapeHtml($(this).val()) )  + "<br><br>";
			} else if ( $(this).val() != "" ) {
				if ( $(this).siblings('.registeredFlg').val() == "1" ) {
					var folder = $('#type_flg').val() != '' ? 'push_follow' : 'push';
					folder += '/' + $('#cd').val();
					preview_body += '<img src="/hp/uploads/c3/' + folder + '/' + $(this).val() + '"><br><br>';
				} else {
					preview_body += '<img src="/system/uploads/' + $(this).val() + '"><br><br>';
				}
			}

		});

		$("#preview_dialog .preview_title").html( preview_title );
		$("#preview_dialog .preview_body").html( preview_body );

		$( "#preview_dialog" ).dialog({
			modal: true,
			title: "プレビュー",
			autoOpen:true,
			height: 500,
			buttons: {
				"閉じる": function() {
					$( this ).dialog( "close" );
				}
			}
		});

		$( "#preview_dialog" ).dialog( "open" );

	};

	$("#previewBtn").click(function(){
		previewDialog();
	});

	$("#testSendBtn").click(function(){
		var error = checkForm();
		if ( error ) {
			jAlert(error, "警告");
			return false;
		} else {
			$('#searchMemberWrapper').click();
			$("#easySearchWord").focus();
		}
	});

	// =============================================================
	// 会員検索ダイアログ
	var timeoutHandler = null;
	var preSearchWord = null;
	var lastSearchedData = null;
	$('#searchMemberWrapper').colorbox({opacity:0.5, width:400, height:420, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#searchMemberDialog"});

	$('#easySearchWord').keyup(function(e){
		if (timeoutHandler != null) clearTimeout(timeoutHandler);
		timeoutHandler = setTimeout(function() {
			if($("#easySearchWord").val() == preSearchWord) return false;
			preSearchWord = $("#easySearchWord").val();
			$("#result").html('<p style="text-align:center;color:blue;">検索中</p>');
			searchMember(preSearchWord);
			timeoutHandler = null;
		}, 500);
	});

	function searchMember(searchWord) {
		if ( typeof(searchWord) == 'undefined' ) searchWord = '';

		lastSearchedData = null;

		$.ajax({
			type: "POST",
			url: pushUrls.member,
			data: 'w=' + encodeURIComponent($('#easySearchWord').val()),
			dataType: "json",
			timeout: 15000, // タイムアウト15秒に設定
			success: function(resp) {
				if (resp.status == "ok") {
					lastSearchedData = resp.data;
					var html = '';
					html += '<table class="tableBox2" style="overflow:auto;">';
					html += '<tr style="line-height:15px;">';
					html += '<th style="width:90px; line-height:15px;"><small>ID</small></th>';
					html += '<th style="line-height:15px;"><small>名前</small></th>';
					html += '<th style="width: 100px;">&nbsp;</th>';
					html += '</tr>';

					for ( i = 0; i < resp.data.length; i++ ) {
						var rowData = resp.data[i];
						var bgcolor = (i % 2 == 0) ? "#FFFFFF" : "#EEEEEE";
						html += '<tr style="line-height:15px; background-color:' + bgcolor + '">';
						html += '<td style="overflow:hidden;">' + escapeHtml(rowData.id || '') + '</td>';
						html += '<td style="overflow:hidden;">';
						if (rowData.name_kana && rowData.name_kana != '') html += '<small>' + escapeHtml(rowData.name_kana) + '</small><br />';
						html += escapeHtml(rowData.name || '');
						html += '</td>';
						html += '<td>';
						html += '<input type="button" class="testMemberBtn btnStyle" value="確定" idx="' + i + '" />';
						html += '</tr>';
					}

					$("#result").html(html);
				} else {
					var error = resp.errorMsg || 'データがありません。';
					$("#result").html('<p style="text-align:center;color:red;">' + error + '</p>');
				}
			},
			error: function() {
				$("#result").html('<p style="text-align:center;color:red;">データがありません。</p>');
			}
		});
	}

	$('#result .testMemberBtn').live('click', function(){
		var idx = $(this).attr('idx');
		sendTestTo(idx);
	});

	function sendTestTo(index) {
		var testMember = lastSearchedData[index] || false;
		if ( !testMember ) return false;
		jConfirm(testMember.name + "宛てにテスト配信します。\nよろしいですか？", "テスト配信確認",
			function(ret){
				if (ret) {
					$('#searchMemberWrapper').colorbox.close();
					sendData(testMember);
				} else {
					return false;
				}
			}
		);
	}
	// =============================================================

	function nextPage() {
		var typeFlg = $('#type_flg').val();
		return typeFlg != '' ? pushUrls.followList + '?type_flg=' + typeFlg : pushUrls.index;
	}

	function sendData(testMember) {
		if ( typeof(testMember) != 'object' ) testMember = false;

		var formData = $('#sendForm').serialize();
		if (testMember) {
			formData += '&testMemberCd=' + testMember.cd;
		}

		$.alerts.dialogClass = "style"; // jAlertスタイルを変更する main.cssで定義。
		jAlert("処理中", "");

		$.ajax({
			type: "POST",
			url: pushUrls.save,
			data: formData,
			dataType: "json",
			timeout: 15000, // タイムアウト15秒に設定
			success: function(data) {
				$.alerts._hide();
				if (data.status == "ok") {
					jAlert("配信完了しました。","プッシュ通知");
					if (testMember) {
						setTimeout(function() {
							$.alerts._hide();
						}, 1000);
					} else {
						location.href = nextPage();
					}
				} else {
					$.alerts.dialogClass = null; // jAlertスタイルを解除する。
					var error = data.errorMsg || '予期しないエラー発生しました。\\nもう一度やり直してください。';
					jAlert(error,"警告", function() {
						if (data.isFatal) {
							location.href = nextPage();
						}
					});
				}
			},
			error: function() {
				$.alerts._hide();
				$.alerts.dialogClass = null; // jAlertスタイルを解除する。
				jAlert("予期しないエラー発生しました。","警告");
			}
		});

	}
});

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
					url: pushUrls.status,
					data: "cd="+cd+"&st="+encodeURIComponent(st),
					dataType: "json",
					success: function(res) {
						if (res.status == "ok") {
							location.href = pushUrls.followList + "?type_flg=" + $('#type_flg').val();
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

$(function(){
	$("input"). keydown(function(e) {
		if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
			return false;
		} else {
			return true;
		}
	});
});
</script>
@endsection

@section('content')
<div id="searchMemberWrapper" style="display:none;">
	<div id="searchMemberDialog" class="form">
		<table class="tableBox" style="margin-bottom:0;">
			<tr>
			<td>
				<input name="easySearchWord" id="easySearchWord" class="inputL" style="width: 98%; font-size: 20px;" type="text">
				<br>
				<small>※キーワードは ID 管理番号 名前 カナ 電話番号 メールアドレスから検索されます。</small>
			</td>
			</tr>
		</table>
		<div id="result" style="width:100%;height:290px;overflow:auto;"></div>
	</div>
</div>

<div class="contents">
<p class="h2Title">■プッシュ通知</p>

@php($ro = ' type="text" readonly="readonly" style="background:#FFF; border: 1px solid #999; width: 8em; text-align: center;" onclick="this.select(0,this.value.length)"')
	<p style="margin-bottom: 10px;">
@foreach ($tags as $tag)
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{' . $tag['word'] . '}' }}"{!! $ro !!} /></font> と入力された箇所には顧客の<font color="red">{{ $tag['label'] }}</font>が挿入されます。<br />
@if (! empty($tag['note']))
	　 {{ $tag['note'] }}<br />
@endif
@endforeach

※ <font color="black" style="font-weight: bold;"><input value="{日後:●●}"{!! $ro !!} /></font> と入力された箇所には<font color="red">配信日からの●●日後の日付</font>が挿入されます。<br />
※ 『●●』には半角数字をご入力ください。<br />

@if ($survey !== null)
	<br />
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{アンケートURL[' . $survey . ']}' }}"{!! $ro !!} /></font> と入力された箇所には<font color="red">お客様アンケートのURL</font>が挿入されます。<br />
@endif
		<div style="margin:10px 0;">
			※ 通知内容において以下のタグが入力された箇所には顧客の情報に置き換わります。<br>
			※ タイトルには置換タグは用いないで下さい。<br>
			※ 『画像用リンク』は対応する画像が設定されている場合のみ機能します。
		</div>
	</p>

	<form id="sendForm" class="form" action="" method="post">
		<table class="tableBox">

			<tr>
				<th style="min-width:120px;">配信条件</th>
				<td>{{ $formValues['searchDisplay'] }}</td>
			</tr>

			<tr>
				<th style="min-width:120px;">配信時間</th>
				<td>
				@unless ($recurring)
					@if ($typeFlg === '')
					<input name="scheduled_flg" id="scheduled_flg0" type="radio" value="0" checked="checked"><label for="scheduled_flg0">即時配信する</label>&emsp;
					<input name="scheduled_flg" id="scheduled_flg1" type="radio" value="1"><label for="scheduled_flg1">時間指定する</label>
					@endif
					<input type="text" name="send_date" value="{{ $formValues['sendDate'] }}" id="sendDate" readonly="readonly" style="width:80px;" />
				@endunless
					<select name="send_hour" id="send_hour" class="selectBox">
						@for ($i = 0; $i <= 23; $i++)
						@php($v = sprintf('%02d', $i))
							<option value="{{ $v }}"@if ((string) $formValues['sendHour'] === $v) selected="selected"@endif>{{ $v }}時</option>
						@endfor
					</select>

					<select name="send_min" id="send_min" class="selectBox">
						@for ($i = 0; $i <= 55; $i += 5)
						@php($v = sprintf('%02d', $i))
							<option value="{{ $v }}"@if ((string) $formValues['sendMin'] === $v) selected="selected"@endif>{{ $v }}分</option>
						@endfor
					</select>&emsp;
					@if ($typeFlg === 'auto')
					&emsp;&emsp;{{ $autoDescription }}
					@endif
				</td>
			</tr>

			<tr>
				<th style="min-width:120px;">通知タイプ<em>※</em></th>
				<td>
					<input type="radio" id="post_type_1" class="post_type" name="post_type" value="1"@if ((int) $formValues['postType'] !== 2) checked="checked"@endif />
					<label for="post_type_1">　通常プッシュ配信</label>

					<input type="radio" id="post_type_2" class="post_type" name="post_type" value="2"@if ((int) $formValues['postType'] === 2) checked="checked"@endif />
					<label for="post_type_2">　ポイントチケット配信</label>
				</td>
			</tr>

			<tr class="point_toggle_target">
				<th style="min-width:120px;">付与ポイント数</th>
				<td>
					<input type="number" id="post_point" name="post_point" value="{{ $formValues['postPoint'] }}" min="1" max="999999" class="inputS" />&emsp;
					<span style="color:#ee0000;">※1～999999にしてください。</span>
				</td>
			</tr>

			<tr class="point_toggle_target">
				<th style="min-width:120px;">ポイント獲得の有効期限</th>
				<td>
				@if ($recurring)
					配信から
					<input type="number" name="point_expiration_days" min="0" max="999" value="{{ $formValues['pointExpirationDays'] }}" id="point_expiration_days" class="inputS" />日後まで&emsp;
					<span style="color:#ee0000;">※0～999にしてください。</span>
				@else
					<input type="text" name="point_expiration_date" value="{{ $formValues['pointExpirationDate'] }}" id="datepicker" readonly="readonly" />
				@endif
				</td>
			</tr>

			<tr>
				<th style="min-width:120px;"><label for="title">通知タイトル<em>※</em></label></th>
				<td><input type="text" name="title" id="title" class="writeArea" maxlength="100" value="{{ $formValues['title'] }}" /></td>
			</tr>
		</table>

		<ul class="button-list">
			<li><input type="button" class="btn btn-success" id="textAddButton" value="テキスト追加"></li>
			<li><input type="button" class="btn btn-primary" id="imgAddButton" value="画像追加"></li>
		</ul>

		<input type="hidden" id="cd" name="cd" value="{{ $cd }}" />
		<input type="hidden" id="type_flg" name="type_flg" value="{{ $typeFlg }}" />
		<input type="hidden" name="sendParameter" value="{{ $formValues['sendParameter'] }}" />

		<input type="hidden" name="send_push" value="send" />
		<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />

		<input type="hidden" name="act" value="sendPush" />

		<div style="text-align:center;margin-top:20px;padding:10px;">
@if ($canCancel)
			<input type="button" id="delBtn" value="予定解除する" class="btn btnStyleM" onClick="setMailStatus({{ json_encode($cd) }}, '解除');" />
@endif
			<input type="button" id="previewBtn" value="プレビュー" class="btn btnStyleM" onClick="return false;" />
			<input type="button" id="testSendBtn" value="この内容でテスト配信する" class="btn btnStyleM" onClick="return false;" />
			<input type="button" id="sendBtn" value="この内容で配信する" class="btn btnStyleM" onClick="return false;" />
		</div>
		<div id="show_dialog" style="padding:10px;"></div>
		<div id="preview_dialog" style="display:none">
			<div class="well_over">
				<div class="well">
					<p class="preview_title"></p>
					<div class="preview_body"></div>
				</div>
			</div>
		</div>
		<div id="pushInfo" style="text-align:center;padding:10px;"></div>
	</form>

<!-- / .contents --></div>
@endsection

var contents_number = 0;

$(function(){
	$("<style type='text/css'>.pic-wrapper img { width:auto;height:auto;max-width:240px;max-height:240px; } </style>").appendTo("head");

	$("#textAddButton").click(function(){
		setTextTag( "" );
	});

	$("#imgAddButton").click(function(){
		setImgTag( "" );
	});

});

function setTextTag( arg ){
	if ( typeof arg == 'undefined' ) arg = '';
	$("#sendForm .tableBox").append(
		'<tr>' +
			'<th>' +
				'<label for="contents_text' + contents_number + '">' + (contents_number + 1) + '.テキスト</label>' +
			'</th>' +
			'<td>' +
				'<textarea name="contents[' + contents_number + ']" id="contents_text' + contents_number + '" style="width:360px;height:100px;resize:none;">' + arg + '</textarea>' +
				'<input type="button" value="削除" onClick="$(this).parent().parent().remove()">' +
			'</td>' +
		'</tr>'
	);
	contents_number++;
}

// 画像パーツを生成する
// 引数:
//       img: 画像名
//       registeredFlg: 登録済みであるかどうか（フォロー・自動プッシュの編集など）
//                      '1' = 登録済み（hp/uploadsフォルダーにある）、'' = 新規（一時フォルダーにある）
function setImgTag( img, registeredFlg ) {
	var imgTag = '';
	if ( typeof img == 'undefined' ) img = '';
	if ( typeof registeredFlg == 'undefined' ) registeredFlg = '';

	if ( img != '' ) {
		imgTag = '<img src="' + img + '" />';
		if ( registeredFlg == '1' ) {
			var folder = $('#type_flg').val() ? 'push_follow' : 'push';
			var masterCd = $('#cd').val();
			imgTag = '<img src="/hp/uploads/c3/' + folder + '/' + masterCd + '/' + img + '" />';
		} else {
			imgTag = '<img src="/system/uploads/' + img + '" />';
		}
	}
	$("#sendForm .tableBox").append(
		'<tr>' +
			'<th>' + (contents_number + 1) + '.画像' +
			'</th>' +
			'<td>' +
				'<div id="gazou' + contents_number + '" class="pic-wrapper">' + imgTag +
				'</div>' +
				'<br/>' +
				'<p id="contents_img' + contents_number + '">&nbsp;</p>' +
				'<input type="hidden" name="contents[' + contents_number + ']" id="contents_img_path' + contents_number + '" value="' + img + '" />' +
				'<input type="hidden" name="registeredFlg[' + contents_number + ']" class="registeredFlg" id="registered_flg' + contents_number + '" value="' + registeredFlg + '" />' +
				'<input type="button" value="削除" onClick="$(this).parent().parent().remove()"><br>' +
				'<div style="margin-top: 7px;">画像用リンク: <input type="text" name="pic_link[' + contents_number + ']" value="" style="width: 400px"></div>' + 
			'</td>' +
		'</tr>'
	);
	setImgEvent( contents_number );
	contents_number++;
}

function setImgEvent( pic_no ){

	new AjaxUpload('#contents_img'+pic_no, {
		action: '/system/inc/ajax_picupload.php?up=true',

		name: 'picfile_'+pic_no,

		onSubmit : function(file, ext){

			//処理開始時の定義関数済み関数の呼び出し  2016.02.09 イケダ
			if (typeof( ajaxUploadWillStartLoad ) !== "undefined" && typeof( ajaxUploadWillStartLoad ) == 'function') {
				ajaxUploadWillStartLoad();
			}

			if (ext && /^(jpg|png|jpeg|gif|bmp)$/.test(ext)){
				var picname = this._settings.name;
				var picname_array = picname.split("_");
				var pic_num = parseInt(picname_array[1]);

				$('#gazou'+pic_num).html('<br><br><img src="/system/img/loading.gif">');

				//alert(this._settings.name);
				tempfilename = uniqid+"pic"+pic_num;

				this.setData({
					'file': picname,
					'ext': ext,
					'tempfilename': tempfilename
				});

			} else {
				alert ('jpg png jpeg gif bmp以外のファイルをアップロード出来ません');
				return false;
			}
		},

		onComplete : function(file, response){
			var picname = this._settings.name;
			var picname_array = picname.split("_");
			var pic_num = parseInt(picname_array[1]);

			if (response.substr(0,5) == "error") {
				$('#gazou'+pic_num).html('');
				if ($('#pic_flg2_'+pic_num).length > 0) $('#pic_flg2_'+pic_num).attr("checked", true); // オプション
				alert (response.substr(6));
				return false;
			} else {
				$("#contents_img_path"+pic_num).val(response);
				$("#registered_flg"+pic_num).val("");
				$('#gazou'+pic_num).load("/system/inc/ajax_picupload.php?f="+response);
				if ($('#pic_flg1_'+pic_num).length > 0) $('#pic_flg1_'+pic_num).attr("checked", true); // オプション
			}

			//処理終了時の定義関数済み関数の呼び出し 2016.02.09 イケダ
			if (typeof( ajaxUploadDidEndLoad ) !== "undefined" && typeof( ajaxUploadDidEndLoad ) == 'function') {
				ajaxUploadDidEndLoad();
			}
		}
	});

}

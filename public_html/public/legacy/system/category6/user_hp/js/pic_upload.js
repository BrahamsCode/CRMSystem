/*
pic_upload.js

Martin Takayama
2010.04.27
*/

$(document).ready(function(){
	for (pic_no=0; pic_no <max_pics; pic_no++) {

		if ($('#photo'+pic_no).length < 1) continue; // HTMLフォームでphotoXXが定義していない時にエラーを防ぐ

		new AjaxUpload('#photo'+pic_no, {
//			action: '/system/category1/inc/ajax_picupload.php?up=true',
			action: './ajax_picupload.php?up=true',

			name: 'picfile_'+pic_no,

			onSubmit : function(file, ext){
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
					$("#pic_name"+pic_num).val(response);
					//$("#form1").append('<input type="hidden" name="pic_name'+pic_num+'" id="pic_name'+pic_num+'" value="'+response+'" />');
//					$('#gazou'+pic_num).load("/system/category1/inc/ajax_picupload.php?f="+response);
					$('#gazou'+pic_num).load("./ajax_picupload.php?f="+response);
					if ($('#pic_flg1_'+pic_num).length > 0) $('#pic_flg1_'+pic_num).attr("checked", true); // オプション
				}
			}
		});
	} // end for (pic_no)
});

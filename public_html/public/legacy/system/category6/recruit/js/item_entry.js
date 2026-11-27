var picExt = picExt2 = '';
var piclastExt = piclastExt2 = '';
var mylastTime = mylastTime2 =0;
var myTime = myTime2 = 0;

$(document).ready(function(){
	new AjaxUpload('#photo', {
		action: 'inc/upload.php',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){
				mylastTime = myTime;
				piclastExt = picExt;
				picExt = ext;
				today = new Date();
				myTime = today.getTime();
				this.setData({
					'ext': picExt,
					'time' : myTime,
					'lastTime' : mylastTime,
					'lastExt' : piclastExt
				});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, ext){
			$("#pic_name1").remove();
//			$("#form1").append("<input type=\"hidden\" name=\"pic_name\" id=\"pic_name\" value=\""+myTime+"\" />");
//			$("#form1").append('<input type="hidden" name="pic_name1" id="pic_name1" value="'+myTime+'" />');
//			$('#gazou').load("inc/disp_pic.php?ext=" + picExt + "&t="+myTime);

			$.get("inc/disp_pic.php?ext=" + picExt + "&t="+myTime, function(data){
				if (data != "ERROR") {
					$("#gazou1").html(data);
					$("#gazou1").append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic(1);">画像解除</a>');
					$("#form1").append("<input type=\"hidden\" name=\"pic_name1\" id=\"pic_name1\" value=\""+myTime+"\" />");
				} else {
					alert('画像サイズが大きすぎます。2MB以下推奨です。');
					$("#gazou1").html('<br><br><br><br><br><br>');
				}
			});
		}
	});

	new AjaxUpload('#photo2', {
		action: 'inc/upload.php',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){
				mylastTime2 = myTime2;
				piclastExt2 = picExt2;
				picExt2 = ext;
				today = new Date();
				myTime2 = today.getTime();
				this.setData({
					'ext': picExt2,
					'time' : myTime2,
					'lastTime' : mylastTime2,
					'lastExt' : piclastExt2
				});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, ext){
			$("#pic_name2").remove();
//			$("#form1").append("<input type=\"hidden\" name=\"pic_name2\" id=\"pic_name2\" value=\""+myTime2+"\" />");
//			$("#form1").append('<input type="hidden" name="pic_name2" id="pic_name2" value="'+myTime2+'" />');
//			$('#gazou2').load("inc/disp_pic.php?ext=" + picExt2 + "&t="+myTime2);

			$.get("inc/disp_pic.php?ext=" + picExt2 + "&t="+myTime2, function(data){
				if (data != "ERROR") {
					$("#gazou2").html(data);
					$("#gazou2").append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic(2);">画像解除</a>');
					$("#form1").append("<input type=\"hidden\" name=\"pic_name2\" id=\"pic_name2\" value=\""+myTime2+"\" />");
				} else {
					alert('画像サイズが大きすぎます。2MB以下推奨です。');
					$("#gazou2").html('<br><br><br><br><br><br>');
				}
			});
		}
	});
});


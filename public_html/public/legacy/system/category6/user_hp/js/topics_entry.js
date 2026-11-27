var picExt;
var piclastExt = '';

var mylastTime;

if(myTime == undefined){
	var myTime = 0;
}
//alert(myTime);

$(document).ready(function(){
	new AjaxUpload('#photo', {
		action: 'inc/upload_topics.php',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){

				mylastTime = myTime;

				piclastExt = picExt;

				picExt = ext;

				today = new Date();
				
				myTime = today.getTime();
				
				mytitle = $("#title").val();

				mynumber = $("#bangou").val();

				mynaiyou = $("#naiyou").val();

				mykigen = $("#kigen").val();

				this.setData({

					'ext': picExt,

					'time' : myTime,

					'lastTime' : mylastTime,

					'lastExt' : piclastExt,

					'title' : mytitle,

					'number' : mynumber,

					'naiyou' : mynaiyou,

					'kigen' : mykigen

				});

			} else {

				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');

				return false;				

			}

		},

		onComplete : function(file, ext){

			$("#pic_name").remove();

			$("#form1").append("<input type=\"hidden\" name=\"pic_name\" id=\"pic_name\" value=\""+myTime+"\" />");

			$('#gazou').load("inc/disp_pic.php?ext=" + picExt + "&t="+myTime, function() {
				$('#gazou').append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像添付解除</a>');
			});

			$('#zip_search').click();

		}

	});

	

});


/*
pdf_upload.js

Martin Takayama
2010.04.27
*/

$(document).ready(function(){
	for (pdf_no=0; pdf_no <max_pdfs; pdf_no++) {

		if ($('#pdf'+pdf_no).length < 1) continue; // HTMLフォームでpdfXXが定義していない時にエラーを防ぐ

		new AjaxUpload('#pdf'+pdf_no, {
			action: '/system/inc/ajax_pdfupload.php',

			name: 'pdffile_'+pdf_no,

			onSubmit : function(file, ext){
				if (ext && /^(pdf)$/.test(ext)){
					pdfname = this._settings.name;
					pdfname_array = pdfname.split("_");
					pdf_num = parseInt(pdfname_array[1]);

					$('#pdf_gazou'+pdf_num).html('<br><br><img src="/system/img/loading.gif">');

					//alert(this._settings.name);
					tempfilename = uniqid+"pdf"+pdf_num;

					this.setData({
						'file': pdfname,
						'ext': ext,
						'tempfilename': tempfilename
					});

				} else {
					alert ('pdf以外のファイルをアップロード出来ません');
					return false;
				}
			},

			onComplete : function(file, response){
				pdfname = this._settings.name;
				pdfname_array = pdfname.split("_");
				pdf_num = parseInt(pdfname_array[1]);

				if (response.substr(0,5) == "error") {
					$('#pdf_gazou'+pdf_num).html('');
					$('#pdf_disp_flg2_'+pdf_num).attr("checked", true); // オプション
					alert (response.substr(6));
					return false;
				} else {
					$("#pdf_name"+pdf_num).val(response);
					//$("#form1").append('<input type="hidden" name="pdf_name'+pdf_num+'" id="pdf_name'+pdf_num+'" value="'+response+'" />');
					$('#pdf_gazou'+pdf_num).load("/system/inc/ajax_pdfupload.php?f="+response);
					$('#pdf_disp_flg1_'+pdf_num).attr("checked", true); // オプション
				}
			}
		});
	} // end for (pdf_no)
});

//送信先URL
var url_  = 'contact_send.php';

//フォームid名
var fName = '#contactForm';


$(function() {

	$("body").append('<div id="dialog" style="display: none;"></div>');
	
	$.fn.autoKana('#name',  '#kana', { katakana : true }); // フリガナ変換
	//$.fn.autoKana('#name',  '#kana', { katakana : false }); // ふりがな変換


	// 郵便番号
	$('#zip1').jpostal({
		postcode : [
			'#zip1', //郵便番号上3ケタ
			'#zip2'  //郵便番号下4ケタ
		],
		address : {
			'#pref'  : '%3', //都道府県
			'#city'  : '%4%5' //市区町村 町域
		}
	});

	// バリデーションチェック
	/*
	validate[required] : requiredを入れる事により入力チェック
	custom[phone] : ハイフンを含む20文字以下の数値か判定
	custom[email] : ＠マークを含むメールアドレス形式
	custom[url] : httpを含むURL
	custom[integer] : 整数半角のみ
	custom[onlyNumberSp] : 半角数字のみ
	custom[onlyLetterSp] : 半角アルファベットのみ
	custom[onlyLetterNumber] : 半角英数のみ
	*/

	$(fName).validationEngine('attach', {
		ajaxFormValidation         : true,
		onBeforeAjaxFormValidation : submitForm
	});

	// 送信チェック
	function submitForm() {
		
	    $("#dialog").html('送信してよろしいですか？');
		    $("#dialog").dialog({
	        resizable: false,
	        draggable: false,
	        closeOnEscape: false,
	        open: function(event, ui) {
	            $(".ui-dialog-titlebar-close").hide();
	        },
	        modal: true,
	        title: '確認',
	        width: 400,
	        height: 400,
	        buttons: {
	            'OK': function() {
	                submitData();
	            },
	            '閉じる': function() {
	                $(this).dialog('close');
	            }
	        }
	    });
	}

	// 送信データ処理
	function submitData(){
		var f = $(fName);
		var method_ = f.prop('method');
		var formdata = new FormData( f.get(0) );


		// POSTでアップロード
		$.ajax({
			url    : url_,
			method : method_,
			type   : 'POST',
			data : formdata,
			cache       : false,
			contentType : false,
			processData : false,
			success: function(data) {
			//	console.log(data);
				$("#dialog").dialog({
			        buttons: {}
			    });
				$("#dialog").html("送信完了しました。");
				setTimeout(function() {
                compPage('contact.php');
	            }, 1000);
			}
		}).fail(function(data) {
			$("#dialog").dialog({
		        buttons: {}
		    });
			$("#dialog").html("送信失敗しました！");
			setTimeout(function() {
            compPage('contact.php');
            }, 1000);
		});
	}


	// 完了後ページ
	function compPage(url) {
		location.href = url + '?sendFlg=1';
	}

});

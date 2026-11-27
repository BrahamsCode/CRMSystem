// *************************************************************************************


//	touch系操作対応のボタンスタイル設定プラグイン

//  2011.10.28 ohno.


// *************************************************************************************


/* -------------------------------------------------------------------------------------


	■使い方の説明■

	①jsとcssファイルを読み込む

	②ファイル内で対象を指定してプラグインを実行する
	  必要ならば引数で設定を変更する


	■オプション設定■

	btnClass:		対象となるボタンクラス名
					ここで指定した文字列を含むすべてのエレメントが対象となる
					'btnStyle'を指定した場合、'btnStyleWhite'も'btnStyleBlack'も対象に含まれる
					初期値は'btnStyle'

					※一つのエレメントにクラスを複数設定している場合は、このクラスを最初に書いてください。
					  2個目以降に書くとセレクタの検索条件にヒットしません。

	touchedClass:	touch時に追加されるクラス名
					イベント'touchstart'時に追加され、'touchend'時に削除される
					初期値は'touched'


	■使用例■

	<head>

	<link rel="stylesheet" type="text/css" href="./plugin/touch_btn.css" />
	<script type="text/javascript" src="./plugin/touch_btn.js"></script>

	<script type="text/javascript">
	<!-- 
		$(document).ready(function(){

			$('#btnArea').setTouchBtn({ btnClass: 'myBtnStyle', touchedClass: 'myTouchedStyle' });

		});
	//-->
	</script>

	<title>My FlexSelect Sample</title>

	</head>
	<body>

		<div id="btnArea">
		<input type="button" name="logout" id="logout" class="btnStyleWhite" value="ログアウト" />
		<div id="searchSelectBtn" class="btnStyleBlack">会員IDを入力して検索する ≫</div>
		<!--■■ /#btnArea ■■ --></div>

	</body>


-------------------------------------------------------------------------------------- */



//	************************************************************************************
//
//		プラグインの設定
//
//	************************************************************************************

(function($) {





	// =====================================================================================
	// setTouchBtnオブジェクトを作成
	// =====================================================================================

	$.setTouchBtn = function(target, opt) { this.init(target, opt); };





	// =====================================================================================
	// setTouchBtnオブジェクトを拡張（ここでの内容が継承される）
	// =====================================================================================

	$.extend($.setTouchBtn.prototype, {



		// 初期設定 ----------------------------------------------------------------------------

		target: null,				// 対象となるエレメント

		settings: {

			btnClass: 'btnStyle',	// 対象となるボタンクラス名
			touchedClass: 'touched'	// touch時に追加されるクラス名

		},



		// 初期化 ------------------------------------------------------------------------------

		init: function(target, opt) {

			this.settings = $.extend({}, this.settings, opt);	// 初期設定にオプション設定を拡張して返す
			this.target = target;								// 対象となるエレメントを設定

			this.setTouched();	// イベントを設定（touch時にクラスを追加）

		},



		// イベントを設定（touch時にクラスを追加） ---------------------------------------------

		setTouched: function() {

			var self = this;	// each内で有効にするためthisを変数に代入

			$('*[class^='+self.settings.btnClass+']',self.target).each(function(){

				$(this).bind({

					touchstart: function(){
						$(this).addClass(self.settings.touchedClass);
					},
					touchend: function(){
						$(this).removeClass(self.settings.touchedClass);
					}

				});

			});

		},



	});






	// =====================================================================================
	// jQueryエレメントに新しいメソッドとしてsetTouchBtnを追加
	// =====================================================================================

	$.fn.setTouchBtn = function(opt) {

		// 対象にプラグインを適用して返す
		this.each(function() {
			new $.setTouchBtn(this, opt);
		});
		return this;

	};





})(jQuery);






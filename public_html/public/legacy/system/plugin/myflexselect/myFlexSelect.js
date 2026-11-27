// *************************************************************************************


//	テキスト＆セレクト複合入力自動作成プラグイン

//  2011.10.21 ohno.


// *************************************************************************************


/* -------------------------------------------------------------------------------------


	■使い方の説明■

	①jsとcssファイルを読み込む

	②ファイル内で対象と、選択肢の内容、オプションを指定してプラグインを実行する


	■オプション設定■

	options:		SELECTの選択肢（配列）

	dropdownSize:	SELECTの展開サイズ（選択肢何個分の高さを表示するか）
					初期値は5

	selectedClass:	プラグインで生成するSELECTのselected表示用クラス
					初期値は'flexselect_selected'

	dropdownClass:	プラグインで生成するSELECT共通クラス
					初期値は'flexselect_dropdown'


	■使用例■

	<head>

	<link rel="stylesheet" type="text/css" href="./plugin/myFlexSelect.css" />
	<script type="text/javascript" src="./plugin/myFlexSelect.js"></script>

	<script type="text/javascript">
	<!-- 
		$(document).ready(function(){

			var options = ['月','火','水','木','金','土','日'];

			$('#week').myFlexSelect({ options: options, dropdownSize: 3 });

		});
	//-->
	</script>

	<title>My FlexSelect Sample</title>

	</head>
	<body>

		<input type="text" name="week" id="week" size="8" value="" />

	</body>


-------------------------------------------------------------------------------------- */



//	************************************************************************************
//
//		プラグインの設定
//
//	************************************************************************************

(function($) {





	// =====================================================================================
	// myFlexSelectオブジェクトを作成
	// =====================================================================================

	$.myFlexSelect = function(target, opt) { this.init(target, opt); };





	// =====================================================================================
	// myFlexSelectオブジェクトを拡張（ここでの内容が継承される）
	// =====================================================================================

	$.extend($.myFlexSelect.prototype, {



		// 初期設定 ----------------------------------------------------------------------------

		settings: {

			options: [],		// SELECTの選択肢（配列）
			dropdownSize: 5,	// SELECTの展開サイズ

			selectedClass: "flexselect_selected",	// selected表示用クラス
			dropdownClass: "flexselect_dropdown",	// プラグインで生成するSELECT共通クラス

			dropdownIdTransform: function(id) { return id + "_flexselect"; } // ターゲットのID名からSELECTのID名を生成して返す

		},

		target: null,				// 対象となるDOM
		dropdown: null,				// SELECTの外枠
		dropdownList: null,			// SELECTのリスト
		dropdownOptions: null,		// SELECTの選択肢
		dropdownID: null,			// SELECTのID名

		dropdownMouseover: false,	// IE対策



		// 初期化 ------------------------------------------------------------------------------

		init: function(target, opt) {

			this.settings = $.extend({}, this.settings, opt);	// 初期設定にオプション設定を拡張して返す
			this.target = $(target);							// 対象となるDOM

			// SELECTを作成
			if (this.settings.options.length > 0) {
				this.createSelectBox();
			} else {
				return false;
			}

		},



		// SELECTを作成 ------------------------------------------------------------------------

		createSelectBox : function() {


			var self = this;				// each内で有効にするためthisを変数に代入
			var val = self.target.val();	// ターゲットの初期値
			var selected = '';				// リストのselected属性を表示するクラス

			// SELECTの外枠を設定
			self.dropdownID = self.settings.dropdownIdTransform(self.target.attr('id'));

			$('body').append('<div id="'+self.dropdownID+'" class="'+self.settings.dropdownClass+'"><ul></ul></div>');
			self.dropdown = $('#'+self.dropdownID);
			self.dropdownList = $('#'+self.dropdownID+' ul');

			// SELECTの選択肢を設定してリストにいれる
			self.dropdownOptions = '';
			$.each(self.settings.options, function() {
				selected = (this == val) ? ' class="'+self.settings.selectedClass+'"': '';
				self.dropdownOptions += '<li'+selected+'>'+this+'</li>';
			});
			self.dropdownList.append(self.dropdownOptions);


			// ターゲットのイベント設定
			self.setTargetEvent(self);

			// SELECTのサイズ調整
			self.adjustSelectSize();

			// SELECTのイベント設定
			self.setSelectEvent(self);


		},



		// ターゲットのイベント設定 ------------------------------------------------------------

		setTargetEvent: function(self) {

			// focusでSELECTを表示
			this.target.focus(function (){
				self.dropdown.show();
			});

			// blurでSELECTを非表示
			this.target.blur(function (){
				self.dropdown.hide();
			});

			// キーイベント
			this.target.keyup(function(event) {
				switch (event.keyCode) {

					case 13: // return
						event.preventDefault();
						this.blur();
						break;

					case 27: // esc
						event.preventDefault();
						this.blur();
						break;

					default:
						break;

				}
			});

		},



		// SELECTのサイズ調整と初期位置の設定 --------------------------------------------------

		adjustSelectSize: function() {

			var dropdownBorderWidth = this.dropdown.outerWidth() - this.dropdown.innerWidth();
			var inputOffset = this.target.offset();

			// SELECTのサイズ調整と初期位置の設定
			this.dropdown.css({
				width: (this.target.outerWidth() - dropdownBorderWidth) + "px",
				height: ($('#'+this.dropdownID+' li').innerHeight()*this.settings.dropdownSize) + 'px',
				top: (inputOffset.top + this.target.outerHeight()) + "px",
				left: inputOffset.left + "px",
				display: 'none'
			});

			// inputのautocompleteも切っておく
			if (this.target.get(0).tagName.toUpperCase() == 'INPUT') {
				this.target.attr({ autocomplete: 'off' });
			}

		},



		// SELECTのイベント設定 ------------------------------------------------------------

		setSelectEvent: function(self) {

			// SELECTの選択をターゲットに反映させる
			$('li',this.dropdownList).click(function (event) {

				$('li',self.dropdownList).removeClass(self.settings.selectedClass);
				$(this).addClass(self.settings.selectedClass);

				self.target.val($(this).html()).blur();

			});

			// windowがリサイズされたときの位置修正
			$(window).resize(function () {
				var inputOffset = self.target.offset();
				self.dropdown.css({
					top: (inputOffset.top + self.target.outerHeight()) + "px",
					left: inputOffset.left + "px",
				});
			});

			// IE対策とイベントキャンセル
			this.dropdown.mouseover(function (event) {
				self.dropdownMouseover = true;
			});
			this.dropdown.mouseleave(function (event) {
				self.dropdownMouseover = false;
			});
			this.dropdown.mousedown(function (event) {
				event.preventDefault();
			});

		}



	});





	// =====================================================================================
	// jQueryエレメントに新しいメソッドとしてmyFlexSelectを追加
	// =====================================================================================

	$.fn.myFlexSelect = function(opt) {

		// 対象にプラグインを適用して返す
		this.each(function() {
			new $.myFlexSelect(this, opt);
		});
		return this;

	};





})(jQuery);






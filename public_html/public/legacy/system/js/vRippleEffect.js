// *************************************************************************************


//	波紋エフェクトプラグイン

//	version   : 1.00
//	copyright : 2014.07.22 大野 恭彦


// *************************************************************************************


/* -------------------------------------------------------------------------------------


	■■■■使い方の説明■■■■

	①プラグインのjsファイルとCSSファイルを読み込む

	②ファイル内で対象に必要なオプションを指定してプラグインを実行する


	■■■■オプション設定■■■■

	color:		[値] エフェクトの色
	duration:	[値] アニメーションの時間

	type:		[オプション] イベントタイプ
							 指定の種類は'defalt'で'mouseup'と'touchend'、'hover'で'mouseenter'
							 初期値は'defalt'


	■■■■補足■■■■

	・CSS3を使用しているので基本的にモダンブラウザでないと使えません。
	  ただしIEについてはIE10以降でないと対応していないようです。
	  IE・Chrome・FireFoxの最新版では動作確認済み（2014.07.22）


	■■■■使用例■■■■

	<head>

	<script type="text/javascript" src="./plugin/vRippleEffect.js"></script>

	<script type="text/javascript">
	<!-- 
		$(document).ready(function(){

			$.vRippleEffect.data.color = '#FF0000';

			$('#sampleList').find('li:eq(0)').vRippleEffect();
			$('#sampleList').find('li:eq(1)').vRippleEffect({ color: '#FF848E', duration: 1.5 });
			$('#sampleList').find('li:eq(2)').vRippleEffect({ color: '#E900FF', duration: 3.5 });
			$('#sampleList').find('li:eq(3)').vRippleEffect({ color: '#72DEFF', duration: 0.35, type: 'hover' }).vRippleEffect({ color: '#E900FF', duration: 0.35 });
			$('#sampleList').find('li:eq(4) a').vRippleEffect({ color: '#1462FF', duration: 0.5, type: 'hover' });

		});
	//-->
	</script>

	<title>vRippleEffect Sample</title>

	</head>
	<body>

		<ul id="sampleList">

		<li><a href="./#id1">sample-01</a></li>
		<li><a href="./#id2">sample-02</a></li>
		<li><a href="./#id3">sample-03</a></li>
		<li><a href="./#id4">sample-04</a></li>
		<li><a href="./#id5">sample-05</a></li>

		</ul>

	</body>


-------------------------------------------------------------------------------------- */





//	************************************************************************************
//
//		プラグインの設定
//
//	************************************************************************************

(function($) {





	// =====================================================================================
	// vRippleEffectオブジェクトを作成
	// =====================================================================================

	$.vRippleEffect = {



		data: {

			// 初期設定データ（外部から設定変更可能）

			color:		null,		// [値] エフェクトの色
			duration:	null,		// [値] アニメーションの時間

			type:		'default'	// [オプション] イベントタイプ（ 'defalt': mouseup / touchend, 'hover': mouseenter ）

		},



		act: function(target, opt) {

			// 初期設定データを読み込んでプラグインを設置

			this.settings = $.extend({}, this.settings, $.vRippleEffect.data);
			this.init(target, opt);

		}



	}





	// =====================================================================================
	// vSpinBtnオブジェクトを拡張（ここでの内容が継承される）
	// =====================================================================================

	$.extend($.vRippleEffect.act.prototype, {



		// 初期設定 ----------------------------------------------------------------------------

		jqversion: ($().jquery).split('.'),	// jqueryのバージョン

		settings: {},		// 初期設定データ
		eventName: {},		// デバイスに対応したイベント名
		target: null,		// 対象となるエレメント



		// 初期化 ------------------------------------------------------------------------------

		init: function(target, opt) {


			var smartPhone = ((navigator.userAgent).match(/iPhone|iPod|iPad|Android/i)) ? true: false
			  , mouseEvent = { mousedown: 'mousedown',  mouseup: 'mouseup',  mousemove: 'mousemove' }
			  , touchEvent = { mousedown: 'touchstart', mouseup: 'touchend', mousemove: 'touchmove' };

			this.eventName  = ( smartPhone ) ? touchEvent : mouseEvent; // デバイスに対応したイベントを設定

			this.settings = $.extend({}, this.settings, opt);	// 初期設定にオプション設定を拡張して返す
			this.target = $(target);							// 対象となるエレメント

			// 波紋エフェクト設定
			this.addRippleEffect();


		},



		// 波紋エフェクト設定 ------------------------------------------------------------------

		addRippleEffect: function(){



			var self    = this	// each内で有効にするためthisを変数に代入
			  , myStyle = {};	// 個別のスタイル設定

			if (self.settings.color) {
				myStyle['background'] = self.settings.color;
			}
			if (self.settings.duration) {
				myStyle['animation-duration'] = self.settings.duration +'s';
			}

			var myEvent = self.eventName['mouseup']; // イベントタイプ

			if (self.settings.type == 'hover' && self.eventName['mouseup'] == 'mouseup') {
				myEvent = 'mouseenter';
			}

			self.target.css({ position: 'relative', overflow: 'hidden' });

			self.target.bind(myEvent, function(e) {


				// エフェクト用のレイヤーが未追加なら追加する
				if (self.target.find('span.v_ripple_effect_animation_layer').length == 0) {
					self.target.prepend('<span class="v_ripple_effect_animation_layer"></span>');
				}

				// レイヤー初期化
				var rippleLayer = self.target.find('span.v_ripple_effect_animation_layer');

				if (!rippleLayer.height() && !rippleLayer.width()) {
					var d = Math.max(self.target.outerWidth(), self.target.outerHeight());
					rippleLayer.css({ width: d, height: d });
				}
				rippleLayer.removeClass('animate').css(myStyle);

				// エフェクト
				var pos = self.target.offset()
				  , x = e.pageX - pos.left - rippleLayer.width() / 2
				  , y = e.pageY - pos.top - rippleLayer.height() / 2;

				rippleLayer.css({ top: y +'px', left: x +'px' }).addClass('animate');


			});



		}



	});





	// =====================================================================================
	// jQueryエレメントに新しいメソッドとしてvRippleEffectを追加
	// =====================================================================================

	$.fn.vRippleEffect = function(opt) {



		// 対象にプラグインを適用して返す
		this.each(function() {
			new $.vRippleEffect.act(this, opt);
		});
		return this;



	};





})(jQuery);






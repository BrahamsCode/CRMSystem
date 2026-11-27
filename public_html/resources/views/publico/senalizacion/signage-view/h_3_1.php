<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>デジタルサイネージ</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="">
<meta name="author" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" type="text/css" href="/signage/view/css/reset.css">
<link rel="stylesheet" type="text/css" href="/signage/view/css/common.css">
<link rel="stylesheet" type="text/css" href="/signage/view/css/jquery.bxslider.css">
<link rel="shortcut icon" href="/signage/view/img/favicon.ico" >
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">


<script src="/signage/view/js/jquery-1.11.0.min.js"></script>
<script src="/signage/view/js/jquery.bxslider.js"></script>

<script>

	function slider1() {
		
	<?php if ( isset( $_cycle_data ) && (is_countable(($_cycle_data ?? null)) ? count( ($_cycle_data ?? null) ) : (isset($_cycle_data) ? 1 : 0)) > 0 ) { ?>
		<?php foreach ( ($_cycle_data ?? null) as $c_k => $c_v ) { ?>
		<?php
			$cnt = 0;
			if ( $c_v['flame_cd'] == 1 ) {
				$cnt = 3;
			} else if ( $c_v['flame_cd'] == 2 || $c_v['flame_cd'] == 3 ) {
				$cnt = 2;
			} else {
				$cnt = 1;
			}
			
			foreach ( range( 1, $cnt ) as $k => $v ) { //この辺りが A ～ Dの代わり 
		?> 
		var obj1 = $('.bxslider<?=($c_k+1) . "_" . ($k+1)?>').bxSlider({
			
			auto:true,	//スライドショーを自動再生
			pause:6000,	//3.5秒ポーズ（停止して次のスライド）
			<?php if ( isset( $c_v['action_ary'][$k]['action'] ) && $c_v['action_ary'][$k]['action'] == 1 ) { ?>
			mode: 'vertical', //画像の切り替えを垂直方向の縦スライド。縦は'vertical'。横は'horizontal（初期値）'。フェードは'fade'。
			<?php } else if ( isset( $c_v['action_ary'][$k]['action'] ) && $c_v['action_ary'][$k]['action'] == 2 ) { ?>
			mode: 'horizontal', //画像の切り替えを垂直方向の縦スライド。縦は'vertical'。横は'horizontal（初期値）'。フェードは'fade'。
			<?php } else if ( isset( $c_v['action_ary'][$k]['action'] ) && $c_v['action_ary'][$k]['action'] == 3 ) { ?>
			mode: 'fade', //画像の切り替えを垂直方向の縦スライド。縦は'vertical'。横は'horizontal（初期値）'。フェードは'fade'。
			<?php } else { ?>
			mode: 'vertical', //画像の切り替えを垂直方向の縦スライド。縦は'vertical'。横は'horizontal（初期値）'。フェードは'fade'。
			<?php } ?>
			autoHover:false, //スライダーにマウスオーバーすると一時停止する
			moveSlides: 1, //指定された値だけスライドする
			minSlides: 1, //１スライドに表示するサムネイルの数
			maxSlides: 1, //１スライドに表示するサムネイルの最大数
			<?php if ( $c_v['flame_cd'] == 1 ) { ?> 
			slideWidth: 1920, //スライダーの横幅
			<?php } else if ( $c_v['flame_cd'] == 2 ) { ?> 
				<?php if ( $v == 1 ) { ?>
			slideWidth: 1920, //スライダーの横幅
				<?php } else { ?>
			slideWidth: 3840, //スライダーの横幅
				<?php } ?>
			<?php } else if ( $c_v['flame_cd'] == 3 ) { ?> 
				<?php if ( $v == 1 ) { ?>
			slideWidth: 3840, //スライダーの横幅
				<?php } else { ?>
			slideWidth: 1920, //スライダーの横幅
				<?php } ?>
			<?php } else { ?>
			slideWidth: 5760, //スライダーの横幅
			<?php } ?>
			slideMargin: 0, //スライダー間のマージン
			speed: 2000, //スライダーのスピード
			
			startSlide: 0, //最初の画像をどれにするか？？
			randomStart: false, //最初のスライドをランダムに表示させる。
			infiniteLoop: true, //無限に繰り返すかどうか
			captions: true, //キャプションを表示するかどうか。キャプションは img 要素の title 属性に記述
			
			video: false, //スライドにビデオが含まれる場合は「true」に設定してダウンロードした「plugins」。フォルダの「jquery.fitvids.js」も読み込むようにする。
			responsive: true, //boolean (true / false) レスポンシブ対応にするかどうか
			useCSS: true, //boolean (true / false) true の場合、CSS の transitions が使われ、 false の場合は、jQuery animate() が使われる。
			pager: false, //boolean (true / false) ページャー（丸いアイコン）の表示、サムネル表示の場合もtrue
			controls: false, //boolean (true / false) コントロール（Next, Prev）を表示するかどうか
			
			autoControls: false, //boolean (true / false) true の場合 Stop, Start のコントロールが表示される
			autoControlsCombine: false, //boolean (true / false) true の場合スライドが実行されていれば Stop のみが表示され、停止しているときは Start のみが表示される
			
		});
		<?php } ?>
		<?php } ?>
	<?php } ?>
		
	}
	

	$(document).ready(function(){

		slider1();
		
	});

<?php if ( isset( $_cycle_data ) && (is_countable(($_cycle_data ?? null)) ? count( ($_cycle_data ?? null) ) : (isset($_cycle_data) ? 1 : 0)) > 0 ) { ?>
var nowCnt = 1;
var gradCnt = <?=(is_countable(($_cycle_data ?? null)) ? count( ($_cycle_data ?? null) ) : (isset($_cycle_data) ? 1 : 0))?>;

$(document).ready(function(){
	
	vh_height = $(".img_base").height();
	
	$(".bx-viewport").each(function(){
		
		v = $(this).closest( ".mvCanceler" ).attr("data-level");
		
		$(this).css( "top", "-" + (vh_height * (v-1)) + "px" );
		//$(this).find("*[class^=bxslider]").each(function(){
		//	$(this).fadeOut();
		//});
		
	});
	
	$(".cycle_group" + nowCnt).find(".bx-viewport").css("z-index", 1);
	$(".cycle_group" + nowCnt).find(".bx-viewport").animate({ opacity: "1"});
	
	function loop() {
		id = setInterval("dateDisp2()",1000);
	}
	
	loop();
	
});

function dateDisp(){
	$(".cycle_group" + nowCnt).find(".bx-viewport").css("z-index", "");
	$(".cycle_group" + nowCnt++).find(".bx-viewport").animate({ opacity: "0"});
	if ( nowCnt > gradCnt ) {
		nowCnt = 1;
	}
	$(".cycle_group" + nowCnt).find(".bx-viewport").css("z-index", 1);
	$(".cycle_group" + nowCnt).find(".bx-viewport").animate({ opacity: "1"});
}

setInterval("dateDisp()",30000);

var data = {};
var n_id = null;
var first_flg = false;

function dateDisp2(){
	$.ajax({
		type: "GET", // el legacy enviaba "GETT" (errata): Apache+PHP lo aceptaba, Laravel responde 405
		url: "<?=($_base_url ?? null)?>/view/ajax.php?packages_cd=<?=\App\Services\Senalizacion\LegacyHtml::h(($_request['cd'] ?? null))?>",
		dataType: 'json',
		success: function(j_data){
			
			var object1String = JSON.stringify(data); 
			var object2String = JSON.stringify(j_data);
			
			if (object1String === object2String) {
				
			} else { 
				
				if ( first_flg ) {
					location.reload();
				}
				
				first_flg = true;
				
				clearInterval( n_id );
				
				data = j_data;
				
			}
			
			data = j_data;
		},
		error: function(j_data){
			console.log("error");
		}
	});
}

<?php } ?>

</script>

<style>

.cycle_group_base {
	z-index: 0;
}

.cycle_group_base .bx-viewport {
	opacity: 0;
}

html{
    overflow: hidden;
}



<?php if ( isset( $_cycle_data ) && (is_countable(($_cycle_data ?? null)) ? count( ($_cycle_data ?? null) ) : (isset($_cycle_data) ? 1 : 0)) > 0 ) { ?>
	<?php foreach ( ($_cycle_data ?? null) as $c_k => $c_v ) { ?>
	<?php
		$cnt = 0;
		if ( $c_v['flame_cd'] == 1 ) {
			$cnt = 3;
		} else if ( $c_v['flame_cd'] == 2 || $c_v['flame_cd'] == 3 ) {
			$cnt = 2;
		} else {
			$cnt = 1;
		}
		
		foreach ( range( 1, $cnt ) as $k => $v ) { //この辺りが A ～ Dの代わり 
	?> 
	/*
	#cover<?=($c_k+1) . "_" . ($k+1)?> .bx-viewport{
		top: -<?=270*($c_k)?>px !important;
	}
	*/
	
	<?php if ( $c_v['flame_cd'] == 1 ) { ?> 
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 33.333%;
		float: left;
	}
	<?php } else if ( $c_v['flame_cd'] == 2 ) { ?> 
		<?php if ( $v == 1 ) { ?>
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 33.333%;
		float: left;
	}
		<?php } else { ?>
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 66.666%;
		float: left;
	}
		<?php } ?>
	<?php } else if ( $c_v['flame_cd'] == 3 ) { ?> 
		<?php if ( $v == 1 ) { ?>
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 66.666%;
		float: left;
	}
		<?php } else { ?>
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 33.333%;
		float: left;
	}
		<?php } ?>
	<?php } else { ?>
	#cover<?=($c_k+1) . "_" . ($k+1)?>
	{
		width: 100%;
		float: left;
	}
	<?php } ?>
	<?php } ?>
	<?php } ?>
<?php } ?>
</style>

<!--meta http-equiv="refresh" content="900"-->
</head>


<body id="pTop">
	<div id="coverBox">
	<?php if ( isset( $_cycle_data ) && (is_countable(($_cycle_data ?? null)) ? count( ($_cycle_data ?? null) ) : (isset($_cycle_data) ? 1 : 0)) > 0 ) { ?>
		<?php foreach ( ($_cycle_data ?? null) as $c_k => $c_v ) { ?>

		<div class="cycle_group_base cycle_group<?=($c_k+1)?>">
		<?php
			$cnt = 0;
			if ( $c_v['flame_cd'] == 1 ) {
				$cnt = 3;
			} else if ( $c_v['flame_cd'] == 2 || $c_v['flame_cd'] == 3 ) {
				$cnt = 2;
			} else {
				$cnt = 1;
			}
			
			//行に対して表示の有無のチェック
			$is_file_array = array();
			foreach ( range( 1, $cnt ) as $k => $v ) { //この辺りが A ～ Dの代わり 
				foreach ( range( 1, 5 ) as $num => $num_val ) { 
					if ( !isset( $is_file_array[$num_val] ) ) {
						$is_file_array[$num_val] = false;
					}
					$_c  = ($_contents_data[$c_v['img_path_ary'][$k][$num]] ?? null); 
					$_cc = ($_contents_category_data[$_c['contents_category_cd']] ?? null); 
					$f   = $_signage_root . "/upload/" . $_cc['width'] . "x" . $_cc['height'] . "/" . $_c['img_path'];  
					if ( is_file( $f ) ) {
						$is_file_array[$num_val] = true;
					}
				}
			}
			
			foreach ( range( 1, $cnt ) as $k => $v ) { //この辺りが A ～ Dの代わり 
		?> 
			<div class="mvCanceler" id="cover<?=($c_k+1) . "_" . ($k+1)?>" data-level="<?=($c_k+1)?>">
				<ul class="bxslider<?=($c_k+1) . "_" . ($k+1)?>">
				<?php 
					foreach ( range( 1, 5 ) as $num => $num_val ) { 
						
						if ( !$is_file_array[$num_val] ) {
							continue;
						}
						
						$_c  = ($_contents_data[$c_v['img_path_ary'][$k][$num]] ?? null); 
						$_cc = ($_contents_category_data[$_c['contents_category_cd']] ?? null); 
						$f   = $_signage_root . "/upload/" . $_cc['width'] . "x" . $_cc['height'] . "/" . $_c['img_path'];  
				?> 
					<li class="cover<?=$num_val?>"> 
					<?php if ( is_file( $f ) ) { ?> 
						<img class="img_base" src="<?=($_base_url ?? null) . "/upload/" . $_cc['width'] . "x" . $_cc['height'] . "/" . $_c['img_path']?>?q=<?=filemtime($f)?>" alt="<?=\App\Services\Senalizacion\LegacyHtml::h($_c['name'])?>">
					<?php } else { ?>
						
						<?php if ( $c_v['flame_cd'] == 1 ) { ?> 
							<img src="/signage/img/blank1920x1080.jpg" alt="準備中">
						<?php } else if ( $c_v['flame_cd'] == 2 ) { ?>
							<?php if ( $v == 1 ) { ?>
								<img src="/signage/img/blank1920x1080.jpg" alt="準備中">
							<?php } else { ?>
								<img src="/signage/img/blank3840x1080.jpg" alt="準備中">
							<?php } ?>
						<?php } else if ( $c_v['flame_cd'] == 3 ) { ?>
							<?php if ( $v == 1 ) { ?>
								<img src="/signage/img/blank3840x1080.jpg" alt="準備中">
							<?php } else { ?>
								<img src="/signage/img/blank1920x1080.jpg" alt="準備中">
							<?php } ?>
						<?php } else { ?>
								<img src="/signage/img/blank5760x1080.jpg" alt="準備中">
						<?php } ?>
						
					<?php } ?>
					</li>
				<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	<?php } ?>
	</div>
	
</body>

</html>
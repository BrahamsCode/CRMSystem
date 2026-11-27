<?php /* Convertido de signage/index.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content main-wrap">
		<div class="title-box">
			<h1 class="page-title">ホーム</h1>
		</div>
	
		
		
		
		<h2 class="page-title-h2">現在表示中のサイネージ</h2>
		<?php if ( !count( $data ) ) { ?>
			<p>データがありません。</p>
		<?php } else { ?>
			
			<?php foreach ( $data as $key => $val ) {
				
				$_e = explode( "_", $val['display_type'] );
				if ( $_e[0] == "v" ) {
					$width  = 90 * $_e[1];
					$height = 160 * $_e[2];
				} else { 
					$width  = 160 * $_e[1];
					$height = 90 * $_e[2];
				}
				
			?>
			<div class="sub-wrap">
				<div class="terminal-number-box">
					<span>端末番号 : <?=$val['cd']?></span><br>
				</div>
				<a 
					href="<?=url('/signage/view')?>?cd=<?=$val['cd']?>"
					target="_blank"
					style="margin-top: 25px;"
				><?=url('/signage/view')?>?cd=<?=$val['cd']?></a><br>
				<iframe src="<?=url('/signage/view')?>?cd=<?=$val['cd']?>" width="<?=$width?>" height="<?=$height?>"></iframe>
				<br>
			</div>
			<?php } ?>
		<?php } ?>
	</div>
</div>

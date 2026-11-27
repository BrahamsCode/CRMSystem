
<?php if ( ($val['flame_cd'] ?? null) == 1 ) { ?>
	<span class="">パターン1 (3分割)</span>
<?php } else if( ($val['flame_cd'] ?? null) == 2 ) { ?>
	<span class="">パターン2 (2分割①)</span>
<?php } else if( ($val['flame_cd'] ?? null) == 3 ) { ?>
	<span class="">パターン3 (2分割②)</span>
<?php } else if( ($val['flame_cd'] ?? null) == 4 ) { ?>
	<span class="">パターン4 (分割なし)</span>
<?php } ?>
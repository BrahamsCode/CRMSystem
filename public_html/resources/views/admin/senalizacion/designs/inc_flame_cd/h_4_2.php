<?php

if ( ($val['flame_cd'] ?? null) == 1 ) {

	echo "8分割";

} else if ( ($val['flame_cd'] ?? null) >= 2 && ($val['flame_cd'] ?? null) <= 11 ) { 

	echo "7分割 - パターン " . (($val['flame_cd'] ?? null));

} else if ( ($val['flame_cd'] ?? null) >= 12 && ($val['flame_cd'] ?? null) <= 42 ) {

	echo "6分割 - パターン " . (($val['flame_cd'] ?? null));

} else if ( ($val['flame_cd'] ?? null) >= 43 && ($val['flame_cd'] ?? null) <= 69 ) { 

	echo "5分割 - パターン " . (($val['flame_cd'] ?? null));

} else if ( ($val['flame_cd'] ?? null) >= 70 && ($val['flame_cd'] ?? null) <= 97 ) { 

	echo "4分割 - パターン " . (($val['flame_cd'] ?? null) );

} else if ( ($val['flame_cd'] ?? null) >= 98 && ($val['flame_cd'] ?? null) <= 112 ) { 

	echo "3分割 - パターン " . (($val['flame_cd'] ?? null));

} else if ( ($val['flame_cd'] ?? null) >= 113 && ($val['flame_cd'] ?? null) <= 116 ) { 

	echo "2分割 - パターン " . (($val['flame_cd'] ?? null));

} else {

	echo "分割なし";

}



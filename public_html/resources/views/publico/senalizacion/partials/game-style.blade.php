{{-- Estilos de roulette.php y slot.php (los del legacy) más el lienzo y el mensaje de los juegos HTML5 --}}
<style type="text/css"><!--


html {
	height: 100%;
}

body {
	margin: 0;
	padding: 0;
	height: 100%;
	text-align: center;
}

.wrapper {
	width: 100%;
	z-index: 1;
	position: absolute;

}

.backGround {
	width: 100%;
	height: 100%;
	position: fixed;

}

#result {
	display: none;
}

/*印刷物一枚の設定*/
.kobetsuPrintArea {
	font-size: 8px;
	text-align: center;
}

.couponTitle {
	font-size: 18px;
}

.couponComment {
	font-size: 14px;
}

.couponLimit {
	font-size: 14px;
}

/* HTML5 (en lugar del swf) */
.gameCanvas {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.gameMessage {
	position: absolute;
	left: 0;
	bottom: 8%;
	width: 100%;
	color: #EE1111;
	font-size: 8vh;
	font-weight: bold;
	text-shadow: 0 0 6px #FFFFFF;
}

--></style>

{{--
    スロット (legacy: digitalsignage/slot.php + slot_demo.swf) en HTML5/Canvas.

    Flujo del legacy (se conserva): index2.php con システム内蔵 2 → un toque abre slot.php → el swf (demo)
    juega y, pasados 14 s, <meta Refresh> vuelve a ./?shop_cd=&display_cd=. No emite ni imprime cupones
    (el swf es una demo: no llama a ninguna URL ni a JavaScript).

    Inventado (el swf no deja ver más): tres tambores con símbolos; un toque los hace girar y se paran
    uno tras otro; tres iguales = «おめでとう！», si no «ざんねん…».

    Contrato de datos: $requestShopCd, $requestDisplayCd (string, de la petición), $kanshiTime int
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
<base href="{{ url('digitalsignage') }}/" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<meta http-equiv="Refresh" content="14; URL=./?shop_cd={{ $requestShopCd }}&display_cd={{ $requestDisplayCd }}">
<title>--</title>

<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />


<script type="text/javascript" src="../system/js/jquery-1.3.2.min.js"></script>

@include('publico.senalizacion.partials.game-style')

</head>
<body>

<div id="wrapper">
</div>


<div id="result">
<input type="hidden" name="kanshi_time" id="kanshi_time" value="{{ $kanshiTime }}" />
</div>

<div class="backGround">

<canvas id="slot" class="gameCanvas"></canvas>
<div id="gameMessage" class="gameMessage">タッチしてスタート</div>

</div>

<script type="text/javascript">
(function () {
	var SYMBOLS = ["7", "BAR", "★", "♪", "◆"];
	var canvas = document.getElementById("slot");
	var message = document.getElementById("gameMessage");
	var ctx = canvas.getContext("2d");
	var reels = [0, 0, 0], stopped = [true, true, true], played = false;

	function resize() {
		canvas.width = window.innerWidth;
		canvas.height = window.innerHeight;
		draw();
	}

	function draw() {
		var w = canvas.width / 5, h = canvas.height / 3, top = canvas.height / 3;
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		ctx.font = "bold " + Math.floor(h / 2) + "px sans-serif";
		ctx.textAlign = "center";
		ctx.textBaseline = "middle";
		for (var i = 0; i < 3; i++) {
			var left = w * (i + 1);
			ctx.fillStyle = "#FFFFFF";
			ctx.fillRect(left + 5, top, w - 10, h);
			ctx.strokeStyle = "#333333";
			ctx.lineWidth = 4;
			ctx.strokeRect(left + 5, top, w - 10, h);
			ctx.fillStyle = "#E83828";
			ctx.fillText(SYMBOLS[Math.floor(reels[i]) % SYMBOLS.length], left + w / 2, top + h / 2);
		}
	}

	function play() {
		if (played) { return; }
		played = true;
		message.innerHTML = "";
		stopped = [false, false, false];
		var result = [0, 1, 2].map(function () { return Math.floor(Math.random() * SYMBOLS.length); });
		var begin = null;

		function frame(now) {
			if (begin === null) { begin = now; }
			var elapsed = now - begin;
			for (var i = 0; i < 3; i++) {
				if (!stopped[i]) {
					reels[i] += 0.5;
					// los tambores se paran a 1,5 s, 2,2 s y 2,9 s
					if (elapsed > 1500 + i * 700) { stopped[i] = true; reels[i] = result[i]; }
				}
			}
			draw();
			if (!stopped[2]) {
				window.requestAnimationFrame(frame);
				return;
			}
			message.innerHTML = (result[0] == result[1] && result[1] == result[2]) ? "おめでとう！" : "ざんねん…";
		}
		window.requestAnimationFrame(frame);
	}

	window.onresize = resize;
	document.body.onclick = play;
	resize();
})();
</script>

</body>
</html>

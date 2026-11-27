{{--
    ルーレット (legacy: digitalsignage/roulette.php + roulette.swf) en HTML5/Canvas.

    Flujo del legacy (se conserva): index2.php con システム内蔵 5 → un toque en la pantalla abre roulette.php →
    el swf lee ./load_data.xml (<title name="win|other" id param>クーポン名#クーポンCD</title>), gira y al
    terminar llama a reloadAction(couponNo), que vuelve a ./?shop_cd=&display_cd=&coupon_no=<CD>.
    index2.php no usa coupon_no: la ruleta NO emite ni imprime ningún cupón (igual que el legacy).

    Probabilidades (load_data.xml): cada "win" sale con su param % ; si ninguna toca, una "other" al azar.
    Inventado (el swf no deja ver más): un toque inicia el giro, 5 s de giro, 3 s mostrando el resultado
    («はずれ» si cae en una "other") antes de reloadAction.

    Contrato de datos: $requestShopCd, $requestDisplayCd (string, de la petición)
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
<title>--</title>

<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />


<script type="text/javascript" src="../system/js/jquery-1.3.2.min.js"></script>

<script type="text/javascript"><!--

function reloadAction(couponNo){
	location.href = "./?shop_cd={{ $requestShopCd }}&display_cd={{ $requestDisplayCd }}&coupon_no=" + couponNo + "";
}

--></script>

@include('publico.senalizacion.partials.game-style')

</head>
<body>

<div id="wrapper">
</div>

<div class="backGround">

<!--roulette action...-->
<canvas id="roulette" class="gameCanvas"></canvas>
<div id="gameMessage" class="gameMessage">タッチしてスタート</div>

</div>

<script type="text/javascript">
(function () {
	var SPIN_MS = 5000, RESULT_MS = 3000;
	var COLORS = ["#E83828", "#F39800", "#FFF100", "#8FC31F", "#009944", "#00A0E9", "#1D2088", "#E4007F"];
	var canvas = document.getElementById("roulette");
	var message = document.getElementById("gameMessage");
	var ctx = canvas.getContext("2d");
	var items = [];
	var angle = 0, spinning = false, finished = false;

	function resize() {
		canvas.width = window.innerWidth;
		canvas.height = window.innerHeight;
		draw();
	}

	function draw() {
		var cx = canvas.width / 2, cy = canvas.height / 2, r = Math.min(cx, cy) * 0.85;
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		if (items.length == 0) { return; }
		var step = Math.PI * 2 / items.length;
		ctx.font = Math.max(12, r / 14) + "px sans-serif";
		ctx.textAlign = "right";
		ctx.textBaseline = "middle";
		for (var i = 0; i < items.length; i++) {
			var start = angle + i * step;
			ctx.beginPath();
			ctx.moveTo(cx, cy);
			ctx.arc(cx, cy, r, start, start + step);
			ctx.closePath();
			ctx.fillStyle = COLORS[i % COLORS.length];
			ctx.fill();
			ctx.save();
			ctx.translate(cx, cy);
			ctx.rotate(start + step / 2);
			ctx.fillStyle = "#FFFFFF";
			ctx.fillText(items[i].name, r - 10, 0);
			ctx.restore();
		}
		// flecha fija arriba
		ctx.beginPath();
		ctx.moveTo(cx - 20, cy - r - 30);
		ctx.lineTo(cx + 20, cy - r - 30);
		ctx.lineTo(cx, cy - r + 10);
		ctx.closePath();
		ctx.fillStyle = "#333333";
		ctx.fill();
	}

	// Sorteo de load_data.xml: "win" con su param %, si no, una "other" al azar
	function pick() {
		var roll = Math.random() * 100, sum = 0, others = [];
		for (var i = 0; i < items.length; i++) {
			if (items[i].win) {
				sum += items[i].param;
				if (roll < sum) { return i; }
			} else {
				others.push(i);
			}
		}
		return others.length ? others[Math.floor(Math.random() * others.length)] : 0;
	}

	function spin() {
		if (spinning || finished || items.length == 0) { return; }
		spinning = true;
		message.innerHTML = "";
		var winner = pick();
		var step = Math.PI * 2 / items.length;
		// el centro del sector ganador termina bajo la flecha (-90°)
		var target = -Math.PI / 2 - (winner * step + step / 2);
		var from = angle;
		var tau = Math.PI * 2;
		var to = from - (((from - target) % tau) + tau) % tau - tau * 8;
		var begin = null;

		function frame(now) {
			if (begin === null) { begin = now; }
			var t = Math.min(1, (now - begin) / SPIN_MS);
			var eased = 1 - Math.pow(1 - t, 3);
			angle = from + (to - from) * eased;
			draw();
			if (t < 1) {
				window.requestAnimationFrame(frame);
				return;
			}
			spinning = false;
			finished = true;
			message.innerHTML = items[winner].win ? items[winner].name : "はずれ";
			setTimeout(function () { reloadAction(items[winner].couponNo); }, RESULT_MS);
		}
		window.requestAnimationFrame(frame);
	}

	var request = new XMLHttpRequest();
	request.open("GET", "load_data.xml?t=" + new Date().getTime(), true);
	request.onreadystatechange = function () {
		if (request.readyState != 4) { return; }
		var nodes = request.responseXML ? request.responseXML.getElementsByTagName("title") : [];
		for (var i = 0; i < nodes.length; i++) {
			var parts = (nodes[i].textContent || "").split("#");
			items.push({
				win: nodes[i].getAttribute("name") == "win",
				param: parseFloat(nodes[i].getAttribute("param")) || 0,
				name: parts[0],
				couponNo: parts[1] || ""
			});
		}
		resize();
	};
	request.send(null);

	window.onresize = resize;
	document.body.onclick = spin;
})();
</script>

</body>
</html>

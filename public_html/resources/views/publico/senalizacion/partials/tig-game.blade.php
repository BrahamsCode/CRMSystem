{{--
    鬼ごっこゲーム (システム内蔵, tb_dispaly_systems_cds = 3) en HTML5: sustituye a tig_game/tig_game.swf.
    Lo que se lee del swf: carga tig_game/img/chara.jpg, la mueve hacia puntos al azar de la pantalla,
    al tocarla llama a callJavascript("HIT!") y expone sendSwfCommand() para el botón de prueba.
    Velocidad y trayectoria son inventadas. El legacy definía sendSwfCommand()/callJavascript() solo con
    el sistema 2 (スロット), así que el botón fallaba; aquí se definen con el juego.
--}}
<style type="text/css">
#tig_game { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; }
#tig_game img { position: absolute; cursor: pointer; }
</style>

<script type="text/javascript"><!--

function sendSwfCommand(){
	var swf_cmd = window.tigGame.sendSwfCommand();
}

function callJavascript(val){
	alert(val);
}

--></script>

<input type="button" name="testButton" id="testButton" value="flash上のスクリプトを起動" onclick="sendSwfCommand()">
<div id="tig_game"><img src="tig_game/img/chara.jpg?time={{ time() }}" alt="" id="tigGameChara" /></div>

<script type="text/javascript">
(function () {
	var stage = document.getElementById("tig_game");
	var chara = document.getElementById("tigGameChara");
	var x = 0, y = 0, targetX = 0, targetY = 0;

	function newTarget() {
		targetX = Math.random() * Math.max(0, stage.clientWidth - chara.width);
		targetY = Math.random() * Math.max(0, stage.clientHeight - chara.height);
	}

	function moveAction() {
		x += (targetX - x) / 20;
		y += (targetY - y) / 20;
		if (Math.abs(targetX - x) < 2 && Math.abs(targetY - y) < 2) { newTarget(); }
		chara.style.left = x + "px";
		chara.style.top = y + "px";
		window.requestAnimationFrame(moveAction);
	}

	chara.onclick = function (event) {
		event.stopPropagation();
		callJavascript("HIT!");
		newTarget();
	};

	window.tigGame = { sendSwfCommand: function () { newTarget(); return "moveSprite"; } };
	chara.onload = function () { newTarget(); moveAction(); };
})();
</script>

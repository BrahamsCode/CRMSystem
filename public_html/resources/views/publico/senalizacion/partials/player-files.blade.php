{{--
    Reproductor de 放映ファイル en HTML5: sustituye a digitalsignage_<landscape|portrait>.swf.
    Como el swf: lee ../hp/uploads/c7/display/<programa>.xml?t=…, recorre los <p> en orden y muestra
    los huecos con pic_flg = 1 de cada material (../hp/uploads/c7/screen_saver/<cd>/<hueco><ext>?t=…),
    con digitalsignage_logo.jpg si no hay nada que mostrar. Fundido de entrada/salida (el swf usaba Tweener).

    Lo que no puede replicarse o se ha decidido (ver respuesta de S4):
      - SWFファイル (<cd>/20.swf): se salta (Flash).
      - FLVファイル (<cd>/10.flv): se intenta con <video>; los navegadores no reproducen FLV, así que se salta al fallar.
      - Duración: `time` del XML en segundos por imagen; vacío o 0 → 10 s (el valor del swf no se puede leer).
      - テロップ (telop_comment): el swf no la mostraba; aquí, banda inferior con desplazamiento si existe.
      - portrait: mismo encuadre (contain); el giro de la pantalla lo hace el monitor/sistema operativo.

    Contrato de datos: $programCd int, $setting 'landscape'|'portrait', $program (telop_comment)
--}}
<style type="text/css">
.signagePlayer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #FFFFFF; overflow: hidden; }
.signagePlayer img, .signagePlayer video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; opacity: 0; transition: opacity 1s; }
.signagePlayer .isVisible { opacity: 1; }
.signageTelop { position: absolute; left: 0; bottom: 0; width: 100%; padding: 0.4em 0; background: rgba(0, 0, 0, 0.6); color: #FFFFFF; font-size: 4vh; white-space: nowrap; overflow: hidden; z-index: 2; }
.signageTelop span { display: inline-block; padding-left: 100%; animation: signageTelop 20s linear infinite; }
@@keyframes signageTelop { from { transform: translateX(0); } to { transform: translateX(-100%); } }
</style>

<div id="signagePlayer" class="signagePlayer signagePlayer--{{ $setting }}"
	data-xml="../hp/uploads/c7/display/{{ $programCd }}.xml"
	data-base="../hp/uploads/c7/screen_saver/"
	data-logo="digitalsignage_logo.jpg"></div>

@if (trim((string) $program->telop_comment) !== '')
<div class="signageTelop"><span>{{ $program->telop_comment }}</span></div>
@endif

<script type="text/javascript">
(function () {
	var DEFAULT_SECONDS = 10;
	var stage = document.getElementById("signagePlayer");
	var playlist = [];
	var index = -1;
	var current = null;

	function bust(url) {
		return url + (url.indexOf("?") < 0 ? "?" : "&") + "t=" + new Date().getTime();
	}

	// <p cd time type pic_extension pic_flg> → elementos reproducibles
	function buildPlaylist(xml) {
		var items = [];
		var nodes = xml.getElementsByTagName("p");
		for (var i = 0; i < nodes.length; i++) {
			var p = nodes[i];
			var cd = p.getAttribute("cd");
			var seconds = parseInt(p.getAttribute("time"), 10);
			var duration = (seconds > 0 ? seconds : DEFAULT_SECONDS) * 1000;
			var type = p.getAttribute("type");
			var flags = (p.getAttribute("pic_flg") || "").split(",");
			var exts = (p.getAttribute("pic_extension") || "").split(",");
			var base = stage.getAttribute("data-base") + cd + "/";

			if (type == "画像ファイル") {
				for (var x = 0; x < flags.length; x++) {
					if (flags[x] == "1") {
						items.push({ kind: "image", src: base + x + (exts[x] || ".jpg"), duration: duration });
					}
				}
			} else if (type == "FLVファイル" && flags[0] == "1") {
				items.push({ kind: "video", src: base + "10" + (exts[0] || ".flv"), duration: duration });
			}
			// SWFファイル: Flash, no se reproduce
		}
		return items;
	}

	function show(element) {
		stage.appendChild(element);
		// fuerza el reflujo para que la transición de opacidad se aplique
		void element.offsetWidth;
		element.className = "isVisible";
		if (current) {
			var old = current;
			old.className = "";
			setTimeout(function () { if (old.parentNode) { old.parentNode.removeChild(old); } }, 1000);
		}
		current = element;
	}

	function showLogo() {
		var img = document.createElement("img");
		img.src = stage.getAttribute("data-logo");
		show(img);
	}

	function next() {
		if (playlist.length == 0) {
			showLogo();
			return;
		}
		index = (index + 1) % playlist.length;
		var item = playlist[index];
		var advanced = false;
		function advance() { if (!advanced) { advanced = true; next(); } }

		if (item.kind == "image") {
			var img = document.createElement("img");
			img.onload = function () { show(img); setTimeout(advance, item.duration); };
			img.onerror = function () { setTimeout(advance, 1000); };
			img.src = bust(item.src);
		} else {
			var video = document.createElement("video");
			video.muted = true;
			video.autoplay = true;
			video.onended = advance;
			video.onerror = function () { setTimeout(advance, 1000); };
			video.src = bust(item.src);
			show(video);
		}
	}

	var request = new XMLHttpRequest();
	request.open("GET", bust(stage.getAttribute("data-xml")), true);
	request.onreadystatechange = function () {
		if (request.readyState != 4) { return; }
		var xml = request.status == 200 ? request.responseXML : null;
		playlist = xml ? buildPlaylist(xml) : [];
		next();
	};
	request.send(null);
})();
</script>

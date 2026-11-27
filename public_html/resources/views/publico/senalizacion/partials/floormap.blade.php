{{--
    インフォメーション (システム内蔵, tb_dispaly_systems_cds = 1) en HTML5: sustituye a fla/floormap/floormap.swf.
    Lee los mismos datos que el swf: ../hp/uploads/c7/display/floormap.xml (tiendas por planta, detalle y 求人)
    y fla/floormap/category.xml (業種 y su color). Mantiene las funciones del legacy floormapPrintAction(cd, tipo)
    y floormapCrawl(), que cargan fla/floormap/ajax_print_crawl.php en #ajaxFloormapPrintCouponCrawlResult.

    Inventado (el mapa vectorial del swf no se puede extraer): en lugar del plano por planta, una lista con
    las tres búsquedas del swf (階から探す / 業種から探す / アイウエオ順で探す) y una ficha con お店の詳細,
    求人情報 y los botones クーポン印刷 / 印刷する.
--}}
<style type="text/css">
.floormap { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #FFFFFF; color: #333333; text-align: left; font-size: 2.4vh; overflow: hidden; }
.floormap h1 { margin: 0; padding: 0.6em 1em; background: #EE7700; color: #FFFFFF; font-size: 1.6em; }
.floormapTabs { display: flex; }
.floormapTabs a { flex: 1; padding: 0.8em; background: #EEEEEE; border-right: 1px solid #FFFFFF; color: #333333; text-align: center; text-decoration: none; }
.floormapTabs a.isActive { background: #FFCC66; }
.floormapList { height: 75%; overflow-y: auto; padding: 0.5em 1em; }
.floormapList h2 { margin: 0.8em 0 0.3em; font-size: 1.2em; border-bottom: 2px solid #EE7700; }
.floormapList a { display: block; margin: 0.2em 0; padding: 0.6em; border-left: 0.6em solid #CCCCCC; color: #333333; text-decoration: none; }
.floormapDetail { display: none; position: absolute; top: 5%; left: 5%; width: 90%; height: 90%; background: #FFFFFF; border: 3px solid #EE7700; box-sizing: border-box; overflow-y: auto; padding: 1em; z-index: 3; }
.floormapDetail pre { white-space: pre-wrap; font-family: inherit; }
.floormapDetail button { margin: 0.5em; padding: 0.8em 2em; font-size: 1em; }
</style>

<script type="text/javascript"><!--

function floormapPrintAction(val, type){

	floormapCrawl(val, type);
}


function floormapCrawl(val, type){



	$("#ajaxFloormapPrintCouponCrawlResult").load("fla/floormap/ajax_print_crawl.php",{
		cd:val,
		print_type:type
	});


}

--></script>

<div class="floormap" id="floormap">
	<h1>どのお店をお探しですか</h1>
	<div class="floormapTabs">
		<a href="#" data-mode="floor" class="isActive">階から探す</a>
		<a href="#" data-mode="category">業種から探す</a>
		<a href="#" data-mode="kana">アイウエオ順で探す</a>
	</div>
	<div class="floormapList" id="floormapList"></div>
	<div class="floormapDetail" id="floormapDetail"></div>
</div>

<script type="text/javascript">
(function () {
	var shops = [];
	var categories = {};
	var list = document.getElementById("floormapList");
	var detail = document.getElementById("floormapDetail");

	function load(url, done) {
		var request = new XMLHttpRequest();
		request.open("GET", url + "?t=" + new Date().getTime(), true);
		request.onreadystatechange = function () {
			if (request.readyState == 4) { done(request.status == 200 ? request.responseXML : null); }
		};
		request.send(null);
	}

	function text(node) { return node ? (node.textContent || "") : ""; }

	function escapeHtml(value) {
		var div = document.createElement("div");
		div.appendChild(document.createTextNode(value));
		return div.innerHTML;
	}

	function render(mode) {
		var groups = {}, order = [];
		var sorted = shops.slice();
		if (mode == "kana") {
			sorted.sort(function (a, b) { return a.kana < b.kana ? -1 : (a.kana > b.kana ? 1 : 0); });
		}
		for (var i = 0; i < sorted.length; i++) {
			var shop = sorted[i];
			var key = mode == "floor" ? shop.floor : (mode == "category" ? (categories[shop.cate] ? categories[shop.cate].name : "") : (shop.kana ? shop.kana.charAt(0) : ""));
			if (!groups[key]) { groups[key] = []; order.push(key); }
			groups[key].push(shop);
		}
		var html = "";
		for (var g = 0; g < order.length; g++) {
			html += "<h2>" + escapeHtml(order[g]) + "</h2>";
			for (var s = 0; s < groups[order[g]].length; s++) {
				var item = groups[order[g]][s];
				var color = categories[item.cate] ? categories[item.cate].color : "#CCCCCC";
				html += "<a href=\"#\" data-index=\"" + shops.indexOf(item) + "\" style=\"border-left-color:" + color + "\">" + escapeHtml(item.name) + "</a>";
			}
		}
		list.innerHTML = html;
	}

	function openDetail(shop) {
		var html = "<h2>" + escapeHtml(shop.name) + "</h2>";
		html += "<h3>お店の詳細</h3><pre>" + escapeHtml(shop.detail) + "</pre>";
		html += "<button type=\"button\" data-print=\"coupon\">クーポン印刷</button>";
		if (shop.recruitCd) {
			html += "<h3>求人情報</h3><pre>" + escapeHtml(shop.recruit) + "</pre>";
			html += "<button type=\"button\" data-print=\"recruit\">印刷する</button>";
		}
		html += "<button type=\"button\" data-close=\"1\">×</button>";
		detail.innerHTML = html;
		detail.style.display = "block";
		detail.onclick = function (event) {
			var target = event.target;
			if (target.getAttribute("data-print") == "coupon") { floormapPrintAction(shop.cd, "クーポン"); }
			if (target.getAttribute("data-print") == "recruit") { floormapPrintAction(shop.recruitCd, "求人情報"); }
			if (target.getAttribute("data-close")) { detail.style.display = "none"; }
		};
	}

	document.getElementById("floormap").onclick = function (event) {
		var target = event.target;
		if (target.getAttribute("data-mode")) {
			var tabs = target.parentNode.getElementsByTagName("a");
			for (var i = 0; i < tabs.length; i++) { tabs[i].className = ""; }
			target.className = "isActive";
			render(target.getAttribute("data-mode"));
			return false;
		}
		if (target.getAttribute("data-index") !== null) {
			openDetail(shops[parseInt(target.getAttribute("data-index"), 10)]);
			return false;
		}
	};

	load("fla/floormap/category.xml", function (xml) {
		var nodes = xml ? xml.getElementsByTagName("category") : [];
		for (var i = 0; i < nodes.length; i++) {
			categories[nodes[i].getAttribute("cd")] = { name: text(nodes[i]), color: "#" + String(nodes[i].getAttribute("color")).replace("0x", "") };
		}
		load("../hp/uploads/c7/display/floormap.xml", function (data) {
			var maps = data ? data.getElementsByTagName("map") : [];
			for (var m = 0; m < maps.length; m++) {
				var nodes = maps[m].getElementsByTagName("shop");
				for (var s = 0; s < nodes.length; s++) {
					var recruit = nodes[s].getElementsByTagName("recruit")[0];
					shops.push({
						floor: maps[m].getAttribute("floor"),
						cd: nodes[s].getAttribute("cd"),
						cate: nodes[s].getAttribute("cate"),
						name: nodes[s].getAttribute("name") || "",
						kana: nodes[s].getAttribute("kana") || "",
						detail: text(nodes[s].getElementsByTagName("detail")[0]),
						recruit: text(recruit),
						recruitCd: recruit ? recruit.getAttribute("cd") : null
					});
				}
			}
			render("floor");
		});
	});
})();
</script>

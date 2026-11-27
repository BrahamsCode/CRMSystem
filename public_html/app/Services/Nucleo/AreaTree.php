<?php

namespace App\Services\Nucleo;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Árbol de エリア con casillas de tiendas, mismo HTML/CSS/JS que `Area::createAreaCheckUi` del legacy
 * (`system/class/Area.php`). Lo usan 店舗一覧 y los buscadores de los módulos (clientes, cupones…).
 *
 * Uso en Blade: {!! app(\App\Services\Nucleo\AreaTree::class)->checkUi($shops, request('tb_shop_cds', [])) !!}
 * con $shops = filas de tb_shop (array u objeto con cd, area_cd, kaishamei, is_closed).
 */
class AreaTree
{
    private int $depthMax = 0;

    /**
     * Llamadas `d.add(cd, pid, 'nombre', 'url', …)` del árbol dTree de エリア管理 (areaTree / areaTree_noLink
     * de inc/function.php). $url = página base de los enlaces ('area.php'); null = sin enlace.
     */
    public function dtreeJs(?string $url, string $areaCd = '', int $pid = 0): HtmlString
    {
        $areas = DB::table('tb_area')->where('disp_flg', '<>', '9')->where('flg', '1')
            ->when($pid, fn ($q) => $q->where('pid', $pid), fn ($q) => $q->where(fn ($w) => $w->whereNull('pid')->orWhere('pid', '<', 1)))
            ->orderBy('row_lis')->get();

        $js = '';
        foreach ($areas as $area) {
            $name = json_encode((string) $area->name, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $js .= $url === null
                ? "d.add({$area->cd}, {$pid},{$name},'javascript: void(0);');"
                : "d.add({$area->cd}, {$pid},{$name},'{$url}?pid={$area->cd}&category_cd=&area_cd=" . e($areaCd) . "','javascript: void(0);');";
            if (DB::table('tb_area')->where('pid', $area->cd)->exists()) {
                $js .= $this->dtreeJs($url, $areaCd, (int) $area->cd);
            }
        }

        return new HtmlString($js);
    }

    /**
     * @param  iterable  $shops  tiendas a colgar del árbol
     * @param  array  $selected  cd de tiendas marcadas
     * @param  string  $inputName  nombre de la casilla (`{shop_cd}` se reemplaza si $coverflow)
     * @param  array  $attributes  atributos extra para las casillas
     */
    public function checkUi(iterable $shops, array $selected = [], string $inputName = 'tb_shop_cds[]', array $attributes = [], int $createNumber = 0, bool $coverflow = false): HtmlString
    {
        $this->depthMax = 0;
        $shops = collect($shops)->map(fn ($s) => (array) $s)->values();
        $selected = array_map('strval', $selected);

        return new HtmlString($this->level(0, 0, $selected, $shops, $inputName, $attributes, $createNumber, $coverflow));
    }

    private function level(int $pid, int $level, array $selected, Collection $shops, string $inputName, array $attributes, int $createNumber, bool $coverflow): string
    {
        $res = '';
        $level++;
        $this->depthMax = max($this->depthMax, $level);

        $areas = DB::table('tb_area')->where('disp_flg', '<>', '9')->where('flg', '1')
            ->when($pid, fn ($q) => $q->where('pid', $pid), fn ($q) => $q->where(fn ($w) => $w->whereNull('pid')->orWhere('pid', '<', 1)))
            ->orderBy('row_lis')->get();

        $attributeString = '';
        foreach ($attributes as $k => $v) {
            $attributeString .= ' ' . $k . '="' . e($v) . '"';
        }

        foreach ($areas as $area) {
            $addStr = $level > 1 ? '└ ' : '';
            $depth = str_repeat('&emsp;', $level - 1);

            $shopStr = '';
            foreach ($shops as $shop) {
                if ((string) ($shop['area_cd'] ?? '') !== (string) $area->cd) {
                    continue;
                }
                $id = 'area_check_btn_' . $createNumber . '_' . $shop['cd'];
                $name = $coverflow ? str_replace('{shop_cd}', (string) $shop['cd'], $inputName) : $inputName;
                $checked = in_array((string) $shop['cd'], $selected, true) ? ' checked' : '';
                $shopStr .= '<div class="areaShopLabel"><label for="' . $id . '">'
                    . $depth . '<input type="checkbox" id="' . $id . '" name="' . e($name) . '" value="' . e($shop['cd']) . '" ' . $checked . ' ' . $attributeString . ' />'
                    . e($shop['kaishamei']) . (! empty($shop['is_closed']) ? ' (閉店)' : '')
                    . '</label></div>';
            }

            $subStr = DB::table('tb_area')->where('pid', $area->cd)->exists()
                ? $this->level((int) $area->cd, $level, $selected, $shops, $inputName, $attributes, $createNumber, $coverflow)
                : '';

            $res .= '<div class="areaCheckWrap" data-area-code="' . $area->cd . '">' . "\n"
                . $depth . $addStr . "\n"
                . '<a class="areaBtn" data-area-code="' . $area->cd . '" style="color: #336699; cursor: pointer;">' . e($area->name) . '</a>' . "\n"
                . "<br>\n" . $shopStr . "\n" . $subStr . "\n"
                . '</div>' . "\n";
        }

        if ($pid === 0) {
            $res .= $this->shopsWithoutArea($selected, $shops, $inputName, $createNumber) . $this->assets();
        }

        return $res;
    }

    /** Tiendas sin área válida (area_cd 0/NULL, área borrada u oculta, o área inexistente) */
    private function shopsWithoutArea(array $selected, Collection $shops, string $inputName, int $createNumber): string
    {
        $fallArea = DB::table('tb_area')->where(fn ($q) => $q->where('disp_flg', '9')->orWhereRaw("coalesce(flg, '') <> '1'"))
            ->pluck('cd')->map(fn ($v) => (string) $v)->all();
        $fallShops = DB::table('tb_shop as sp')->leftJoin('tb_area as ar', 'ar.cd', '=', 'sp.area_cd')->whereNull('ar.cd')
            ->pluck('sp.cd')->map(fn ($v) => (string) $v)->all();

        $res = '';
        foreach ($shops as $shop) {
            $areaCd = $shop['area_cd'] ?? null;
            if (! ((string) $areaCd === '0' || $areaCd === null || in_array((string) $areaCd, $fallArea, true) || in_array((string) $shop['cd'], $fallShops, true))) {
                continue;
            }
            $id = 'area_check_btn_' . $createNumber . '_' . $shop['cd'];
            $checked = in_array((string) $shop['cd'], $selected, true) ? ' checked' : '';
            $res .= '<div class="areaNonShopLabel"><label for="' . $id . '">'
                . '<input type="checkbox" id="' . $id . '" name="' . e(str_replace('{shop_cd}', (string) $shop['cd'], $inputName)) . '" value="' . e($shop['cd']) . '" ' . $checked . ' />'
                . e($shop['kaishamei']) . '</label></div>';
        }

        return $res;
    }

    private function assets(): string
    {
        $depth = $this->depthMax * 15;

        return <<<HTML
<style type="text/css">
.areaCheckWrap{ margin-bottom: 2px; }
.areaCheckWrap .areaShopLabel { margin-left: {$depth}px; }
.areaCheckWrap .areaCheckWrap{
	margin-left: 0px;
	text-indent: -20px;
	padding-left: 5px;
}
.areaCheckWrap .areaShopLabel {
	margin-left: 40px !important;
	text-indent: -40px;
}
</style>
<script type="text/javascript">
var clicked = false;
$(function(){
	$(".areaCheckWrap").each(function(){
		var isChildCheck = false;
		$(this).find("input[name^=mypage_config]").each(function(){
			if ( $(this).prop('checked') ) {
				isChildCheck = true;
			}
		});
		if ( !isChildCheck ) {
			$(this).find(".areaShopLabel").hide();
			$(this).find(".areaCheckWrap").hide();
		}
	});

	var jq = ( typeof $('.areaBtn').off != 'function' && typeof $1112 != 'undefined' ) ? $1112 : $;
	jq('.areaBtn').off().on('click', function(){
		if (clicked) {
			if ( jq(this).closest("div").children("div").css('display') == 'block') {
				jq(this).closest("div").children("div").hide();
			} else {
				jq(this).closest("div").children("div").show();
			}
			clicked = false;
			return;
		}
		clicked = true;
		setTimeout(jq.proxy(function() {
			if (clicked) {
				var t = jq(this).parent().find('input[type="checkbox"]');
				var chk = false;
				t.each(function(){
					if ( !jq(this).prop('checked') ) chk = true;
				});
				if ( chk ) t.prop('checked', true);
				else t.prop('checked', false);
			}
			clicked = false;
		}, this), 300);
	});
});
</script>
HTML;
    }
}

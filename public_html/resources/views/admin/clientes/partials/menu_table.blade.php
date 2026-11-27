{{-- Legacy: system/inc/function.php → makeMenuTable() (form_type 'メニュー項目' de los campos personalizados).
     Variables:
       $menuItems  colección/array de tb_yoyaku_koumoku_master ya filtrada y ordenada como el legacy
                   (type_flg, rank de categoría, tb_shop_cd, rank); claves: cd, name, tb_shop_cd, category_name
       $selected   array cd => '1' de las menús marcadas (yoyaku_menu_cd)
       $view       bool: true = solo lectura (確認), sin checkbox
     Nota: el legacy cierra un </ul> que nunca abre (el <ul> está comentado); se conserva igual. --}}
@php $preCateName = ''; @endphp
@forelse ($menuItems as $i => $menu)
@if (($menu['category_name'] ?? '') != $preCateName)
<li class="echoKoumokuYoyakuKoMidashi">{{ ($menu['category_name'] ?? '') != '' ? $menu['category_name'] : 'カテゴリ無し' }}</li>
@endif
<li>
@if (! $view)
<input type="checkbox" name="yoyaku_menu_cd[{{ $menu['cd'] }}]" id="yoyaku_menu_cd_{{ $menu['cd'] }}" value="1"@if (isset($selected[$menu['cd']])) checked="checked"@endif />
@endif
<label for="yoyaku_menu_cd_{{ $menu['cd'] }}">{{ $menu['name'] }}</label>
</li>
@if ($loop->last)
</ul>
@endif
@php $preCateName = $menu['category_name'] ?? ''; @endphp
@empty
<p>メニューデータはありません</p>
@endforelse

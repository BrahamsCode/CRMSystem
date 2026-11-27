{{--
    Legacy: system/category9/yoyaku/ajax_yoyaku_menu.php (fragmento que yoyaku.php carga en #menu).
    Variables:
      $baseMenus    Collection  menús base (type_flg = '1') del empleado: cd, shoyou_time, name, naiyou, category
      $optionMenus  Collection  menús de opción
      $checked      array       cd marcados (menu_cds)
    name y naiyou se escapan (el legacy los imprimía tal cual).
--}}
@if (count($baseMenus) > 0)

	<table class="tableBox2 wordBreakAll">
		<tr>
			<th style="width:20px;"></th>
			<th style="width: 7em;">カテゴリ</th>
			<th>ベースメニュー</th>
			<th style="width:6em;">目安時間</th>
		</tr>
@foreach ($baseMenus as $i => $menu)
		<tr{!! $i % 2 == 1 ? ' class="tableBg"' : '' !!}>

			<td style="text-align:center;"><input class="base_menu_cd menuBtn" type="checkbox" name="menu[{{ $menu->cd }}]" value="1"{{ in_array((string) $menu->cd, $checked, true) ? ' checked' : '' }} /></td>

			<td>{{ (string) $menu->category !== '' ? $menu->category : 'カテゴリ設定無し' }}</td>

			<td><span style="font-weight:bold;">{{ $menu->name }}</span><div style="padding-left:10px; font-size:90%;">{!! nl2br(e($menu->naiyou)) !!}</div></td>
			<td><span style="font-weight:bold;">{{ $menu->shoyou_time }}</span>分</td>
		</tr>
@endforeach
	</table>
	<div class="right">※1つ以上のベースメニューを選択して下さい。</div>

	<br/>

@if (count($optionMenus) > 0)
	<table class="tableBox2 wordBreakAll">
		<tr>
			<th style="width:20px"></th>
			<th style="width: 7em;">カテゴリ</th>
			<th>オプションメニュー</th>
			<th style="width:6em">目安時間</th>
		</tr>
@foreach ($optionMenus as $i => $menu)
		<tr{!! $i % 2 == 1 ? ' class="tableBg"' : '' !!}>
			<td style="text-align:center;"><input class="menuBtn" type="checkbox" name="option_menu[{{ $menu->cd }}]" value="1"{{ in_array((string) $menu->cd, $checked, true) ? ' checked' : '' }} /></td>

			<td>{{ (string) $menu->category !== '' ? $menu->category : 'カテゴリ設定無し' }}</td>
			<td><span style="font-weight:bold;">{{ $menu->name }}</span><div style="padding-left:10px; font-size:90%;">{!! nl2br(e($menu->naiyou)) !!}</div></td>
			<td><span style="font-weight:bold;">{{ $menu->shoyou_time }}</span>分</td>
		</tr>
@endforeach
	</table>
@endif

	<div class="btnBox">
	<input type="hidden" name="act" id="act" value="reg" />
	<input type="submit" name="enter" id="enter" class="btn" value="確定" />

	</div>

@else
<p>メニューデータはありません</p>
@endif

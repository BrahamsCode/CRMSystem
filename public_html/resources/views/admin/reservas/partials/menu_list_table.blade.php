{{--
    Legacy: creatMenuFrom() de system/category9/yoyaku/ajax_yoyaku_koumoku_new.php (una tabla por categoría).
    Variables: $section (category: ?ReservationMenuCategory, menus: Collection<ReservationMenu>), $shopCd, $isMaster, $thumbnails (ver menu_list).
--}}
@php
    $rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
    $sectionCategory = $section['category'];
@endphp
<h3>@if (! $sectionCategory)カテゴリ設定無し@else{{ $sectionCategory->name }}@if ($sectionCategory->hyouji_flg === '9')<span style="color:red;font-weight:bold;font-size:90%;">※非表示</span>@endif @endif</h3><div class="form" style="margin-bottom: 30px;"><table class="tableBox8 wordBreakAll"><tr><th width="110">名前</th><th>内容</th><th width="50">目安</th><th width="50"></th><th width="50"></th><th width="30"></th></tr>
@foreach ($section['menus'] as $item)
@php
    $editParams = $isMaster ? ['cd' => $item->cd, 'shop' => $shopCd] : ['cd' => $item->cd];
    $isAllShopsItem = $item->tb_shop_cd === \App\Models\Reservations\ReservationMenu::ALL_SHOPS;
@endphp
<tr><td valign="top"><span style="font-weight: bold;">{!! $item->name !!}</span>@if ($isAllShopsItem)<br /><small>(全店舗表示メニュー)</small>@endif</td><td valign="top">@if (isset($thumbnails[$item->cd]))[画像] <br><img src="{{ $thumbnails[$item->cd] }}"><br><br>[内容]<br>@endif{!! nl2br($item->naiyou) !!}</td><td valign="top"><span style="font-weight: bold;">{{ $item->shoyou_time }}</span>分</td>@if ($isAllShopsItem && $shopCd !== -1)<td colspan="2" align="center">全店共通項目</td>@else<td class="textCenter"><input type="button" value="修正" onclick="location.href='{{ route('admin.reservas.menus.index', $editParams) }}';" /></td><td><input type="button" value="削除" onclick="submitChk2({{ $item->cd }}); return false;" /><form method="post" name="form_{{ $item->cd }}" id="form_{{ $item->cd }}" class="displayNone" action="{{ route('admin.reservas.menus.destroy', $item->cd) }}">@csrf @method('DELETE')<input type="hidden" name="act" class="btn" value="del"><input type="hidden" name="cd" class="btn" value="{{ $item->cd }}"><input type="hidden" name="rank" class="btn" value="{{ $item->rank }}"><input type="hidden" name="tb_shop_cd" class="btn" value="{{ $item->tb_shop_cd }}"></form></td>@endif<td>@if ($shopCd === -1 || ! $isAllShopsItem)<div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankup', '{{ $item->cd }}', '{{ $item->rank }}')">▲</div><div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankdown', '{{ $item->cd }}', '{{ $item->rank }}')">▼</div>@endif</td></tr>
@endforeach
</table><!--/.form--></div>

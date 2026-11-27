{{--
    Legacy: system/category9/yoyaku/ajax_yoyaku_koumoku_category.php (登録カテゴリ一覧, cargado por AJAX).
    Variables:
      $categories  Collection<ReservationMenuCategory>  categorías activas (orden rank, cd)
--}}
@php
    $rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
@endphp


<p class="h3Title">登録カテゴリ一覧</p>
@if ($categories->isNotEmpty())

<table class="tableBox4 wordBreakAll">

<tr>
<th>名称</th>
<th style="width:30px; text-align:center;">表示</th>
<th style="width:40px; text-align:center;">編集</th>
<th style="width:40px; text-align:center;">削除</th>
<th style="width:30px; text-align:center;">順番</th>
</tr>
@foreach ($categories as $item)
<tr>
<td>{{ $item->name }}</td>
<td align="center">
<input type="checkbox" name="hyouji{{ $item->cd }}" value="1" onchange="statusControl('hyouji', '{{ $item->cd }}', '{{ $item->hyouji_flg }}')"@if ($item->hyouji_flg == '1') checked="checked"@endif />
</td>
<td><input type="button" value="編集" onclick="rHref({{ $item->cd }});" /></td>
<td><input type="button" value="削除" onclick="statusControl('del', '{{ $item->cd }}', '{{ $item->rank }}')" /></td>
<td>
<div style="{{ $rankStyle }}" onclick="statusControl('rankup', '{{ $item->cd }}', '{{ $item->rank }}')">▲</div>
<div style="{{ $rankStyle }}" onclick="statusControl('rankdown', '{{ $item->cd }}', '{{ $item->rank }}')">▼</div>
</td>
</tr>
@endforeach
</table>
@else
データがありません。
@endif

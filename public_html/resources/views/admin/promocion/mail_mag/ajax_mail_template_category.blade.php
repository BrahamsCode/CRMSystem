{{-- Lista de categorías que carga mail_template_category por AJAX — legacy: system/category3/mail_mag/ajax_mail_template_category.php --}}
@php
	$rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
@endphp
<p class="h3Title">登録カテゴリ一覧</p>
@if ($categories->isNotEmpty())

<table class="tableBox4">

<tr>
<th>名称</th>
<th style="width:30px; text-align:center;">表示</th>
<th style="width:40px; text-align:center;">編集</th>
<th style="width:40px; text-align:center;">削除</th>
<th style="width:30px; text-align:center;">順番</th>
</tr>
@foreach ($categories as $category)
<tr>
<td>{{ $category->name }}</td>
<td align="center">
<input type="checkbox" name="hyouji{{ $category->cd }}" value="1" onchange="statusControl('hyouji', '{{ $category->cd }}', '{{ $category->hyouji_flg }}')"@checked($category->hyouji_flg === '1') />
</td>
<td><input type="button" value="編集" class="btnStyle" onclick="rHref({{ $category->cd }});" /></td>
<td><input type="button" value="削除" class="btnStyle" onclick="statusControl('del', '{{ $category->cd }}', '{{ $category->rank }}')" /></td>
<td>
<div style="{{ $rankStyle }}" onclick="statusControl('rankup', '{{ $category->cd }}', '{{ $category->rank }}')">▲</div>
<div style="{{ $rankStyle }}" onclick="statusControl('rankdown', '{{ $category->cd }}', '{{ $category->rank }}')">▼</div>
</td>
</tr>
@endforeach
</table>
@else
データがありません。
@endif

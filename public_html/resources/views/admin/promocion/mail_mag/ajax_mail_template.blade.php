{{-- Lista de plantillas que carga mail_template por AJAX — legacy: system/category3/mail_mag/ajax_mail_template.php --}}
@php
	$rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
	$cate = (string) ($sendCate ?? '');
@endphp
@if ($templates->isNotEmpty())

<table class="tableBox4">

<tr>
@if ($cate === '')
<th>カテゴリ</th>
@endif
<th>タイトル</th>
<th>タイプ</th>
<th>内容</th>
<th style="width:30px; text-align:center;">表示</th>
<th style="width:40px; text-align:center;">編集</th>
<th style="width:40px; text-align:center;">削除</th>
@if ($cate === '')
<th style="width:30px; text-align:center;">順番</th>
@endif
</tr>
@foreach ($templates as $template)
@php
	$entryUrl = route('admin.promocion.templates.entry', ['cd' => $template->cd]);
	$popupScript = ($isTablet ?? false)
		? "roid.popUp('" . $entryUrl . "');"
		: "openPopWindow('" . $entryUrl . "', '650', '');";
@endphp
<tr>
@if ($cate === '')
<td>{{ $template->category?->name }}</td>
@endif
<td>{{ $template->title }}</td>
<td>
<small>
@if ($template->mail_type === 'deco')
デコメール
@else
通常メルマガ
@endif
</small>
</td>
<td>
<div class="sampleDecoView">
{{-- デコメールは HTML をそのまま見せる (legacy と同じ) --}}
@if ($template->mail_type === 'deco')
{!! $template->naiyou !!}
@else
{!! nl2br(e($template->naiyou)) !!}
@endif
</div>
</td>
<td align="center">
<input type="checkbox" name="hyouji{{ $template->cd }}" value="1" onchange="statusControl('{{ $cate }}', 'hyouji', '{{ $template->cd }}', '{{ $template->hyouji_flg }}')"@checked($template->hyouji_flg === '1') />
</td>
<td><input type="button" value="編集" class="btnStyle" onclick="{{ $popupScript }}" /></td>
<td><input type="button" value="削除" class="btnStyle" onclick="statusControl('{{ $cate }}', 'del', '{{ $template->cd }}', '{{ $template->rank }}')" /></td>
@if ($cate === '')
<td>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $cate }}', 'rankup', '{{ $template->cd }}', '{{ $template->rank }}')">▲</div>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $cate }}', 'rankdown', '{{ $template->cd }}', '{{ $template->rank }}')">▼</div>
</td>
@endif
</tr>
@endforeach
</table>

<div>
※「全て」を選択している場合のみ、順番の変更が行えます。
</div>
@else
データがありません。
@endif

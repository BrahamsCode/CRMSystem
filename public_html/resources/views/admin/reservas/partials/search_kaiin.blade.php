{{--
    Legacy: system/category9/yoyaku/ajax_search_kaiin.php (respuesta HTML que search_kaiin.js / related_customer.php meten en #result).
    Variables:
      $total         int         socios que cumplen la búsqueda
      $members       Collection  hasta 200 filas (20 en iPad) de tb_customer (cd, id, kanri_id, name, name_kana)
      $btnFontColor  string      color del botón 確定 ('' en la UI v2, '#116699 !important' en la v1)
    El legacy imprimía los textos sin escapar; aquí se escapan (también dentro del onclick).
--}}
@if (count($members))
{{ $total }}件中/{{ count($members) }}件表示 
<table class="tableBox2" style="overflow:auto;table-layout:fixed;">
<tr style="line-height:15px;">
<th style="width:60px; line-height:15px;"><small>ID</small></th>
<th style="width:60px; line-height:15px;"><small>管理番号</small></th>
<th style="line-height:15px;"><small>名前</small></th>
<th style="width: 100px;">&nbsp;</th>
</tr>
@foreach ($members as $row => $member)
<tr style="line-height:15px; background-color:{{ $row % 2 == 0 ? '#FFFFFF' : '#EEEEEE' }}">
	<td style="overflow:hidden;">{{ $member->id }}</td>
	<td style="overflow:hidden;">{{ $member->kanri_id }}</td>
	<td style="overflow:hidden;">
	@if ($member->name_kana)<small>{{ $member->name_kana }}</small><br />@endif
	{{ $member->name }}
	</td>
	<td>
	<input type="button" class="btnStyle" value="確定" onclick="setMember({{ \Illuminate\Support\Js::from((string) $member->cd) }}, {{ \Illuminate\Support\Js::from(e('【' . $member->id . '】  ' . $member->name)) }});" style="color: {{ $btnFontColor }};" />
	<input type="button" class="btnStyle" onclick="javascript:window.open('/system/category1/inc/customer_detail.php?kaiin_cd={{ $member->cd }}&amp;wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" value="詳細"></td>
</tr>

@endforeach
</table>
@else
<p>データが見つかりませんでした</p>
@endif

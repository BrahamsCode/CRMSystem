{{-- Resultado del buscador de 例外顧客 de follow/auto — legacy: system/category3/inc/ajax_search_kaiin.php. Variables: $rows --}}
<table class="tableBox2" style="width:95%;overflow:auto;table-layout:fixed;">
@if ($rows->isNotEmpty())
<tr style="height:25px;">
<th width="20%">ID</th>
<th width="25%">管理番号</th>
<th width="35%">名前</th>
<th width="20%">生年月日</th>
</tr>
@foreach ($rows as $i => $row)
<tr style="height:25px;cursor:pointer;background-color:{{ $i % 2 === 0 ? '#FFFFFF' : '#EEEEEE' }}" onclick="fnc_setMember('{{ $row->cd }}');">
	<td style="line-height:15px;">{{ $row->id }}</td>
	<td style="line-height:15px;">{{ $row->kanri_id }}</td>
	<td style="line-height:15px;">{{ $row->name }}</td>
	<td style="line-height:15px;">{{ $row->kojin_birth }}</td>
</tr>
@endforeach
@else
<p>データが見つかりませんでした</p>
@endif
</table>

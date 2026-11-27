{{--
    Legacy: system/category1/karute_menu/ajax_karute_waribiki_list.php (lista que carga karute_waribiki.php por AJAX).
    Variables:
      $items     Collection<Discount>     割引 activas de la tienda (orden rank, cd)
      $shopCd    int                      tienda
      $isMaster  bool                     master: añade ?shop= al enlace 編集
--}}
@php
    $rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
    $typeDisplay = ['', '％', '円'];
    $buaiFlgDisplay = ['', '割引額を歩合に含む', '割引額を歩合に含まない'];
    $canUpdate = Gate::allows('ventas.update');
    $canDelete = Gate::allows('ventas.delete');
@endphp
@if ($items->isNotEmpty())

<table class="tableBox4">

<tr>
<th>名称</th>
<th>割引額</th>
<th>歩合適用</th>
@if ($canUpdate)<th style="width:40px; text-align:center;">編集</th>@endif
@if ($canDelete)<th style="width:40px; text-align:center;">削除</th>@endif
@if ($canUpdate)<th style="width:30px; text-align:center;">順番</th>@endif
</tr>
@foreach ($items as $row)
@php $editUrl = route('admin.ventas.discounts.index', $isMaster ? ['cd' => $row->cd, 'shop' => $shopCd] : ['cd' => $row->cd]); @endphp
<tr>
<td>{{ $row->name }}</td>
<td>{{ number_format((float) $row->value) }}{{ $typeDisplay[(int) $row->type] ?? '' }}</td>
<td>{{ $buaiFlgDisplay[(int) $row->buai_flg] ?? '' }}</td>
@if ($canUpdate)<td><input type="button" value="編集" onclick="location.href='{{ $editUrl }}';" /></td>@endif
@if ($canDelete)<td><input type="button" value="削除" onclick="submitChk2({{ $row->cd }}); return false;" /></td>@endif
@if ($canUpdate)<td>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankup', '{{ $row->cd }}', '{{ $row->rank }}')">▲</div>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankdown', '{{ $row->cd }}', '{{ $row->rank }}')">▼</div>
@endif
{{-- El legacy pone el formulario de borrado dentro de la celda 順番; aquí va siempre que se pueda borrar --}}
@if ($canDelete)
<form method="post" name="form_{{ $row->cd }}" id="form_{{ $row->cd }}" class="displayNone" action="{{ route('admin.ventas.discounts.destroy', $row->cd) }}">
@csrf
<input type="hidden" name="act" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
<input type="hidden" name="rank" class="btn" value="{{ $row->rank }}">
<input type="hidden" name="tb_shop_cd" class="btn" value="{{ $row->tb_shop_cd }}">
</form>
@endif
@if ($canUpdate)
</td>@endif
</tr>
@endforeach
</table>

@else
データがありません。
@endif

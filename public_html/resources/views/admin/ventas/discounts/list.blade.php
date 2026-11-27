{{--
    Tabla de 割引管理 cargada por AJAX (legacy: ajax_karute_waribiki_list.php).
    Datos: $shopCd (int), $items (Collection<Discount>), $isMaster (bool)
    Columnas 編集/順番 con ventas.update y 削除 con ventas.delete (kengen 1033 [3] y [4]).
    El formulario oculto de borrado va en la celda 削除 (el legacy lo ponía en 順番: sin 編集 no se podía borrar).
--}}
@if ($items->isNotEmpty())

<table class="tableBox4">

<tr>
<th>名称</th>
<th>割引額</th>
<th>歩合適用</th>
@can('ventas.update')<th style="width:40px; text-align:center;">編集</th>@endcan
@can('ventas.delete')<th style="width:40px; text-align:center;">削除</th>@endcan
@can('ventas.update')<th style="width:30px; text-align:center;">順番</th>@endcan
</tr>
@foreach ($items as $item)
<tr>
<td>{{ $item->name }}</td>
<td>{{ number_format((float) $item->value) }}{{ $item->typeLabel() }}</td>
<td>{{ $item->buaiLabel() }}</td>
@can('ventas.update')<td><input type="button" value="編集" onclick="location.href='{{ route('admin.ventas.discounts.index', array_filter(['cd' => $item->cd, 'shop' => $isMaster ? $shopCd : null])) }}';" /></td>@endcan
@can('ventas.delete')<td><input type="button" value="削除" onclick="submitChk2({{ $item->cd }}); return false;" />
<form method="post" name="form_{{ $item->cd }}" id="form_{{ $item->cd }}" class="displayNone" action="{{ route('admin.ventas.discounts.destroy') }}">
@csrf
<input type="hidden" name="act" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $item->cd }}">
<input type="hidden" name="rank" class="btn" value="{{ $item->rank }}">
<input type="hidden" name="tb_shop_cd" class="btn" value="{{ $item->tb_shop_cd }}">
</form>
</td>@endcan
@can('ventas.update')<td>
<div style="text-align: center;line-height: 1em;cursor: pointer;" onclick="statusControl('{{ $shopCd }}', 'rankup', '{{ $item->cd }}', '{{ $item->rank }}')">▲</div>
<div style="text-align: center;line-height: 1em;cursor: pointer;" onclick="statusControl('{{ $shopCd }}', 'rankdown', '{{ $item->cd }}', '{{ $item->rank }}')">▼</div>
</td>@endcan
</tr>
@endforeach
</table>

@else
データがありません。
@endif

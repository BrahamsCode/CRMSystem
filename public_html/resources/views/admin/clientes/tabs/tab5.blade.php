{{-- Legacy: system/category1/inc/detail_tab5.php (カルテ; sin pestaña en la barra del legacy, accesible con ?tab=5).
     Variables:
       $customer        modelo tb_customer
       $shopList        array cd => kaishamei
       $karuteSelected  objeto|null: tb_karute JOIN tb_sejutsu_cate de ?karute_cd=, o la última del cliente
                        (ORDER BY uketsuke_date DESC, cd DESC): cd, tb_shop_cd, uketsuke_date (epoch), sejutsu_name, comment
       $karuteList      LengthAwarePaginator (kensu por defecto 20) de tb_karute JOIN tb_sejutsu_cate del cliente
                        (ORDER BY uketsuke_date DESC, cd DESC): cd, tb_shop_cd, uketsuke_date, sejutsu_name --}}
@php
    $dateDisp = fn ($v) => is_numeric($v) && $v > 0
        ? \Illuminate\Support\Carbon::createFromTimestamp($v, 'Asia/Tokyo')->format('Y/m/d')
        : '';
    // Parámetros que el legacy añadía en $addstr
    $addParams = array_filter(['tab' => request('tab'), 'kaiin_cd' => $customer->cd, 'from' => request('from')], fn ($v) => $v !== null && $v !== '');
@endphp
{{-- ************************************************************ カルテ 開始 ************************************************************ --}}
<!--カルテ情報table start-->
<div class="tabBody">
@if ($karuteSelected)
<strong style="font-size: 120%;">カルテ番号：　{{ $karuteSelected->cd }}</strong>
<table class="tableBox">
	<tr>
		<th>店舗</th>
		<td>{{ $shopList[$karuteSelected->tb_shop_cd] ?? '' }}</td>
	</tr>

	<tr>
		<th>受付年月日</th>
		<td>{{ $dateDisp($karuteSelected->uketsuke_date) }}</td>
	</tr>

	<tr>
		<th>施術</th>
		<td>{{ $karuteSelected->sejutsu_name }}</td>
	</tr>

	<tr>
		<th>備考</th>
		<td>{!! nl2br(e($karuteSelected->comment)) !!}</td>
	</tr>
</table>
</div>
@endif

<div class="btnBox">

<form method="get" name="add_record_form" id="add_record_form" class="form" action="{{ route('admin.clientes.kartes.create', $customer->cd) }}">
<input type="submit" name="add_record" id="add_record" class="btn" value="カルテを追加する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
@if ($karuteSelected)
<form method="get" name="update_record_form" id="add_record_form" class="form" action="{{ route('admin.clientes.kartes.edit', [$customer->cd, $karuteSelected->cd]) }}">
<input type="submit" name="add_record" id="add_record" class="btn" value="カルテを修正する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="karute_cd" id="karute_cd" class="btn" value="{{ $karuteSelected->cd }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
<form method="post" name="delete_record_form" id="add_record_form" class="form" action="{{ route('admin.clientes.kartes.destroy', [$customer->cd, $karuteSelected->cd]) }}" onsubmit="submitChk(this); return false;">
@csrf
<input type="submit" name="add_record" id="add_record" class="btn" value="カルテを削除する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="karute_cd" id="karute_cd" class="btn" value="{{ $karuteSelected->cd }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
@endif

</div>

<!--カルテ情報table end-->
<p class="h2Title">■カルテ一覧</p>

@if ($karuteList->total() > 0)
<div class="form">
<p><span class="attention">{{ $karuteList->firstItem() }}件～{{ $karuteList->lastItem() }}件目/{{ $karuteList->total() }}件中</span></p>

<table class="tableBox2">
<tr>
<th>カルテ番号</th>
<th>店舗</th>
<th>受付年月日</th>
<th>施術</th>
<th></th>
</tr>
@foreach ($karuteList as $karute)
<tr @if ($karuteSelected && $karute->cd == $karuteSelected->cd) style="background-color: #FFEEAA"@endif>
<td>{{ $karute->cd }}</td>
<td>{{ $shopList[$karute->tb_shop_cd] ?? '' }}</td>
<td>{{ $dateDisp($karute->uketsuke_date) }}</td>
<td>{{ $karute->sejutsu_name }}</td>
<td>
@if ($karuteSelected && $karute->cd == $karuteSelected->cd)
<span style="padding: 2px; border: 1px solid #FF9955; color: #EE6622;">選択中</span>
@else
<a href="{{ route('admin.clientes.show', array_merge([$customer->cd, 'page' => request('page'), 'kensu' => $karuteList->perPage(), 'karute_cd' => $karute->cd], $addParams)) }}">詳細</a>
@endif
</td>
</tr>
@endforeach


</table>


<ul class="btnBox2">
@include('admin.clientes.partials.page-control', ['paginator' => $karuteList, 'params' => $addParams])
</ul>


@else
カルテはありません。
@endif
</div>


{{-- ************************************************************ カルテ 終了 ************************************************************ --}}

{{-- Legacy: system/category1/customer/customer_list.php (bloque カスタマイズ検索フォーム).
     Variables:
       $customSearchCategories  tb_search_cate de la categoría (disp_flg <> '9', serach_flg = '1', hyouji_flg = '1', orden rank),
                                cada una con 'cd', 'name' y 'forms' = tb_search_form (disp_flg <> '9', serach_flg = '1', orden rank)
       $filters, $hiddenSearchMenus, $cellTag, $yoyakuMenus, $isMaster  (ver partials/search_set1 y partials/search_form_field)
--}}
@php
    $cellTag = $cellTag ?? 'th';
    $hiddenSearchMenus = $hiddenSearchMenus ?? [];
@endphp
@foreach ($customSearchCategories as $searchCate)
@php
    $menuKey = 'custom' . $searchCate['cd'];
    $forms = $searchCate['forms'] ?? [];
    $formCount = count($forms);
@endphp
<fieldset>
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array($menuKey, $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />{{ $searchCate['name'] }}</legend>
<div class="searchDiv" menu="{{ $menuKey }}"@if (in_array($menuKey, $hiddenSearchMenus)) style="display:none;"@endif>
<table class="tableBox9">
@foreach ($forms as $searchForm)
@php $num = $loop->iteration; @endphp
@if ($num % 3 == 1)
	<tr>
@endif
	<{{ $cellTag }}>

		<small>{{ $searchForm['name'] }}</small><br />

@include('admin.clientes.partials.search_form_field', ['form' => $searchForm])

	</{{ $cellTag }}>
@if ($num % 3 == 0)
	</tr>
@elseif ($loop->last)
	<{{ $cellTag }} colspan="{{ 3 - ($formCount % 3) }}"></{{ $cellTag }}>
@endif
@endforeach

</table>
</div>
</fieldset>
@endforeach

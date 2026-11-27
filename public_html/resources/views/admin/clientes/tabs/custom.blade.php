{{-- Legacy: system/category1/inc/detail_tabcustom.php (pestaña de categoría personalizada tb_search_cate de la ficha,
     tab=custom + search_cate_cd; parcial sin @extends).
     Variables:
       $customer               (modelo tb_customer: cd)
       $searchCateData         (tb_search_cate seleccionada: cd, name, record_type (1 = varios registros), disp_column)
       $fukusuGroupCd          (string|null: category_inner_group mostrado: ?fukusu_group_cd o MAX del cliente; solo record_type 1)
       $kanrenCd               (int|null: tb_raiten_rireki.cd enlazado a ese registro (関連済); bloquea el borrado)
       $customFields           (campos de la categoría con el valor del registro, ver tabs/_custom-fields: name, form_type, unit, comment, html)
       $firstSearchForm        (primer tb_search_form de la categoría por rank, disp_flg IN ('1','2'): cd, name; solo record_type 1)
       $posKoubaiSearchFormCd  (string|null: admin_master.pos_koubai_tb_search_form_cd)
       $customRecords          (LengthAwarePaginator, 5 por página; solo record_type 1: registros de tb_customer_sub de la categoría
                                (disp_flg '1') con claves category_inner_group, touroku_date, update_date, raiten_cd (tb_raiten_rireki.cd
                                enlazado o null), text_data y text_data_cd (tb_customer_sub.cd/text_data del campo $firstSearchForm))
       $relatedFeaturesConfig  (array: explode('<>', admin_master.related_features), lo define la ficha)
     JS usados de la ficha: send_tab(), submitChk(). --}}
@php
    $isFukusu = (float) ($searchCateData['record_type'] ?? 0) == 1;
    // Fecha epoch del legacy → 'Y/m/d H:i:s' en hora de Japón (vacío si no hay fecha: no se muestra 1970)
    $formatEpoch = fn ($epoch) => ($epoch !== null && $epoch !== '') ? \Illuminate\Support\Carbon::createFromTimestamp((int) $epoch, 'Asia/Tokyo')->format('Y/m/d H:i:s') : '';
    // Parámetros GET de la ficha que el legacy añadía en $addstr
    $addParams = ['tab' => 'custom', 'kaiin_cd' => $customer->cd];
    if (($searchCateData['cd'] ?? '') != '') {
        $addParams['search_cate_cd'] = $searchCateData['cd'];
    }
@endphp
<!--カスタマイズtable start-->
<div class="tabBody">


<!--カスタマイズ情報table start-->
	@if ($isFukusu && ! $fukusuGroupCd)
	<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>{{ $searchCateData['name'] }}がありません。<br/><br/></p>
	<div class="innerBody">
	<div class="innerTabLeft">
	@else

@if ($isFukusu)
	<div class="btnBox">
	<input type="button" class="btnStyleM" value="{{ $searchCateData['name'] }}を追加する" onclick="document.add_record_form.submit();" />
	<input type="button" class="btnStyleM" value="{{ $searchCateData['name'] }}を修正する" onclick="document.update_record_form.submit();" />
	<input type="button" class="btnStyleM" value="{{ $searchCateData['name'] }}を削除する" onclick="submitChk(document.delete_record_form);" />
	</div>
	<div class="innerBody">
	<div class="innerTabLeft">
@endif
	<div class="tabBody2">

	@if ($isFukusu)
<p class="h2Title">{{ $searchCateData['name'] }}番号：　{{ $fukusuGroupCd }}@if ($kanrenCd)&emsp;&emsp;<span style="float: right; margin-top: -2px; padding: 0 2px; font-size: 100%;font-weight:bold; border: 1px solid #FF9955; color: #EE6622; background: #FFFFFF;">関連済</span>@endif</p>
	@endif

		<table class="tableBox">
@include('admin.clientes.tabs._custom-fields', ['fields' => $customFields, 'baseCnt' => $searchCateData['disp_column'], 'thStyle' => 'width: 150px'])
		</table>

	</div> <!-- tabBody2 -->

	@endif



@if ($isFukusu)
<!-- / div.innerTabLeft END --></div>

<div class="innerTabRight">

@if ($customRecords->count() > 0)
		<p class="h2Title">■{{ $searchCateData['name'] }}一覧</p>

		<div class="form">

		<p><span class="attention">{{ $customRecords->firstItem() }}件～{{ $customRecords->lastItem() }}件目/{{ $customRecords->total() }}件中</span></p>

		<table class="tableBox2">
		<tr>
		<th style="width:25px;">番号</th>
		<th>{{ $firstSearchForm['name'] ?? '' }}</th>
		<th style="width: 75px; text-align: right;">登録/更新日</th>
		<th style="width: 60px; text-align: center;"></th>
		</tr>
		@foreach ($customRecords as $customRecord)
		@php
			$trAdds = '';
			if ($customRecord['category_inner_group'] == $fukusuGroupCd) {
				$trAdds = ' style="background-color: #FFEEAA;"';
			} elseif ($loop->index % 2 != 0) {
				$trAdds = ' class="tableBg"';
			}
		@endphp
		{{-- $trAdds: atributo fijo generado aquí --}}
		<tr{!! $trAdds !!}>
		<td>{{ $customRecord['category_inner_group'] }}</td>
		<td>
		<div style="overflow-y: auto;max-height: 80px;">
		</div>{{ str_replace('<>', '　', (string) $customRecord['text_data']) }}
		</td>
		<td style="text-align: right;">
		{{ $formatEpoch($customRecord['touroku_date']) }}
		<span style="color: #777777;">{{ $formatEpoch($customRecord['update_date']) }}</span>
		</td>
		<td style="text-align: center;">
			@if ($customRecord['category_inner_group'] == $fukusuGroupCd)
			<span style="padding: 2px; border: 1px solid #FF9955; color: #EE6622;">選択中</span>
			@else
			<a class="btnStyle" href="{{ route('admin.clientes.show', array_merge([$customer->cd, 'page' => request('page'), 'kensu' => $customRecords->perPage(), 'fukusu_group_cd' => $customRecord['category_inner_group']], $addParams)) }}" style="display: inline-block;">詳細</a>
				@if (($customRecord['raiten_cd'] ?? '') != '')<span style="display: inline-block;padding: 2px; border: 1px solid #778899; color: #778899;">関連済</span>@endif
			@endif

			@if (($posKoubaiSearchFormCd ?? '') != '' && ($firstSearchForm['cd'] ?? null) == $posKoubaiSearchFormCd)
			<br/>
			<a class="btnStyle" href="{{ route('admin.clientes.show', [$customer->cd, 'tab' => '11', 'kaiin_cd' => $customer->cd, 'tb_customer_sub_cd' => $customRecord['text_data_cd']]) }}" style="display:block;margin-top: 10px;">購買履歴</a>
			@endif
		</td>
		</tr>
		@endforeach

		</table>

		</div>

		<ul class="btnBox2">
		@include('admin.clientes.partials.page-control', ['paginator' => $customRecords, 'params' => $addParams + ($fukusuGroupCd != '' ? ['fukusu_group_cd' => $fukusuGroupCd] : [])])
		</ul>
@endif

<!-- / div.innerTabRight END --></div>

	<div class="btnBox" style="clear: both;">

		<form method="post" name="add_record_form" id="add_record_form" class="form" action="{{ $pendingRoute('admin.clientes.custom-records.create') }}">
		@csrf
		<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $searchCateData['name'] }}を追加する" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="custom">
		<input type="hidden" name="act" id="act" value="new">
		<input type="hidden" name="search_cate_cd" id="search_cate_cd" class="btn" value="{{ $searchCateData['cd'] }}">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		@if ($fukusuGroupCd)
		<form method="post" name="update_record_form" id="add_record_form" class="form" action="{{ $pendingRoute('admin.clientes.custom-records.create') }}">
		@csrf
		<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $searchCateData['name'] }}を修正する" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="custom">
		<input type="hidden" name="act" id="act" value="up">
		<input type="hidden" name="fukusu_group_cd" id="fukusu_group_cd" value="{{ $fukusuGroupCd }}">
		<input type="hidden" name="search_cate_cd" id="search_cate_cd" class="btn" value="{{ $searchCateData['cd'] }}">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		<form method="post" name="delete_record_form" id="delete_record_form" class="form" action="{{ $pendingRoute('admin.clientes.custom-records.destroy') }}">
		@csrf
		<input type="button" name="add_record" id="add_record" class="btn btnStyleM" value="{{ $searchCateData['name'] }}を削除する" @if ($kanrenCd) disabled @else onclick="submitChk(document.delete_record_form);" @endif />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
		<input type="hidden" name="tab" id="tab" value="custom">
		<input type="hidden" name="fukusu_group_cd" id="fukusu_group_cd" value="{{ $fukusuGroupCd }}">
		<input type="hidden" name="search_cate_cd" id="search_cate_cd" class="btn" value="{{ $searchCateData['cd'] }}">
		<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
		</form>
		@endif

	<!-- / div.btnBox END --></div>



<!-- / div.innerBody END --></div>
@endif
<!-- / div.tabBody END --></div>
<!--カスタマイズtable end-->
@if (isset($relatedFeaturesConfig[2]) && $relatedFeaturesConfig[2] == $searchCateData['cd'])
	<a class="backBtnStyle" onclick="send_tab('9',''); return false;">　戻る　</a>
@endif

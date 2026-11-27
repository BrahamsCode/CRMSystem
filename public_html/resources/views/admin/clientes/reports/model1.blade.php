{{-- Legacy: system/category1/shukei/model1.php (+ model1_search.php): 【<campo>】集計, ventana sin cabecera ni menú.
     Variables: $form (CustomField: cd, name, titles), $graphType ('pie' | 'bar'), $meta (CustomerReportService::applyVisitFilters:
       field, title, titleB, rirekiTitle, addRirekiStr), $blocks (list: key, value, total, counts [opción => veces]),
       + las de partials/visit-search-form ($in, $shops, $isMaster, $staffShopCd, $staffShopName, $selfUrl, $years).
     メール: el legacy ponía el SQL en los ocultos mail_where*; aquí van `field=…&value=…[&option=…]` y el servidor arma el
     filtro (model1_select.php). Tras guardarlo, la ventana de origen pasa a 内容設定 de メール配信 (S3). --}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
@include('admin.clientes.reports.partials.head')
@endsection

@section('js')
<script type="text/javascript">

function sendMailConfirm(target) {

	var conf = confirm("起動元の管理画面をメール作成画面に切り替えます。\nよろしいですか？");

	if (conf) {

		var where = $("#mail_where"+target).val();
		var cond = $("#mail_cond"+target).val();

		document.mailForm.conditionText.value = cond;
		document.mailForm.otherCondition.value = where;

		var sendData = $("#mailForm").serialize();
		$.ajax({
			type: "POST",
			url: "{{ route('legacy.clientes.reports.model1-select') }}",
			data: sendData,
			success: function(resp){
				window.opener.location.href = "{{ route('admin.promocion.mail.create') }}?search_uniqid="+resp;
			}
		});

	} else {

		return false;

	}
}

</script>
@endsection

@php
	$alpha = range('A', 'Z');
	$cond = (string) ($in['cond'] ?? '');
@endphp

@section('content')
<div class="contents">

<div class="h2Title" style="font-size:120%;">【{{ $form->name }}】集計</div>

@include('admin.clientes.reports.partials.visit-search-form', ['formCd' => $form->cd])

<br/><br/>
※本集計は、カルテツールにより入力されたデータを元に算出しています。
<p class="h2Title">【{{ $form->name }}】{{ $meta['rirekiTitle'] . $meta['title'] . $meta['titleB'] }}集計</p>
@if (count($blocks) > 0)
@foreach ($blocks as $n => $block)
@php
	$resultNum = $n + 1;
	$mainToken = $meta['field'] !== '' ? http_build_query(['field' => $meta['field'], 'value' => $block['value']]) : '';
	$mailCond = '【' . $form->name . '】' . $meta['rirekiTitle'] . $meta['title'] . ($cond !== '' ? $block['key'] : '全て');
	$total = array_sum($block['counts']);
	$pie = [];
	$letters = [];
	$values = [];
@endphp
<table class="tableBox8">
<tr>
@if ($cond !== '')<th rowspan="2" style="width:150px;">{{ $block['key'] }}</th>@endif
<th>
@include('admin.clientes.reports.partials.type-links')
<input type="hidden" id="mail_where{{ $resultNum }}" value="{{ $mainToken }}" />
<input type="hidden" id="mail_cond{{ $resultNum }}" value="{{ $mailCond }}" />
<input type="button" value="{{ $mailCond }}の人にメールを送信する" onclick="sendMailConfirm('{{ $resultNum }}');" />
</th>
</tr>

<tr>
<td>
<div>
<div style="font-weight:bold;font-size:130%;">述べ人数：{{ $block['total'] }}人</div>
<ul style="display: inline-block; *display: inline; *zoom: 1; vertical-align: middle;min-width:120px;">
@foreach (array_keys($block['counts']) as $i => $option)
@php
	$count = $block['counts'][$option];
	$letter = $graphType === 'pie' ? '' : ($alpha[$i] ?? '') . '： ';
	$percent = $total > 0 ? $count * 100 / $total : 0;
	$subToken = ltrim($mainToken . '&' . http_build_query(['option' => (string) $option]), '&');
	if ($graphType === 'pie') {
		$pie[] = $option . ',' . $count;
	} else {
		$letters[] = $alpha[$i] ?? '';
		$values[] = $count;
	}
@endphp
<li style="font-size: 120%;"><input type="hidden" id="mail_where{{ $resultNum . $i }}" value="{{ $subToken }}" /><input type="hidden" id="mail_cond{{ $resultNum . $i }}" value="{{ $mailCond }}・{{ $option }}" />{{ $letter }}[@if ($count > 0)<a href="javascript:void(0);" onclick="sendMailConfirm('{{ $resultNum . $i }}');">{{ $option }}</a>@else{{ $option }}@endif]<span style="margin-left: 3px; font-weight: bold;">{{ $count }}</span>人({{ number_format($percent, 1) }}%)</li>
@endforeach
</ul>
@if ($graphType === 'pie')
<iframe src="/system/inc/graph_pie.php?w=500&amp;h=200&amp;v={{ rawurlencode(implode('|', $pie)) }}" frameborder="0" width="500" height="220" scrolling="no" style="display: inline-block; *display: inline; *zoom: 1; margin-left: 30px; vertical-align: middle; overflow:hidden"></iframe>
@else
<iframe src="/system/inc/graph_bar.php?b=FFFFFF&amp;gb=CCCCCC&amp;lc=000000&amp;gtype=cylinder&amp;l=false&amp;w=500&amp;h=200&amp;x={{ rawurlencode(',' . implode(',', $letters)) }}&amp;y={{ rawurlencode('回答数') }}&amp;bc=005DFF&amp;v={{ rawurlencode(',' . implode(',', $values)) }}" frameborder="0" width="550" height="220" scrolling="no" style="display: inline-block; *display: inline; *zoom: 1; margin-left: 30px; vertical-align: middle; overflow:hidden"></iframe>
@endif
</div>

</td>
</tr>
</table>
@endforeach
@include('admin.clientes.reports.partials.mail-form')
@else
該当データがありません。
@endif

<!-- / .contents --></div>
@endsection

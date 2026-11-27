{{--
    ペーパーサイネージ 集計 (legacy: card_signage/aggregate.php). head.php carga jQuery 1.11.2 / UI 1.11.4 en esta URL.

    Contrato de datos (PaperCardStats):
      $card     PaperCard
      $summary  ?array  count, man, woman, other, guest (null si no hay accesos: celdas vacías como el legacy)
      $hourly   array<string, list<int>>  24 valores por grupo: 男性, 女性, 性別未登録, 非会員
      $ranking  LengthAwarePaginator  clientes (cd, id, name, count) por accesos DESC
      $siteName string (núcleo)

    Rutas: GET admin.terminales.paper-cards.index, GET admin.terminales.paper-cards.csv (?cd=).
    La ficha del cliente abre /system/category1/inc/customer_detail.php (pantalla de S2, URL del legacy).
    Diferencia: la paginación del legacy perdía cd (aggregate.php sin cd terminaba en blanco); aquí lo conserva.
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('body_class', 'main_terminal_master')

@section('jquery', 'jquery-1.11.2.min.js')

@section('jquery_ui', 'jquery-ui-1.11.4.min.js')

@section('title', '  - ' . ($siteName ?? ''))

@section('js')
<script src="{{ asset('legacy/system/js/highcharts.js') }}"></script>

<script type="text/javascript">

$(function () {

	$( ".ac4" ).accordion({ active: 0 });

    $('#chart').highcharts({
        title: {
            text: 'アクセス',
            x: -20 //center
        },
        subtitle: {
            text: 'リンク転送集計',
            x: -20
        },
        xAxis: {
            categories: ['0時', '1時', '2時', '3時', '4時', '5時', '6時', '7時', '8時', '9時', '10時', '11時', '12時', '13時', '14時', '15時', '16時', '17時', '18時', '19時', '20時', '21時', '22時', '23時']
        },
        yAxis: {
            title: {
                text: 'アクセス数'
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        tooltip: {
            valueSuffix: '人'
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle',
            borderWidth: 0
        },
        series: [{
            name: '男性',
            color: '#006699',
            data: [{{ implode(',', $hourly['男性']) }}]
        }, {
            name: '女性',
            color: '#EE0000',
            data: [{{ implode(',', $hourly['女性']) }}]
        }, {
            name: '性別未登録',
            color: '#999999',
            data: [{{ implode(',', $hourly['性別未登録']) }}]
        }, {
            name: '非会員',
            color: '#95C95C',
            data: [{{ implode(',', $hourly['非会員']) }}]
        }, {
            name: '合計',
            color: '#332211',
            data: [{{ implode(',', array_map(fn ($h) => $hourly['男性'][$h] + $hourly['女性'][$h] + $hourly['性別未登録'][$h] + $hourly['非会員'][$h], range(0, 23))) }}]
        }]
    });


	$('.customerDetail').on('click', function(){
		window.open('/system/category1/inc/customer_detail.php?kaiin_cd='+$(this).attr('cd')+'&wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');
	})
});

</script>
@endsection

@section('content')
<div class="contents">

<div class="h2Title h2TitleLeft MB10 OFH">ペーパーサイネージ</div>
<div class="h3Title h2TitleLeft MB10 OFH">集計</div>

<div class="fLeft">
	<a class="btnStyle FS20" href="{{ route('admin.terminales.paper-cards.index') }}">一覧へ戻る</a>
</div>


<div style="margin-top: 100px;">


	<div id="chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

	<table class="tableBox TLFix">
		<tr>
			<th>合計</th>
			<th>男性</th>
			<th>女性</th>
			<th>性別未登録</th>
			<th>非会員</th>
		</tr>
		<tr>
			<td class="right">{{ $summary['count'] ?? '' }}</td>
			<td class="right">{{ $summary['man'] ?? '' }}</td>
			<td class="right">{{ $summary['woman'] ?? '' }}</td>
			<td class="right">{{ $summary['other'] ?? '' }}</td>
			<td class="right">{{ $summary['guest'] ?? '' }}</td>
		</tr>
	</table>
</div>






@if ($ranking->total() > 0)
<table class="tableBox MB10 MT10">
	<tr>
		<th class="WD60">ID</th>
		<th>名前</th>
		<th class="WD60 center">アクセス数</th>
		<th class="WD60"></th>
	</tr>
@foreach ($ranking as $customer)

	<tr>
		<td>{{ $customer->id }}</td>
		<td>{{ $customer->name }}</td>
		<td>{{ $customer->count }}</td>
		<td class="center">
			<input type="button" class="btnStyle customerDetail" cd="{{ $customer->cd }}" value="詳細" />
		</td>
	</tr>

@endforeach
</table>

<ul class="center">
@include('admin.terminales.paper-cards.partials.pagination', ['paginator' => $ranking, 'baseUrl' => route('admin.terminales.paper-cards.aggregate'), 'extraQuery' => ['cd' => $card->cd]])
</ul>

@else
<div class="form CB">
該当するデータがみつかりません。
</div>
@endif

{{-- カウントがあれば、CSV出力を出す --}}
@if (($summary['count'] ?? 0) > 0)
<div>
	<a class="btnStyle" href="{{ route('admin.terminales.paper-cards.csv', ['cd' => $card->cd]) }}">集計データCSV出力</a>
</div>
@endif


<!-- / .contents --></div>
@endsection

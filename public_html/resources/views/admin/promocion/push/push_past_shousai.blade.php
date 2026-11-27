{{--
	過去プッシュ配信詳細 — legacy: system/category3/push/push_past_shousai.php → category3/inc/push_shousai.php
	Variables: $push (fila con title, body (HTML ya escapado), shop_cd, post_type, post_point, point_expiration_date,
	  search_display, touroku_date, image), $dataType (push|mailmaga), $pushCd, $shopName, $query (query string),
	  PushReport::detail(): $unread, $time, $sexCounts, $sexRows, $jobs, $jobCounts, $jobRows, $ages; $activeAc
--}}
@php
	$report = \App\Services\Promotions\PushReport::class;
	$zone = \App\Services\Promotions\LegacyTime::ZONE;

	$pageTitle = '過去プッシュ配信詳細';
	$timeCounts = array_map('count', $time);
	$ageCounts = array_map('count', $ages);
	$calcResultFlg = count($unread) > 0 || array_sum($timeCounts) > 0;
	$ageResultFlg = array_sum($ageCounts) > 0;
	$keep = array_intersect_key($query, array_flip(['page', 'kensu', 'data_type']));
	$detailUrl = route('admin.promocion.push.show') . '?' . http_build_query(['pushcd' => $pushCd] + $keep);

	// Lista de la porción elegida del gráfico (calc_index vacío = 未読)
	$detailRows = [];
	$heading = null;
	if (array_key_exists('calc_index', $query)) {
		$key = (string) $query['calc_index'];
		$detailRows = $key === '' ? $unread : ($time[$key] ?? []);
		$heading = '時間別集計 - ' . ($report::TIME_BUCKETS[$key][0] ?? '未読');
	} elseif (array_key_exists('sex_index', $query)) {
		$detailRows = $sexRows[(string) $query['sex_index']] ?? [];
		$heading = '性別集計 - ' . ($report::SEXES[(string) $query['sex_index']] ?? '');
	} elseif (array_key_exists('job_index', $query)) {
		$detailRows = $jobRows[(string) $query['job_index']] ?? [];
		$heading = '職業集計 - ' . $query['job_index'];
	} elseif (array_key_exists('age_index', $query)) {
		$detailRows = $ages[(string) $query['age_index']] ?? [];
		$heading = '年代集計 - ' . ($report::AGES[(string) $query['age_index']] ?? '');
	}

	$dataset = function (array $labels, array $counts, callable $color) {
		$out = [];
		foreach ($labels as $key => $label) {
			[$c, $h] = $color($key);
			$out[] = ['value' => (int) ($counts[$key] ?? 0), 'color' => $c, 'lineColor' => $c, 'highlight' => $h, 'label' => (string) $label, 'index' => (string) $key];
		}
		return $out;
	};
	$calcData = $dataset(array_map(fn ($b) => $b[0], $report::TIME_BUCKETS), $timeCounts, fn ($k) => $report::TIME_COLORS[$k]);
	$calcData[] = ['value' => count($unread), 'color' => '#FF6666', 'lineColor' => '#FF6666', 'highlight' => '#FF9966', 'label' => '未読', 'index' => ''];
	$sexData = $dataset($report::SEXES, $sexCounts, fn ($k) => $report::SEX_COLORS[$k]);
	$jobData = $dataset($jobs === [] ? [] : array_combine($jobs, $jobs), $jobCounts,
		fn ($k) => $report::JOB_COLORS[array_search((string) $k, $jobs, true) % count($report::JOB_COLORS)]);
	$ageData = $dataset($report::AGES, $ageCounts, fn ($k) => $report::AGE_COLORS[$k]);
	$backUrl = ($dataType === 'push' ? route('admin.promocion.push.past') : route('admin.promocion.mail.past')) . '?' . http_build_query($query);
	$isPoint = (int) ($push['post_type'] ?? 0) === 2;
	$json = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '詳細 - ' . $siteName)

@section('css')
<style>
	.graphArea { overflow: hidden; padding-top: 30px; padding-bottom: 30px; width: 103%; }
	.graphLegend { padding: 10px; overflow: hidden; position: relative; }
	ul.pie-legend { list-style: none outside none; float: left; margin: 0 0 0 0; padding: 0; position: relative; left: 50%; }
	ul.pie-legend>li { float: left; margin-right: 5px; padding: 5px; position: relative; left: -50%; }
	ul.pie-legend>li span { margin-right: 5px; border-radius: 21px; height: 20px; width: 20px; display: inline-block; }
	.pieGrapArea { overflow: hidden; padding-top: 30px; padding-bottom: 30px; width: 103%; }
	.pieGrapAreaCell { width: 47%; float: left; padding-right: 3%; margin-bottom: 20px; }
	.pieGrapAreaCell .title { font-size: 21px; border-bottom: 1px solid #717779; padding-bottom: 6px; margin-bottom: 10px; text-align: left; font-weight: bold; color: #717779; }
	.pieGrapAreaCell .title small { font-size: 14px; margin-left: 10px; }
	@media (min-width: 1450px) { .pieGrapAreaCell { width: 30%; padding-right: 3%; margin-bottom: 20px; } }
	h2 { margin-bottom: 0px; padding: 10px 5px 7px; background-color: #607D8B; font-size: 120%; color: #FFFFFF; margin-top: 50px; }
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/Chart.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		window.scroll(0, 0);
	});

	var datasets_calc = {!! $json($calcData) !!};
	var datasets_sex = {!! $json($sexData) !!};
	var datasets_job = {!! $json($jobData) !!};
	var datasets_age = {!! $json($ageData) !!};
	var detailUrl = {!! $json($detailUrl) !!};

	// Leyenda con enlaces a la lista de clientes de cada porción (el legacy enlazaba la de ocupación con `jpb_index`)
	function legendTemplate(name, param) {
		return "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<" + name + ".length; i++){%><li><a href=\"" + detailUrl + "&" + param + "=<%=encodeURIComponent(" + name + "[i].index)%>\"><span style=\"background-color:<%=" + name + "[i].lineColor%>\">&nbsp;&nbsp;&nbsp;</span><%if(" + name + "[i].label){%><%=" + name + "[i].label%>(<%=" + name + "[i].value%>)<%}%></a></li><%}%></ul>";
	}
	var options_calc = { animateRotate: false, legendTemplate: legendTemplate('datasets_calc', 'calc_index') };
	var options_sex = { animateRotate: false, legendTemplate: legendTemplate('datasets_sex', 'sex_index') };
	var options_job = { animateRotate: false, legendTemplate: legendTemplate('datasets_job', 'job_index') };
	var options_age = { animateRotate: false, legendTemplate: legendTemplate('datasets_age', 'age_index') };

	$(function() {
		var pie = function (id, data, options) {
			if (!document.getElementById("pieArea_" + id)) return;
			var chart = new Chart(document.getElementById("pieArea_" + id).getContext("2d")).Pie(data, options);
			document.getElementById("pieLegend_" + id).innerHTML = chart.generateLegend();
		};
		pie("calc", datasets_calc, options_calc);
		pie("age", datasets_age, options_age);
		pie("sex", datasets_sex, options_sex);
		pie("job", datasets_job, options_job);
	});
</script>
@endsection

@section('content')
<div class="contents">
	<p class="h2Title">■{{ $pageTitle }}</p>

	<table class="tableBox">
		<tr>
			<th width="100">通知タイプ</th>
			<td>{{ $isPoint ? 'ポイントチケット配信' : '通常プッシュ配信' }}</td>
		</tr>
		@if ($isPoint)
		<tr>
			<th width="100">付与ポイント数</th>
			<td>{{ $push['post_point'] }}ポイント</td>
		</tr>
		<tr>
			<th width="100">ポイントの有効期限</th>
			<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $push['point_expiration_date'], $zone)->format('Y-m-d H:i:s') }}まで</td>
		</tr>
		@endif
		<tr>
			<th><label for="staff_name">プッシュタイトル</label></th>
			<td>{{ $push['title'] }}</td>
		</tr>
		<tr>
			<th><label for="staff_name">プッシュ内容<em class="attention">※</em></label></th>
			<td>{!! $push['body'] !!}</td>
		</tr>
		@if ($push['image'])
		<tr>
			<th><label for="staff_name">画像</label></th>
			<td>
				<img src="{{ $push['image']['url'] }}" border="0" align="left">
				画像サイズ：横<b>{{ $push['image']['size'][0] }}</b>px、縦<b>{{ $push['image']['size'][1] }}</b>px<br>
			</td>
		</tr>
		@endif
		<tr>
			<th><label for="staff_name">振分条件</label></th>
			<td>{{ $push['search_display'] ?? '' }}</td>
		</tr>
		<tr>
			<th width="100">プッシュ投稿店舗</th>
			<td>{{ $shopName }}</td>
		</tr>
		<tr>
			<th><label for="send_flg">配信時間</label></th>
			<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $push['touroku_date'], $zone)->format('Y年m月d日 H:i:s') }}</td>
		</tr>
	</table>

	@if ($calcResultFlg || $sexCounts || $jobCounts || $ageResultFlg)
		@if ($calcResultFlg || $sexCounts)
		<h2>■集計グラフ</h2>
		<div class="pieGrapArea">
			@if ($calcResultFlg)
			<div class="pieGrapAreaCell">
				<div class="text-center title">時間別集計</div>
				<canvas id="pieArea_calc" class="graphArea"></canvas>
				<div id="pieLegend_calc" class="graphLegend"></div>
			</div>
			@endif
			@if ($sexCounts)
			<div class="pieGrapAreaCell">
				<div class="text-center title">性別集計</div>
				<canvas id="pieArea_sex" class="graphArea"></canvas>
				<div id="pieLegend_sex" class="graphLegend"></div>
			</div>
			@endif
		</div>
		@endif

		@if ($jobCounts || $ageResultFlg)
		<div class="pieGrapArea">
			<div class="pieGrapAreaCell">
				<div class="text-center title">職業集計</div>
				<canvas id="pieArea_job" class="graphArea"></canvas>
				<div id="pieLegend_job" class="graphLegend"></div>
			</div>
			@if ($ageResultFlg)
			<div class="pieGrapAreaCell">
				<div class="text-center title">年代別集計</div>
				<canvas id="pieArea_age" class="graphArea"></canvas>
				<div id="pieLegend_age" class="graphLegend"></div>
			</div>
			@endif
		</div>
		@endif

		@if ($heading !== null)
		<h2>■集計グラフ - {{ $heading }}</h2>
		@endif

		@if (count($detailRows) > 0)
		<br>
		<table class="tableBox">
			<colgroup>
				<col width="100"><col width=""><col width="100"><col width="100"><col width="100"><col width="100"><col width="100"><col width="100">
			</colgroup>
			<tr>
				<th>ID</th>
				<th>名前</th>
				<th>電話番号</th>
				<th>登録店舗</th>
				<th>会員登録日</th>
				<th>年齢</th>
				<th>性別</th>
				<th>職業</th>
			</tr>
			@foreach ($detailRows as $r)
			<tr>
				<td>{{ $r->login_id }}</td>
				<td>{{ $r->name }}</td>
				<td>{{ $r->tel }}</td>
				<td>{{ $r->kaishamei }}</td>
				<td>{{ $r->touroku_ymd }}</td>
				<td>{{ $r->age }}</td>
				<td>{{ $r->kojin_sex }}</td>
				<td>{{ $r->job }}</td>
			</tr>
			@endforeach
		</table>
		<br>
		@endif
	@endif

	<p><a href="{{ $backUrl }}" class="backBtnStyle">一覧へ戻る</a><br /></p>

<!-- / .contents --></div>
@endsection

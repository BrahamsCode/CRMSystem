{{-- Legacy: system/category1/customer/point_history.php (ポイント利用一覧 / ポイント利用検索).
     Variables:
       $filters   array: filtros del buscador (PointHistoryService::FILTERS; search_type '1' por defecto)
       $rows      colección de filas de la página (login_id, customer_name, customer_tel, shop_name, touroku_date (epoch),
                  shori_mae_point, sounyu_point, shori_ato_point, point_reason, use_point)
       $total     int: filas en total
       $page      int: página actual
       $maxPage   int: nº de páginas
       $kensu     int: 表示件数 (20 por defecto)
       $from      int: desplazamiento de la página
       $shops     colección tb_shop (cd, kaishamei) para 登録店舗
       $yearFrom, $yearTo  int: rango del selector de años (hace 2 años … el año que viene)
     Diferencias: kensuSet() no existía en el legacy (el selector de 表示件数 no hacía nada); el CSV y la paginación
     conservan todos los filtros (el legacy olvidaba 付与ポイント y 利用ポイント). --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', 'ポイント利用一覧 - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@php
    $searchType = (string) ($filters['search_type'] ?? '');
    $params = array_filter($filters, fn ($v) => $v !== '');
    $pageUrl = fn (int $p) => route('admin.clientes.point-history', array_merge(['page' => $p], $params, ['kensu' => $kensu]));
    // create_num_options(): valores con 2 cifras
    $numOptions = function (int $min, int $max, ?string $selected) {
        $html = '';
        for ($i = $min; $i <= $max; $i++) {
            $num = $i < 10 ? '0' . $i : (string) $i;
            $html .= '<option value="' . $num . '"' . ($selected !== null && $selected !== '' && $num == $selected ? ' selected' : '') . '>' . $num . "</option>\n";
        }

        return $html;
    };
    $fmtDate = fn ($epoch) => is_numeric($epoch) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $epoch, 'Asia/Tokyo')->format('Ymd') : '';
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	window.scroll(0,0);

	$("#checkAll").click(function(e) {
		var c = $(this).attr('checked');
		$(".delCheckbox").attr('checked', c);
	});

	$('#clearForm').bind('click', function(){
		$("#form_search").find("input[type=text]").val("");
		$("#form_search").find("select").val("");
		//return false;
	});

	var searchTypeToggle = function() {
		var search_type = $("input[name=search_type]:checked").val();
		$(".tr_point_1, .tr_point_2, .tr_point_3").hide();
		$(".tr_point_" + search_type).show();
	};

	searchTypeToggle();
	$("input[name=search_type]").click(function(){
		searchTypeToggle();
	});

});

//表示件数
function kensuSet(parts){
	location = {!! json_encode(route('admin.clientes.point-history', $params)) !!} + ({!! json_encode($params === [] ? '?' : '&') !!}) + 'kensu=' + parts.options[parts.selectedIndex].value;
}
</script>
@endsection

@section('content')
<div class="contents">

<div class="contents">

	<p class="h2Title">■ポイント利用検索</p>

	<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.clientes.point-history') }}">
		@csrf
		<table class="tableBox">
			<tr>
				<th><label for="texto">検索種別</label></th>
				<td>
					<label>
							<input type="radio" name="search_type" value="1" @checked($searchType == '1')>
						全顧客表示
					</label>
					<label>
							<input type="radio" name="search_type" value="2" @checked($searchType == '2')>
						ポイント加算日ごとに表示
					</label>
					<label>
							<input type="radio" name="search_type" value="3" @checked($searchType == '3')>
						ポイント利用日ごとに表示
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="texto">登録店舗</label></th>
				<td>
					<select name="shop_cd">
						<option value="">---</option>
						@foreach ($shops as $shop)
								<option value="{{ $shop->cd }}" @selected((string) $shop->cd === ($filters['shop_cd'] ?? ''))>{{ $shop->kaishamei }}</option>
						@endforeach
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="send_date">
					<span class="tr_point_1 tr_point_3">ポイント利用日</span>
					<span class="tr_point_2">ポイント付与日</span>
				</label></th>
				<td>
					{{-- $numOptions: <option> numéricos generados aquí --}}
					<select name="from_point_year" id="from_point_year">
					<option value="">&nbsp;</option>
					{!! $numOptions($yearFrom, $yearTo, $filters['from_point_year'] ?? '') !!}
					</select>年
					<select name="from_point_month" id="from_point_month">
					<option value="">&nbsp;</option>
					{!! $numOptions(1, 12, $filters['from_point_month'] ?? '') !!}
					</select>月
					<select name="from_point_day" id="from_point_day">
					<option value="">&nbsp;</option>
					{!! $numOptions(1, 31, $filters['from_point_day'] ?? '') !!}
					</select>日
					　～
					<select name="to_point_year" id="to_point_year">
					<option value="">&nbsp;</option>
					{!! $numOptions($yearFrom, $yearTo, $filters['to_point_year'] ?? '') !!}
					</select>年
					<select name="to_point_month" id="to_point_month">
					<option value="">&nbsp;</option>
					{!! $numOptions(1, 12, $filters['to_point_month'] ?? '') !!}
					</select>月
					<select name="to_point_day" id="to_point_day">
					<option value="">&nbsp;</option>
					{!! $numOptions(1, 31, $filters['to_point_day'] ?? '') !!}
					</select>日
				</td>
			</tr>
			<tr class="tr_point_1 tr_point_3">
				<th><label for="before_point">利用前ポイント</label></th>
				<td>
					<input type="text" name="before_point_from" id="before_point_from" class="writeArea" value="{{ $filters['before_point_from'] }}" style="width: 100px" /> ～
					<input type="text" name="before_point_to" id="before_point_to" class="writeArea" value="{{ $filters['before_point_to'] }}" style="width: 100px" />
					pt
				</td>
			</tr>
			<tr class="tr_point_2">
				<th><label for="before_point">付与ポイント数</label></th>
				<td>
					<input type="text" name="add_point_from" id="add_point_from" class="writeArea" value="{{ $filters['add_point_from'] }}" style="width: 100px" /> ～
					<input type="text" name="add_point_to" id="add_point_to" class="writeArea" value="{{ $filters['add_point_to'] }}" style="width: 100px" />
					pt
				</td>
			</tr>
			<tr>
				<th><label for="before_point">
					<span class="tr_point_1 tr_point_3">利用後ポイント</span>
					<span class="tr_point_2">合計ポイント数</span>
				</label></th>
				<td>
					<input type="text" name="after_point_from" id="after_point_from" class="writeArea" value="{{ $filters['after_point_from'] }}" style="width: 100px" /> ～
					<input type="text" name="after_point_to" id="after_point_to" class="writeArea" value="{{ $filters['after_point_to'] }}" style="width: 100px" />
					pt
				</td>
			</tr>
			<tr>
				<th><label for="texto">ポイント利用種別</label></th>
				<td><input type="text" name="texto" id="texto" class="writeArea" value="{{ $filters['texto'] }}" /></td>
			</tr>
			<tr>
				<th><label for="use_point">利用ポイント</label></th>
				<td>
					<input type="text" name="use_point_from" id="use_point_from" class="writeArea" value="{{ $filters['use_point_from'] }}" style="width: 100px" /> ～
					<input type="text" name="use_point_to" id="use_point_to" class="writeArea" value="{{ $filters['use_point_to'] }}" style="width: 100px" />
					pt
				</td>
			</tr>
		</table>

		<div class="btnBox">
			<button id="clearForm" class="btn btnStyleM">クリア</button>
			<input type="submit" name="act" class="btn btnStyleM" value="検索する">
		</div>
			<input type="hidden" name="kensu" value="{{ $kensu }}" />
	</form>

</div>

@if ($total == 0)

	<p>該当のデータはありません。</p>

@else

	<form action="{{ route('admin.clientes.point-history.csv', array_merge($params, ['kensu' => $kensu])) }}" method="POST" style="float: right;">
		@csrf
		<input type="hidden" name="csv_print" value="1">
		<input type="submit" value="CSV出力">
	</form>

	<div id="form">
		<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" {{ $kensu == $i * 10 ? 'selected' : '' }}>{{ $i * 10 }}</option>
@endfor
</select></p>
	</div>

	<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

	<table class="tableBox2">
		@if ($searchType == '2')
			<colgroup>
				<col width="110">
				<col width="50">
				<col width="">
				<col width="110">
				<col width="150">
				<col width="110">
				<col width="80">
				<col width="110">
				<col width="80">
			</colgroup>
			<tr>
				<th>ポイント付与日</th>
				<th>ID</th>
				<th>名前</th>
				<th>電話番号</th>
				<th>登録店舗</th>
				<th>付与ポイント</th>
				<th>合計ポイント</th>
				<th>ポイント利用種別</th>
				<th>利用ポイント数</th>
			</tr>
			@foreach ($rows as $row)
				<tr>
					<td>{{ $fmtDate($row->touroku_date) }}</td>
					<td>{{ $row->login_id }}</td>
					<td>{{ $row->customer_name }}</td>
					<td>{{ $row->customer_tel }}</td>
					<td>{{ $row->shop_name }}</td>
					<td>{{ number_format((float) $row->sounyu_point) }}</td>
					<td>{{ number_format((float) $row->shori_ato_point) }}</td>
					<td>{{ $row->point_reason }}</td>
					<td>{{ number_format((float) $row->use_point) }}</td>
				</tr>
			@endforeach
		@else
			<colgroup>
				<col width="50">
				<col width="">
				<col width="110">
				<col width="150">
				<col width="110">
				<col width="80">
				<col width="110">
				<col width="180">
				<col width="80">
			</colgroup>
			<tr>
				<th>ID</th>
				<th>名前</th>
				<th>電話番号</th>
				<th>登録店舗</th>
				<th>ポイント利用日</th>
				<th>利用前ポイント数</th>
				<th>利用後ポイント数</th>
				<th>ポイント利用種別</th>
				<th>利用ポイント数</th>
			</tr>
			@foreach ($rows as $row)
				<tr>
					<td>{{ $row->login_id }}</td>
					<td>{{ $row->customer_name }}</td>
					<td>{{ $row->customer_tel }}</td>
					<td>{{ $row->shop_name }}</td>
					<td>{{ $fmtDate($row->touroku_date) }}</td>
					<td>{{ number_format((float) $row->shori_mae_point) }}</td>
					<td>{{ number_format((float) $row->shori_ato_point) }}</td>
					<td>{{ $row->point_reason }}</td>
					<td>{{ number_format((float) $row->use_point) }}</td>
				</tr>
			@endforeach
		@endif
	</table>

	<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class=""><a href="{{ $pageUrl($page - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 2); $l <= min($maxPage, $page + 2); $l++)
@if ($l == $page)<li class="now"><b>{{ $l }}</b></li>@else<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>@endif
@endfor
@endif
@if ($page < $maxPage)
<li class=""><a href="{{ $pageUrl($page + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>@endif
	</ul>

@endif

<!-- / .contents --></div>
@endsection

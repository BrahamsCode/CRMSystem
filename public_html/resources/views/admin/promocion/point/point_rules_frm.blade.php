{{--
	ポイント発行ルール 新規登録 / 修正 — legacy: system/category3/point/point_rules_frm.php
	Variables: $mode, $action, $data, $errors (campo => mensaje), $cd, $shops, $singleShop, $isMaster,
	  $kingakuRanks, $raitenRanks (cd => nombre), $thisYear
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'ポイント発行ルール ' . $mode . ' - ' . $siteName)

@php
	use App\Http\Controllers\Admin\Promotions\PointRuleController as Rules;

	$d = fn (string $k) => (string) ($data[$k] ?? '');
	$opt = fn ($value, $label, bool $selected) => '<option value="' . e($value) . '"' . ($selected ? ' selected' : '') . '>' . e($label) . '</option>';
	$hourOptions = function (int $from, int $to, string $selected) use ($opt) {
		$html = '';
		for ($i = $from; $i <= $to; $i++) {
			$html .= $opt($i . '00', $i . ':00', $selected === $i . '00') . "\n";
		}

		return $html;
	};
	$clockOptions = function (string $selected) use ($opt) {
		$html = '';
		for ($i = 0; $i < 24; $i++) {
			$html .= $opt($i . ':00', $i . ':00', $selected === $i . ':00') . "\n";
		}

		return $html;
	};
	$ymdSelects = function (string $p) use ($d, $opt, $thisYear) {
		$y = $m = $dd = '';
		for ($i = $thisYear; $i <= $thisYear + 3; $i++) {
			$y .= $opt($i, $i, $d($p . '_year') === (string) $i);
		}
		for ($i = 1; $i <= 12; $i++) {
			$v = sprintf('%02d', $i);
			$m .= $opt($v, $v, $d($p . '_month') === $v);
		}
		for ($i = 1; $i <= 31; $i++) {
			$v = sprintf('%02d', $i);
			$dd .= $opt($v, $v, $d($p . '_day') === $v);
		}

		return '<select name="' . $p . '_year">' . $y . '</select>&nbsp;年&nbsp;<select name="' . $p . '_month">' . $m . '</select>&nbsp;月&nbsp;<select name="' . $p . '_day">' . $dd . '</select>&nbsp;日&nbsp;';
	};
	$type = $d('type_flg');
	$kingakuSet = $d('tb_kingaku_rank_master_cds') !== '' ? explode(',', $d('tb_kingaku_rank_master_cds')) : [];
	$raitenSet = $d('tb_raiten_rank_master_cds') !== '' ? explode(',', $d('tb_raiten_rank_master_cds')) : [];
	if (is_array($data['tb_kingaku_rank_master_cds'] ?? null)) { $kingakuSet = $data['tb_kingaku_rank_master_cds']; }
	if (is_array($data['tb_raiten_rank_master_cds'] ?? null)) { $raitenSet = $data['tb_raiten_rank_master_cds']; }
@endphp

@section('js')
<script type="text/javascript">
this_year = {{ $thisYear }};
dow = ["日", "月", "火", "水", "木", "金", "土"];

$(document).ready(function() {
	$(".ac13").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#type_flg").change(function() {
		var year_option = '', month_option = '', day_option = '', start_time_option = '', end_time_option = '', disp_i = '', in_html = '';
		var val = $("#type_flg").val();
		switch (val) {
			case "1":
				for (i=0; i<24; i++) {
					start_time_option += '<option value="'+i+'00">'+i+':00</option>\n';
					end_time_option += '<option value="'+(i+1)+'00">'+(i+1)+':00</option>\n';
				}
				in_html  = '<br/><br/>毎日：<select name="start_time">'+start_time_option+'</select>&nbsp;時&nbsp;から&nbsp\n';
				in_html += '<select name="end_time">'+end_time_option+'</select>&nbsp;時&nbsp;まで\n';
				$("#type_flg_span").html(in_html);
				break;
			case "2":
				var dow_option = '';
				for (i=0; i<dow.length; i++) dow_option += '<option value="'+i+'">'+dow[i]+'</option>\n';
				for (i=0; i<24; i++) {
					start_time_option += '<option value="'+i+'00">'+i+':00</option>\n';
					end_time_option += '<option value="'+(i+1)+'00">'+(i+1)+':00</option>\n';
				}
				in_html  = '<br/><br/>毎週：<select name="day_of_week">'+dow_option+'</select>&nbsp;曜日&emsp;\n';
				in_html += '<select name="start_time">'+start_time_option+'</select>&nbsp;時&nbsp;から&nbsp\n';
				in_html += '<select name="end_time">'+end_time_option+'</select>&nbsp;時&nbsp;まで\n';
				$("#type_flg_span").html(in_html);
				break;
			case "3":
				for (i=this_year; i<=(this_year + 3); i++) year_option += '<option value="'+i+'">'+i+'</option>\n';
				for (i=1; i<=12; i++) { disp_i = (i<10) ? '0'+i : i; month_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n'; }
				for (i=1; i<=31; i++) { disp_i = (i<10) ? '0'+i : i; day_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n'; }
				for (i=0; i<24; i++) {
					start_time_option += '<option value="'+i+':00">'+i+':00</option>\n';
					end_time_option += '<option value="'+i+':00">'+i+':00</option>\n';
				}
				in_html  = '<br/><br/><select name="start_year">'+year_option+'</select>&nbsp;年&nbsp;<select name="start_month">'+month_option+'</select>&nbsp;月&nbsp;<select name="start_day">'+day_option+'</select>&nbsp;日&nbsp;';
				in_html += '<select name="start_time">'+start_time_option+'</select>&nbsp;時&nbsp;から\n';
				in_html += '<select name="end_year">'+year_option+'</select>&nbsp;年&nbsp;<select name="end_month">'+month_option+'</select>&nbsp;月&nbsp;<select name="end_day">'+day_option+'</select>&nbsp;日&nbsp;';
				in_html += '<select name="end_time">'+end_time_option+'</select>&nbsp;時&nbsp;まで\n';
				$("#type_flg_span").html(in_html);
				break;
			case "4":
				for (i=1; i<=32; i++) { disp_i = (i==32) ? "末" : i; day_option += '<option value="'+i+'">'+disp_i+'</option>\n'; }
				in_html  = '<br/><br/>毎月：<select name="start_time">'+day_option+'</select>&nbsp;日';
				$("#type_flg_span").html(in_html);
				break;
			case "5":
				for (i=this_year; i<=(this_year + 3); i++) year_option += '<option value="'+i+'">'+i+'</option>\n';
				for (i=1; i<=12; i++) { disp_i = (i<10) ? '0'+i : i; month_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n'; }
				for (i=1; i<=31; i++) { disp_i = (i<10) ? '0'+i : i; day_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n'; }
				in_html  = '<br/><br/><select name="start_year">'+year_option+'</select>&nbsp;年&nbsp;<select name="start_month">'+month_option+'</select>&nbsp;月&nbsp;<select name="start_day">'+day_option+'</select>&nbsp;日&nbsp;終日';
				$("#type_flg_span").html(in_html);
				break;
			default:
				$("#type_flg_span").html('');
				break;
		}
	});
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■ポイント発行ルール {{ $mode }}</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $action }}">
@csrf
<fieldset>
<legend>ポイント発行ルール</legend>
<table class="tableBox">
	<tr>
		<th><label for="tb_shop_cd">店舗<em>※</em></label></th>
		<td @if (isset($errors['tb_shop_cd'])) class="Ng" @endif>
		@if ($singleShop)
		{{ $shops->first()?->kaishamei }}
		<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $shops->first()?->cd }}">
		@else
		<select name="tb_shop_cd" id="tb_shop_cd">
		<option value="">▼店舗選択</option>
		@if ($isMaster)
		<option value="-1" @selected($d('tb_shop_cd') === '-1')>全店舗</option>
		@endif
		@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}" @selected($d('tb_shop_cd') === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
		@endforeach
		</select>
		@endif
		@isset($errors['tb_shop_cd'])<p class="m0">{{ $errors['tb_shop_cd'] }}</p>@endisset
		</td>
	</tr>
	<tr>
		<th><label for="type_flg">ルール区分<em>※</em></label></th>
		<td @if (isset($errors['type_flg'])) class="Ng" @endif>
		<select name="type_flg" id="type_flg" clss="mR10">
		@foreach (Rules::TYPES as $i => $label)
		<option value="{{ $i }}" @selected((int) $type === $i)>{{ $label }}</option>
		@endforeach
		</select>
		<span id="type_flg_span">
		@switch($type)
			@case('1')
			<br/><br/>毎日：<select name="start_time">{!! $hourOptions(0, 23, $d('start_time')) !!}</select>&nbsp;時&nbsp;から
			<select name="end_time">{!! $hourOptions(1, 24, $d('end_time')) !!}</select>&nbsp;時&nbsp;まで
			@break
			@case('2')
			<br/><br/>毎週：<select name="day_of_week">
			@foreach (Rules::DOW as $i => $label)
			{!! $opt($i, $label, $d('day_of_week') !== '' && (int) $d('day_of_week') === $i) !!}
			@endforeach
			</select>&nbsp;曜日&emsp;
			<select name="start_time">{!! $hourOptions(0, 23, $d('start_time')) !!}</select>&nbsp;時&nbsp;から&nbsp;
			<select name="end_time">{!! $hourOptions(1, 24, $d('end_time')) !!}</select>&nbsp;時&nbsp;まで
			@break
			@case('3')
			<br/><br/>{!! $ymdSelects('start') !!}
			<select name="start_time">{!! $clockOptions($d('start_time')) !!}</select>&nbsp;時&nbsp;から
			{!! $ymdSelects('end') !!}
			<select name="end_time">{!! $clockOptions($d('end_time')) !!}</select>&nbsp;時&nbsp;まで
			@break
			@case('4')
			<br/><br/>毎月：<select name="start_time">
			@for ($i = 1; $i <= 32; $i++)
			{!! $opt($i, $i === 32 ? '末' : $i, $d('start_time') === (string) $i) !!}
			@endfor
			</select>&nbsp;日
			@break
			@case('5')
			<br/><br/>{!! $ymdSelects('start') !!}終日
			@break
		@endswitch
		@isset($errors['type_flg'])
			@if (str_starts_with($errors['type_flg'], '<br />'))
			{!! $errors['type_flg'] !!}
			@else
			<p class="m0">{{ $errors['type_flg'] }}</p>
			@endif
		@endisset
		</span>
		</td>
	</tr>
	@if ($kingakuRanks)
	<tr>
		<th>使用金額ランク</th>
		<td>
		@foreach ($kingakuRanks as $rankCd => $rankName)
			<input type="checkbox" name="tb_kingaku_rank_master_cds[]" id="kingaku_rank{{ $rankCd }}" value="{{ $rankCd }}" @checked(in_array((string) $rankCd, array_map('strval', $kingakuSet), true))/>
			<label for="kingaku_rank{{ $rankCd }}">{{ $rankName }}</label>&emsp;&emsp;
		@endforeach
		</td>
	</tr>
	@endif
	@if ($raitenRanks)
	<tr>
		<th>来店回数ランク</th>
		<td>
		@foreach ($raitenRanks as $rankCd => $rankName)
			<input type="checkbox" name="tb_raiten_rank_master_cds[]" id="raiten_rank{{ $rankCd }}" value="{{ $rankCd }}" @checked(in_array((string) $rankCd, array_map('strval', $raitenSet), true))/>
			<label for="raiten_rank{{ $rankCd }}">{{ $rankName }}</label>&emsp;&emsp;
		@endforeach
		</td>
	</tr>
	@endif
	<tr>
		<th><label for="point_count">発行ポイント<em>※</em></label></th>
		<td>
		<select name="point_count" id="point_count" class="mR10">
		@for ($i = 0; $i <= 10; $i++)
		{!! $opt($i, $i, $d('point_count') !== '' && (int) $d('point_count') === $i) !!}
		@endfor
		</select>倍
		</td>
	</tr>
</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $cd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn btnStyleM" value="この内容で登録する" />
</div>
</form>

@include('admin.promocion.point.inc.point_rules_help')

<!-- / .form --></div>
<!-- / .contents --></div>
@endsection

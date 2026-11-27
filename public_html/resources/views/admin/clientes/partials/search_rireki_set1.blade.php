{{-- Legacy: system/inc/search_rireki_set1.php (来店履歴検索条件テーブル).
     Variables:
       $raitenHistoryEnabled  bool: tb_base_system_child '来店履歴管理' activo (si no, no se muestra nada)
       $minYear               año del primer来店 (MIN(raiten_time) de tb_raiten_rireki, Asia/Tokyo); el controlador da el año actual si no hay datos válidos
       $shops                 tiendas tb_shop (disp_flg <> '9', orden cd): 'cd', 'kaishamei'
       $filters, $hiddenSearchMenus, $cellTag
--}}
@if ($raitenHistoryEnabled)
@php
    $cellTag = $cellTag ?? 'th';
    $hiddenSearchMenus = $hiddenSearchMenus ?? [];
    $filters = $filters ?? [];
    $v = fn (string $key) => old($key, $filters[$key] ?? null);
    $today = now('Asia/Tokyo');
    $minYear = (int) ($minYear ?? $today->year);
    $rirekiTypes = ['&nbsp;', '一週間', '一か月', '三か月', '半年', '一年', '期間指定', '全て'];
    $dow = ['日', '月', '火', '水', '木', '金', '土'];
@endphp
<script type="text/javascript">
var this_year = '{{ $today->format('Y') }}';
var this_month = '{{ $today->format('m') }}';
var this_day = '{{ $today->format('d') }}';
var min_year = '{{ $minYear }}';
$(document).ready(function() {
	$("#search_rireki_type_flg").change(function() {
		var year_option = start_month_option = start_day_option = end_month_option = end_day_option = start_time_option = end_time_option = disp_i = selected = "";
		val = $("#search_rireki_type_flg").val();
		switch (val) {
			case "6":
				for (i=this_year; i>=min_year; i--) {
					year_option += '<option value="'+i+'">'+i+'</option>\n';
				}
				for (i=1; i<=12; i++) {
					disp_i = (i<10) ? '0'+i : i;
					selected = (disp_i == this_month) ? ' selected' : ''
					start_month_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n';
					end_month_option += '<option value="'+disp_i+'"'+selected+'>'+disp_i+'</option>\n';
				}
				for (i=1; i<=31; i++) {
					disp_i = (i<10) ? '0'+i : i;
					selected = (disp_i == this_day) ? ' selected' : ''
					start_day_option += '<option value="'+disp_i+'">'+disp_i+'</option>\n';
					end_day_option += '<option value="'+disp_i+'"'+selected+'>'+disp_i+'</option>\n';
				}
				for (i=0; i<24; i++) {
					start_time_option += '<option value="'+i+':00">'+i+':00</option>\n';
					end_time_option += '<option value="'+i+':00">'+i+':00</option>\n';
				} // end for ($i)

				in_html  = '<select name="search_rireki_start_year">'+year_option+'</select>&nbsp;年&nbsp;';
				in_html += '<select name="search_rireki_start_month">'+start_month_option+'</select>&nbsp;月&nbsp;';
				in_html += '<select name="search_rireki_start_day">'+start_day_option+'</select>&nbsp;日&nbsp;';
				in_html += '<select name="search_rireki_start_time">'+start_time_option+'</select>&nbsp;時&nbsp;から<br />\n';
				in_html += '<select name="search_rireki_end_year">'+year_option+'</select>&nbsp;年&nbsp;';
				in_html += '<select name="search_rireki_end_month">'+end_month_option+'</select>&nbsp;月&nbsp;';
				in_html += '<select name="search_rireki_end_day">'+end_day_option+'</select>&nbsp;日&nbsp;';
				in_html += '<select name="search_rireki_end_time">'+end_time_option+'</select>&nbsp;時&nbsp;まで\n';

				$("#search_rireki_span").html(in_html);
				break;
			default:
				$("#search_rireki_span").html('');
				break;
		}
	});
});
</script>

<fieldset class="MT10">
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array('rireki', $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />来店履歴</legend>
<div class="searchDiv" menu="rireki"@if (in_array('rireki', $hiddenSearchMenus)) style="display:none;"@endif>
<p>
※来店履歴検索を行う場合は、「<span style="font-weight: bold; color: #113366;">期間の選択</span>」と「<span style="font-weight: bold; color: #113366;">来店回数</span>」の選択は<span style="font-weight: bold; color: #113366;">必須</span>です。<br />
※必須項目が入力されていない場合は、来店履歴検索条件は無視されます。<br />
※また、適正な抽出値を入力しなかった場合は、抽出条件が正しく動作いたしませんのでご注意ください。<br />
※予測来店日は最終来店日に来店周期の日数を足したものを予測来店日として検索します。
</p>
<table class="tableBox9">

	<tr>
		<{{ $cellTag }}>
			<span>期間の選択</span><br />過去&nbsp;
			<select name="search_rireki_type_flg" id="search_rireki_type_flg" clss="mR10" style="font-size: 120%;">
			@foreach ($rirekiTypes as $i => $rirekiType)
<option style="font-size: 120%;" value="{{ $i }}"@if ((int) $v('search_rireki_type_flg') === $i) selected @endif>{!! $rirekiType !!}</option>
			@endforeach
			</select>&nbsp;の期間中から抽出
			<br />
			<span id="search_rireki_span">
		@if ((string) $v('search_rireki_type_flg') === '6')
				<select name="search_rireki_start_year">@for ($y = $today->year; $y >= $minYear; $y--)<option value="{{ $y }}" @selected($v('search_rireki_start_year') == $y)>{{ $y }}</option>@endfor</select>&nbsp;年&nbsp;
				<select name="search_rireki_start_month">@for ($m = 1; $m <= 12; $m++)<option value="{{ sprintf('%02d', $m) }}" @selected($v('search_rireki_start_month') == sprintf('%02d', $m))>{{ sprintf('%02d', $m) }}</option>@endfor</select>&nbsp;月&nbsp;
				<select name="search_rireki_start_day">@for ($d = 1; $d <= 31; $d++)<option value="{{ sprintf('%02d', $d) }}" @selected($v('search_rireki_start_day') == sprintf('%02d', $d))>{{ sprintf('%02d', $d) }}</option>@endfor</select>&nbsp;日&nbsp;
				<select name="search_rireki_start_time">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}:00" @selected($v('search_rireki_start_time') == $h . ':00')>{{ $h }}:00</option>@endfor</select>&nbsp;時&nbsp;から<br />

				<select name="search_rireki_end_year">@for ($y = $today->year; $y >= $minYear; $y--)<option value="{{ $y }}" @selected($v('search_rireki_end_year') == $y)>{{ $y }}</option>@endfor</select>&nbsp;年&nbsp;
				<select name="search_rireki_end_month">@for ($m = 1; $m <= 12; $m++)<option value="{{ sprintf('%02d', $m) }}" @selected($v('search_rireki_end_month') == sprintf('%02d', $m))>{{ sprintf('%02d', $m) }}</option>@endfor</select>&nbsp;月&nbsp;
				<select name="search_rireki_end_day">@for ($d = 1; $d <= 31; $d++)<option value="{{ sprintf('%02d', $d) }}" @selected($v('search_rireki_end_day') == sprintf('%02d', $d))>{{ sprintf('%02d', $d) }}</option>@endfor</select>&nbsp;日&nbsp;
				<select name="search_rireki_end_time">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}:00" @selected($v('search_rireki_end_time') == $h . ':00')>{{ $h }}:00</option>@endfor</select>&nbsp;時&nbsp;まで
		@endif
			</span> <!--search_rireki_span-->
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			期間中の指定の日にち&nbsp;<br /><small>(例：過去三カ月の毎25日のみ)</small><br />
			<select name="search_rireki_day" id="search_rireki_day">
				<option value="">&nbsp;</option>
		@for ($d = 1; $d <= 31; $d++)
				<option value="{{ sprintf('%02d', $d) }}" @if ($v('search_rireki_day') != '' && (int) $v('search_rireki_day') === $d) selected @endif>{{ $d }}日</option>
		@endfor
			</select>
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			期間中の指定の曜日&nbsp;<br /><small>(例：過去三カ月の毎木曜日のみ)</small><br />
			<select name="search_rireki_dow" id="search_rireki_dow">
				<option value="">&nbsp;</option>
		@for ($i = 1; $i <= 7; $i++)
				<option value="{{ $i }}" @if ($v('search_rireki_dow') != '' && (int) $v('search_rireki_dow') === $i) selected @endif>{{ $dow[$i - 1] }}</option>
		@endfor
			</select>
		</{{ $cellTag }}>
	</tr>

	<tr>
		<{{ $cellTag }}>
			来店店舗<br />
			<select name="search_rireki_shop_cd" id="search_rireki_shop_cd">
			<option value="">&nbsp;</option>
			@foreach ($shops as $shop)
<option value="{{ $shop['cd'] }}"@if ($v('search_rireki_shop_cd') == $shop['cd']) selected @endif>{{ $shop['kaishamei'] }}</option>
			@endforeach
			</select>
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			来店回数<br />
			<input type="text" name="search_rireki_times" size="3" maxlength="3" value="{{ $v('search_rireki_times') }}" style="ime-mode:disabled;" />&nbsp;～&nbsp;
			<input type="text" name="search_rireki_times2" size="3" maxlength="3" value="{{ $v('search_rireki_times2') }}" style="ime-mode:disabled;" />&nbsp;回&emsp;
		</{{ $cellTag }}>

		<{{ $cellTag }}></{{ $cellTag }}>

	</tr>

</table>
<br/>
<table class="tableBox9">

	<tr>
		<{{ $cellTag }}>
			最終来店日から<br />
			<input type="text" name="search_rireki_daysFromLastVisit" size="3" maxlength="3" value="{{ $v('search_rireki_daysFromLastVisit') }}" style="ime-mode:disabled;" />&nbsp;日
		</{{ $cellTag }}>
		<{{ $cellTag }}>
			平均来店周期&nbsp;<br />
			<input type="text" name="search_rireki_averagePeriodTime1" size="3" maxlength="3" value="{{ $v('search_rireki_averagePeriodTime1') }}" style="ime-mode:disabled;" />&nbsp;～&nbsp;
			<input type="text" name="search_rireki_averagePeriodTime2" size="3" maxlength="3" value="{{ $v('search_rireki_averagePeriodTime2') }}" style="ime-mode:disabled;" />&nbsp;日
		</{{ $cellTag }}>
		<{{ $cellTag }}>
			<span>予測来店日</span><br />
			<small>（例：{{ $today->year }}/01/01～{{ $today->year }}/12/31）</small><br />
			<input type="text" size="5" name="search_raiten_yosoku_1" id="search_raiten_yosoku_1" class="inputM" value="{{ $v('search_raiten_yosoku_1') }}" />&nbsp;～&nbsp;
			<input type="text" size="5" name="search_raiten_yosoku_2" id="search_raiten_yosoku_2" class="inputM" value="{{ $v('search_raiten_yosoku_2') }}" />
		</{{ $cellTag }}>
	</tr>

</table>
</div>
</fieldset>
@endif

{{-- Legacy: system/category1/inc/detail_tab4.php (pestaña 来店履歴).
     Variables:
       $customer               modelo tb_customer (cd, shuki_heikin, last_raiten_date)
       $raitenList             LengthAwarePaginator (kensu por defecto 20, ?page=&kensu=) de tb_raiten_rireki JOIN tb_shop
                               (user_cd = cliente, disp_flg <> 9, ORDER BY raiten_time DESC): cd, raiten_time (epoch), kaishamei,
                               manual_flg, tb_search_cate_cd
       $shopList               array cd => kaishamei (opciones de 来店店舗 para master)
       $isMaster               bool: $_SESSION['rank'] == 'master'
       $sessionShopCd          string|int: tienda de la sesión (si no es master)
       $relatedFeaturesConfig  array (ver show.blade.php)
     El registro y el borrado los hacen raiten_submitChk()/raiten_delete() de show.blade.php por $.get a
     /system/category1/inc/raiten_register.php (URL legacy, se conserva). --}}
@php
    $now = \Illuminate\Support\Carbon::now('Asia/Tokyo');
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
@endphp
<!--来店履歴table start-->
<div class="tabBody">

	<fieldset style="margin:10px;padding:10px;border:1px dotted">

	<div class="tabBody2">
		<form method="post" name="form_raiten" id="form_raiten" class="form" action="">
			<p style="text-align:center;"><br/><br/>
			<select name="raiten_y" id="raiten_y">
			@for ($i = (int) $now->format('Y'); $i > (int) $now->format('Y') - 3; $i--)
			<option value="{{ $i }}">{{ $i }}</option>
			@endfor
			</select>年
			<select name="raiten_m" id="raiten_m">
			@for ($i = 1; $i <= 12; $i++)
			@php $dispI = sprintf('%02d', $i); @endphp
			<option value="{{ $dispI }}"@selected($now->format('m') == $dispI)>{{ $dispI }}</option>
			@endfor
			</select>月
			<select name="raiten_d" id="raiten_d">
			@for ($i = 1; $i <= 31; $i++)
			@php $dispI = sprintf('%02d', $i); @endphp
			<option value="{{ $dispI }}"@selected($now->format('d') == $dispI)>{{ $dispI }}</option>
			@endfor
			</select>日
			&emsp;
			<select name="raiten_h" id="raiten_h">
			@for ($i = 0; $i <= 23; $i++)
			@php $dispI = sprintf('%02d', $i); @endphp
			<option value="{{ $dispI }}"@selected($now->format('H') == $dispI)>{{ $dispI }}</option>
			@endfor
			</select>時
			<select name="raiten_i" id="raiten_i">
			{{-- el legacy llega hasta 60 (incluido); se conserva igual --}}
			@for ($i = 0; $i <= 60; $i++)
			@php $dispI = sprintf('%02d', $i); @endphp
			<option value="{{ $dispI }}"@selected($now->format('i') == $dispI)>{{ $dispI }}</option>
			@endfor
			</select>分
			&emsp;
			@if ($isMaster ?? false)
			<select name="raiten_shop_cd" id="raiten_shop_cd">
			@foreach ($shopList as $shopCd => $shopName)
			<option value="{{ $shopCd }}" >{{ $shopName }}</option>
			@endforeach
			</select>
			@else
			<input type="hidden" name="raiten_shop_cd" id="raiten_shop_cd" value="{{ $sessionShopCd ?? '' }}" />
			@endif
			{{-- 2012.11.01【大野】来店履歴一括登録機能を追加実装 --}}
			&nbsp;に&nbsp;
			<input type="text" onchange="replace_value(this)" onmouseover="replace_value(this)" onkeyup="replace_value(this)" onkeypress="return numbersonly(this, event)" style="ime-mode:disabled; width:3em;" maxlength="3" id="raiten_count" name="raiten_count" autocomplete="off" value="1" />回
			&nbsp;来店処理する&emsp;&emsp;
			<input type="button" name="raiten_action" id="raiten_action" class="btnStyle" value="来店履歴処理実行" onclick="raiten_submitChk();" />
			<input type="hidden" name="raiten_kaiin_cd" id="raiten_kaiin_cd" value="{{ $customer->cd }}" />
			<br/><br/></p>
		</form>
	</div>

	</fieldset>

	@if ($raitenList->total() > 0)
	<table class="tableBox" style="margin-bottom: 10px;">
	<tr>
	<th>来店周期</th>
	<td>
	<span style="font-size: 20px; font-weight: bold; color: #336699;">{{ $customer->shuki_heikin }}</span>日
	<small style="margin-left: 10px;">(最終来店日：</small>{{ $customer->last_raiten_date }}<small>)</small>
	</td>
	</tr>
	</table>


	<p class="h2Title">■来店履歴一覧</p>

	<div class="form">
		<table class="tableBox2">
			<tr>
				<th>日時</th>
				<th>来店店舗</th>
				<th>&nbsp;</th>
			</tr>
	@foreach ($raitenList as $raiten)
			@php $raitenTime = is_numeric($raiten->raiten_time) ? \Illuminate\Support\Carbon::createFromTimestamp($raiten->raiten_time, 'Asia/Tokyo') : null; @endphp
			<tr @if ($loop->even) class="tableBg"@endif>
				<td>@if ($raitenTime){{ $raitenTime->format('Y/m/d H:i') }}&nbsp;({{ $youbi[$raitenTime->dayOfWeek] }})&nbsp;@endif</td>
				<td>{{ $raiten->kaishamei }}</td>
				<td>
				@if ($raiten->manual_flg == '1')<a href="javascript:void();" onclick="raiten_delete('{{ $raiten->cd }}'); return false;">削除</a>@endif
				@if ($raiten->tb_search_cate_cd != '')<span style="padding: 2px; border: 1px solid #FF9955; color: #EE6622;">関連済</span>@endif
				</td>
			</tr>
	@endforeach
		</table>
	</div> <!--form-->

	<ul class="btnBox2">
	@include('admin.clientes.partials.page-control', ['paginator' => $raitenList, 'params' => ['tab' => request('tab'), 'kaiin_cd' => $customer->cd]])
	</ul>
	@else
	<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>来店履歴がありません。<br/><br/></p>
	@endif

</div>
<!--来店履歴table end-->
@if (($relatedFeaturesConfig[2] ?? '') != '' && ($relatedFeaturesConfig[0] ?? '') == '1')
	<a class="backBtnStyle" onclick="send_tab('9',''); return false;">　戻る　</a>
@endif

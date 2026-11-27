{{-- Legacy: system/inc/search_set1.php (検索条件テーブル: 顧客情報項目 + 位置情報).
     Variables:
       $searchItems        filas de tb_koumoku (tb_name = 'tb_customer', mail_mag_select = '1', orden rank, cd): claves 'tb_koumoku', 'tb_koumoku_name'
       $filters            array con los valores del buscador actual (request / sesión del buscador)
       $hiddenSearchMenus  array de menús plegados (cookie hideSearchMenu: 'kihon', 'gps', 'rireki', 'custom<cd>'…)
       $cellTag            'td' si la versión CRM es 2, si no 'th' (cKengen/setBaseSystem del legacy)
       $isMaster           bool: la sesión es rank master
       $shopCd, $shopName  tienda de la sesión (no master)
       $shops              tiendas tb_shop (disp_flg <> '9', orden cd): claves 'cd', 'kaishamei', 'name'
       $areaCheckUi        HTML del árbol de áreas/tiendas con checkbox (componente común equivalente a Area::createAreaCheckUi, name "<tb_koumoku>[]")
       $jobs               nombres de tb_job ordenados por rank
       $customerGroups     tb_customer_group (disp_flg = '1', orden rank): 'cd', 'name'
       $raitenDoukis       tb_raiten_douki_master (disp_flg = '1', orden rank): 'cd', 'name'
       $terminalNos        valores DISTINCT no vacíos de tb_customer.terminal_no
       $staffs             personal con clientes asignados: 'cd', 'name', 'shop_cd', 'shop_kaishamei' (orden shop_cd, cd; solo su tienda si no es master)
       $raitenRanks        [cd => name] de tb_raiten_rank_master (disp_flg = '1')
       $kingakuRanks       [cd => name] de tb_kingaku_rank_master (disp_flg = '1')
       $nameInputModeFlg   bool: contents_master name_input_mode_flg = '1' (姓＋名)
       $applicationUseFlg  bool: checkApplicationUseFlg() (muestra 位置情報)
--}}
@php
    $cellTag = $cellTag ?? 'th';
    $hiddenSearchMenus = $hiddenSearchMenus ?? [];
    $filters = $filters ?? [];
    // Valor actual de un campo del buscador
    $v = fn (string $key) => old($key, $filters[$key] ?? null);
    $tenpoSwitch = false;
    $itemCount = count($searchItems);
    $prefectures = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];
    $gyoushuOptions = ['農業', '林業', '漁業', '鉱業', '採石業', '砂利採取業', '建設業', '製造業', '電気・ガス・熱供給・水道業', '情報通信業', '運輸業', '郵便業', '卸売業', '小売業', '金融業', '保険業', '不動産業', '物品賃貸業', '学術研究', '専門・技術サービス業', '宿泊業', '飲食サービス業', '生活関連サービス業', '娯楽業', '教育', '学習支援業', '医療', '福祉', '他の営利事業', 'その他（政治・経済・文化・宗教団体など）'];
@endphp
<script type="text/javascript">
$(document).ready(function() {
	$('.searchLegend').click(function() {
		var imagen = $(this).children('img');

		var t = $(this).parent();
		t.children('.searchDiv').toggle();
		if (t.children('.searchDiv').is(':hidden')) {
			imagen.attr("src", "{{ asset('legacy/system/img/icon_plus.gif') }}");
		} else {
			imagen.attr("src", "{{ asset('legacy/system/img/icon_minus.gif') }}");
		}

		var hiddenElem = new Array();
		$('.searchDiv').each(function(){
			if ($(this).is(':hidden')){
				hiddenElem.push($(this).attr("menu"));
			}
		});

		// クッキーを保存
		setCookie('hideSearchMenu', hiddenElem.join(","), 30);
	});

	// 2012.03.13【大野】カレンダースクリプト設定対象修正
	// 指定の基本項目とclass名に'setDate'が入っているinput要素が対象
	$("input[name='houjin_setsuritsu'], input[name='daihyousha_birth'], input[name='touroku_date_1'], input[name='touroku_date_2'], input[name='update_date_1'], input[name='update_date_2'], input[name='revocation_point_date_1'], input[name='revocation_point_date_2'], .setDate").datepicker();

	// 2013.03.25【大野】予測来店日検索追加
	$("#search_raiten_yosoku_1, #search_raiten_yosoku_2").datepicker();

	// 2012.01.24【大野】テキスト＆セレクト複合入力設定
	var options1 = ['農業','林業','漁業','鉱業','採石業','砂利採取業','建設業','製造業','電気・ガス・熱供給・水道業','情報通信業','運輸業','郵便業','郵便業','卸売業','小売業','金融業','保険業','不動産業','物品賃貸業','学術研究','専門・技術サービス業','宿泊業','飲食サービス業',
	'生活関連サービス業','娯楽業','教育','学習支援業','医療','福祉','他の営利事業','その他（政治・経済・文化・宗教団体など）'];

	$(".searchDiv input[name='kinmusaki_gyoushu']").attr('id','kinmusaki_gyoushu').myFlexSelect({ options: options1, dropdownSize: 5 });

	var options2 = ['10','20','30','40','50','60','70','80'];
	$("#age_1, #age_2").attr('class','input2S').myFlexSelect({ options: options2, dropdownSize: 5 });

	var options3 = ['5','10','15','20','25','30','35','40','45','50'];
	$("#stamp_count_1, #stamp_count_2").myFlexSelect({ options: options3, dropdownSize: 5 });

	var options4 = ['1','2','3','4','5','6','7','8','9','10'];
	$("#error_send_cnt_1, #error_send_cnt_2").myFlexSelect({ options: options4, dropdownSize: 5 });

	var options5 = ['50','100','150','200','250','300','350','400','450','500'];
	$("#service_point_1, #service_point_2").myFlexSelect({ options: options5, dropdownSize: 5 });

});
</script>

<fieldset>
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array('kihon', $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />顧客情報項目</legend>
<div class="searchDiv" menu="kihon"@if (in_array('kihon', $hiddenSearchMenus)) style="display:none;"@endif>
<table class="tableBox9 MB10">
@foreach ($searchItems as $item)
@php
    $itemNo = $loop->iteration;
    $field = $item['tb_koumoku'];
    $label = $item['tb_koumoku_name'];
@endphp
@if ($itemNo % 4 == 1)
<tr>
@endif
<{{ $cellTag }}>
@switch($label)

	{{-- ■ 登録店舗 --}}
	@case('登録店舗')
		@php $tenpoSwitch = true; @endphp
		@if (count($shops))
			<small>{{ $label }}</small><br />
			@if ($isMaster)
				<div style="height: 200px; overflow-y: auto;">
					{{-- HTML generado por el componente común de áreas (Area::createAreaCheckUi en el legacy, impreso sin escapar) --}}
					{!! $areaCheckUi !!}
				</div>
			@else
			{{ $shopName }}
			<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $shopCd }}" />
			@endif
		@elseif (! $isMaster)
			<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $shopCd }}" />
		@endif
		@break

	{{-- ■ 都道府県 --}}
	@case('都道府県')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}" class="selectBox">
			@if ($v($field) != '')
			<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="">&nbsp;</option>
			@foreach ($prefectures as $pref)
			<option value="{{ $pref }}">{{ $pref }}</option>
			@endforeach
			</select>
		@break

	{{-- ■ 個人・法人 --}}
	@case('個人・法人')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
				<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="">&nbsp;</option>
			<option value="個人">個人</option>
			<option value="法人">法人</option>
			</select>
		@break

	{{-- ■ 性別 --}}
	@case('性別')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
				<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="">&nbsp;</option>
			<option value="男性">男性</option>
			<option value="女性">女性</option>
</select>
		@break

	{{-- ■ 生年月日 (月/日). El controlador separa el valor "Y/m/d" que llega por GET desde la ficha. --}}
	@case('生年月日')
			<small>{{ $label }}(月/日)</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
				<option value="{{ $v($field) }}">{{ str_replace('/', '', (string) $v($field)) }}月</option>
			@endif
			<option value="">&nbsp;</option>
			@for ($m = 1; $m <= 12; $m++)
			<option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}月</option>
			@endfor
			</select>

			<select size="1" name="{{ $field }}2">
			@if ($v($field . '2') != '')
			<option value="{{ $v($field . '2') }}">{{ str_replace('/', '', (string) $v($field . '2')) }}日</option>
			@endif
			<option value="">&nbsp;</option>
			@for ($d = 1; $d < 32; $d++)
				<option value="{{ sprintf('%02d', $d) }}">{{ sprintf('%02d', $d) }}日</option>
			@endfor
</select>
		@break

	{{-- ■ 血液型 --}}
	@case('血液型')
		<small>{{ $label }}</small><br />
<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
			<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="">&nbsp;</option>
			<option value="A">A</option>
			<option value="B">B</option>
			<option value="O">O</option>
			<option value="AB">AB</option>
			</select>
		@break

	{{-- ■ 職業 (make_job_options) --}}
	@case('職業')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
<option value="">▼職業選択</option>
@foreach ($jobs as $job)
<option value="{{ $job }}" @selected($v($field) == $job)>{{ $job }}</option>
@endforeach
			</select>
		@break

	{{-- ■ 会員登録日 --}}
	@case('会員登録日')
			<small>{{ $label }}</small><br />
			<input type="text" name="{{ $field }}_1" id="{{ $field }}_1" value="{{ $v($field . '_1') }}" class="inputS" /> ～ <input type="text" name="{{ $field }}_2" id="{{ $field }}_2" value="{{ $v($field . '_2') }}" class="inputS" />
<br />例)2009/01/01～2009/12/31
		@break

	{{-- ■ 情報変更日 --}}
	@case('情報変更日')
			<small>{{ $label }}</small><br />
			<input type="text" size="5" name="{{ $field }}_1" id="{{ $field }}_1" class="inputS" value="{{ $v($field . '_1') }}" /> ～ <input type="text" size="5" name="{{ $field }}_2" id="{{ $field }}_2" class="inputS" value="{{ $v($field . '_2') }}" />
<br />例)2009/01/01～2009/12/31
		@break

	{{-- ■ 登録区分 --}}
	@case('登録区分')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
			<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="登録済">登録済</option>
			<option value="退会済">退会済</option>
			<option value="削除済">削除済</option>
</select>
		@break

	{{-- ■ メルマガ区分 --}}
	@case('メルマガ区分')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}" class="selectBox">
			<option value="">&nbsp;</option>
			<option value="配信あり"@if ($v($field) == '配信あり') selected="selected"@endif>配信あり</option>
			<option value="配信なし"@if ($v($field) == '配信なし') selected="selected"@endif>配信なし</option>
			<option value="不達配信"@if ($v($field) == '不達配信') selected="selected"@endif>不達配信</option>
			</select>
		@break

	{{-- ■ 年齢 --}}
	@case('年齢')
			<small>{{ $label }}</small><br />
			<input type="text" size="5" name="{{ $field }}_1" id="{{ $field }}_1" class="input3S" value="{{ $v($field . '_1') }}" />歳 ～ <input type="text" size="5" name="{{ $field }}_2" id="{{ $field }}_2" class="input3S" value="{{ $v($field . '_2') }}" />歳
		@break

	{{-- ■ 業種 (make_houjin_gyoushu_options) --}}
	@case('業種')
		<small>{{ $label }}</small><br />
		<select name="{{ $field }}" id="{{ $field }}" class="selectBox" style="width: 100px;">
		<option value="">▼業種選択</option>
		@foreach ($gyoushuOptions as $gyoushu)
		<option value="{{ $gyoushu }}" @selected($v($field) == $gyoushu)>{{ $gyoushu }}</option>
		@endforeach
		</select>
		@break

	{{-- ■ 顧客グループ (make_customer_group_options) --}}
	@case('顧客グループ')
			<small>{{ $label }}</small><br />
			<select name="{{ $field }}" id="{{ $field }}" class="selectBox" style="width: 100px;">
			<option value="">&nbsp;</option>
			@foreach ($customerGroups as $group)
			<option value="{{ $group['cd'] }}" @selected((string) $v($field) === (string) $group['cd'])>{{ $group['name'] }}</option>
			@endforeach
			</select>
		@break

	{{-- ■ 初回来店動機 (make_raiten_douki_options) --}}
	@case('初回来店動機')
			<small>{{ $label }}</small><br />
			<select name="{{ $field }}" id="{{ $field }}" class="selectBox" style="width: 100px;">
			<option value="">&nbsp;</option>
			@foreach ($raitenDoukis as $douki)
			<option value="{{ $douki['cd'] }}" @selected($v($field) == $douki['cd'])>{{ $douki['name'] }}</option>
			@endforeach
			</select>
		@break

	{{-- ■ マイコレ使用 / 端末登録 --}}
	@case('マイコレ使用')
	@case('端末登録')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
			<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="どちらも">どちらも</option>
			<option value="有">有</option>
			<option value="無">無</option>
			</select>
		@break

	{{-- ■ 失効ポイント --}}
	@case('失効ポイント')
			<small>{{ $label }}</small><br />
			<input type="text" name="{{ $field }}_1" id="{{ $field }}_1" value="{{ $v($field . '_1') }}" class="inputS" /> ～ <input type="text" name="{{ $field }}_2" id="{{ $field }}_2" value="{{ $v($field . '_2') }}" class="inputS" />
<br />※検索の単位は1～
		@break

	{{-- ■ ポイント失効日 --}}
	@case('ポイント失効日')
			<small>{{ $label }}</small><br />
			<input type="text" size="5" name="{{ $field }}_1" id="{{ $field }}_1" class="inputS" value="{{ $v($field . '_1') }}" /> ～ <input type="text" size="5" name="{{ $field }}_2" id="{{ $field }}_2" class="inputS" value="{{ $v($field . '_2') }}" />
<br />例)2009/01/01～2009/12/31
		@break

	{{-- ■ 端末機ID --}}
	@case('端末機ID')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if ($v($field) != '')
			<option value="{{ $v($field) }}">{{ $v($field) }}</option>
			@endif
			<option value="">&nbsp;</option>
			@foreach ($terminalNos as $terminalNo)
<option value="{{ $terminalNo }}">{{ $terminalNo }}</option>
			@endforeach
			</select>
		@break

	{{-- ■ 指名担当者 (master: optgroup por tienda) --}}
	@case('指名担当者')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
			@if (count($staffs))
<option value="">▼担当者選択</option>
				@php $tempShopCd = ''; @endphp
				@foreach ($staffs as $staff)
					@if ($isMaster && $tempShopCd != $staff['shop_cd'])
						@if (! $loop->first)
</optgroup>
						@endif
<optgroup label="{{ $staff['shop_kaishamei'] }}">
						@php $tempShopCd = $staff['shop_cd']; @endphp
					@endif
<option value="{{ $staff['cd'] }}" @selected((string) $staff['cd'] === (string) $v($field))>{{ $staff['name'] }}</option>
				@endforeach
				@if ($isMaster)
</optgroup>
				@endif
			@else
<option value="">&nbsp;</option>
			@endif
			</select>
		@break

	{{-- ■ スタンプ数 / ポイント / 不達配信数 --}}
	@case('スタンプ数')
	@case('ポイント')
	@case('不達配信数')
			<small>{{ $label }}</small><br />
			<input type="text" name="{{ $field }}_1" id="{{ $field }}_1" value="{{ $v($field . '_1') }}" class="input2S" />以上 ～ <input type="text" name="{{ $field }}_2" id="{{ $field }}_2" value="{{ $v($field . '_2') }}" class="input2S" />以下
		@break

	{{-- ■ 来店ランク --}}
	@case('来店ランク（今回）')
	@case('来店ランク（前回）')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}">
<option value="">&nbsp;</option>
			@foreach ($raitenRanks as $rankCd => $rankName)
<option value="{{ $rankCd }}"@if ($v($field) != '' && $rankCd == $v($field)) selected @endif>{{ $rankName }}</option>
			@endforeach
			</select>
		@break

	{{-- ■ 金額ランク --}}
	@case('金額ランク（今回）')
	@case('金額ランク（前回）')
			<small>{{ $label }}</small><br />
			<select size="1" name="{{ $field }}" style="max-width:160px;">
<option value="">&nbsp;</option>
			@foreach ($kingakuRanks as $rankCd => $rankName)
<option value="{{ $rankCd }}"@if ($v($field) != '' && $rankCd == $v($field)) selected @endif>{{ $rankName }}</option>
			@endforeach
</select>
		@break

	{{-- ■ 名前 / 名前(カナ): 姓＋名モードなら注釈 --}}
	@case('名前')
	@case('名前(カナ)')
			<small>{{ $label }}</small><br />
			<input type="text" name="{{ $field }}" value="{{ $v($field) }}" class="inputM" />@if ($nameInputModeFlg)<br /><small>※フルネームで検索の場合は姓と名の間に【半角スペース】をいれてください</small>@endif
		@break

	{{-- ■ その他 --}}
	@default
			<small>{{ $label }}</small><br />
			<input type="text" name="{{ $field }}" value="{{ $v($field) }}" class="inputM" />
@endswitch
</{{ $cellTag }}>
@if ($itemNo % 4 == 0)
</tr>
@elseif ($loop->last)
<{{ $cellTag }} colspan="{{ 4 - ($itemCount % 4) }}"></{{ $cellTag }}></tr>
@endif
@endforeach

@if (! $tenpoSwitch)
	<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $isMaster ? '' : $shopCd }}" />
@endif

</table>
</div>
</fieldset>
<!--基本情報table end-->

{{-- 2016.07.19 位置に対する検索・配信 --}}
@if ($applicationUseFlg)
@php
    $distOptions = ['500m' => 500, '1Km' => 1000, '2Km' => 2000, '3Km' => 3000, '4Km' => 4000, '5Km' => 5000, '7.5Km' => 7500, '10Km' => 10000];
    $selectedShopCd = array_key_exists('distance_shop_cd', $filters) ? $v('distance_shop_cd') : $shopCd;
@endphp
<fieldset>
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array('gps', $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />位置情報</legend>
<div class="searchDiv" menu="gps"@if (in_array('gps', $hiddenSearchMenus)) style="display:none;"@endif>
<table class="tableBox9 MB10">
<tr>
	<td>
		<select id="interval_gps" name="interval_gps">
		@foreach (range(1, 24) as $intervalGps)
			<option value="{{ $intervalGps }}" @selected($v('interval_gps') == $intervalGps)>{{ $intervalGps }}</option>
		@endforeach
		</select>
		<span>時間以内に</span>

	@if ($isMaster)
		<select name="distance_shop_cd">
			<option value="">&nbsp;</option>
		@foreach ($shops as $shop)
				<option value="{{ $shop['cd'] }}" @selected($selectedShopCd == $shop['cd'])>{{ $shop['kaishamei'] }}</option>
		@endforeach
		</select>
		<span>から</span>
	@else
		{{ $shopName }}
		<input type="hidden" name="distance_shop_cd" id="distance_shop_cd" value="{{ $shopCd }}" />
	@endif

		<select name="distance">
			<option value="">&nbsp;</option>
		@foreach ($distOptions as $distLabel => $distValue)
			<option value="{{ $distValue }}" @selected($v('distance') == $distValue)>{{ $distLabel }}</option>
		@endforeach
		</select>&emsp;以内にいた人
	</td>
</tr>
</table>
</div>
</fieldset>
@endif

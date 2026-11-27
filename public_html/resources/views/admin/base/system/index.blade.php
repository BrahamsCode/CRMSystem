{{-- システム管理: system/category0/config/system.php (sin 初期化 ni エクスポート, ver Doc/diseno/nucleo-base.md) --}}
@extends('layouts.legacy')

@section('title', 'マスターデータ管理 -総合業務管理システム-')

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

$(function(){
	$('#base_system_version_btn').bind('click', function(e){
		var r = confirm('設定を行いますか？');
		if(r) $('form[name="base_system_version_form"]').submit();
		else return false;
	})
});
</script>
@endsection

@php
    $sel = fn (bool $on) => $on ? ' selected="selected"' : '';
    $chk = fn (bool $on) => $on ? ' checked="checked"' : '';
    $a = fn (string $key) => $admin[$key] ?? null;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■基本情報確認・変更</p>

<div style="text-align: center;">
@if ((string) $version !== '')
<div style="display:inline-block; width: 10em; border: 1px solid #999; text-align: center;">
ver <span style="font-size: 20px;font-weight: bold; color:#EE0000;">{{ $version }}</span>
</div>
@endif
@if ($cdType)
<div style="display:inline-block; width: 15em; border: 1px solid #999; text-align: center;">
int形式は @if ($cdType === 'bigint')<span style="font-size: 20px;font-weight: bold; color:#336699;">新型</span>@else<span style="font-size: 20px; color:#336699;">旧型</span>@endif({{ $cdType }})
</div>
@endif
</div>
<br />

<table class="tableBox MB10">
	<tr>
		<th>CRM2切り替え</th>
		<td>
			<form method="post" name="base_system_version_form" action="">
				<select name="base_system_version_flg">
					<option value="" >CRM1</option>
					<option{{ $a('base_system_version_flg') == '2' ? ' selected' : '' }} value="2">CRM2</option>
				</select>
				<input type="button" id="base_system_version_btn" class="btn" style="color:red" value="設定する" />
				<input type="hidden" name="act_flg" value="base_system_version" />
			</form>
		</td>
	</tr>
	@if ($isDevelop)
	<tr>
		<th>マイコレアプリ使用フラグ</th>
		<td>
			<form method="post" name="application_use_flg_form" action="">
				<div>
					<p>
						<select name="application_use_flg">
							<option value=""{!! $sel($a('application_use_flg') != '1') !!}>使用しない</option>
							<option value="1"{!! $sel($a('application_use_flg') == '1') !!}>使用する</option>
						</select>
					</p>
					<p style="padding:10px;">利用するプッシュapiサーバ:&emsp;
						<input type="radio" name="push_api_host" id="push_api_host1" value="0"{!! $chk((int) $a('push_api_host') === 0) !!} />
						<label for="push_api_host1">開発サーバ</label>&emsp;
						<input type="radio" name="push_api_host" id="push_api_host2" value="1"{!! $chk((int) $a('push_api_host') === 1) !!} />
						<label for="push_api_host2">本番サーバ</label>
					</p>
				</div>
				<input type="submit" id="application_use_flg_btn" class="btn" style="color:red" value="設定する" />
				<input type="hidden" name="act_flg" value="application_use_flg" />
			</form>
		</td>
	</tr>
	@endif
	<tr>
		<th>スマトピ連携利用するサーバー</th>
		<td>
			<form method="post" name="smatopi_environment_form" action="">
				<p style="margin:10px 0;">
					<input type="radio" name="smatopi_environment" value="develop" id="smatopi_environment_1" @if ($smatopiEnvironment === 'develop')checked='checked'@endif>
					<label for="smatopi_environment_1">開発サーバ</label>&emsp;
					<input type="radio" name="smatopi_environment" value="production" id="smatopi_environment_0" @if ($smatopiEnvironment !== 'develop')checked='checked'@endif>
					<label for="smatopi_environment_0">本番サーバ</label>
				</p>
				<input type="submit" id="smatopi_environment_btn" class="btn" style="color:red" value="設定する" />
				<input type="hidden" name="act_flg" value="smatopi_environment" />
			</form>
		</td>
	</tr>
	@foreach (['beacon_use_flg' => 'ビーコン使用', 'ssl_flg' => 'SSL使用'] as $flag => $label)
	<tr>
		<th>{{ $label }}</th>
		<td>
			<form method="post" name="{{ $flag }}_form" action="">
				<select name="{{ $flag }}">
					<option value=""{!! $sel($a($flag) != '1') !!}>使用しない</option>
					<option value="1"{!! $sel($a($flag) == '1') !!}>使用する</option>
				</select>
				<input type="button" id="{{ $flag }}_btn" class="btn" style="color:red" value="設定する" />
				<input type="hidden" name="act_flg" value="{{ $flag }}" />
			</form>
			<script type="text/javascript">
				$('#{{ $flag }}_btn').click(function(){
					var r = confirm('この内容で設定してよろしいでしょうか？');
					if ( r ) {
						$('form[name="{{ $flag }}_form"]').submit();
					}
				});
			</script>
		</td>
	</tr>
	@endforeach
	<tr>
		<th style="width: 200px;">アプリケーション - レイアウト</th>
		<td>
			<form method="post" name="app_layout_mode_form" action="">
				<select name="app_layout_mode">
					<option value=""{!! $sel($appLayoutMode != '1') !!}>基本レイアウト</option>
					<option value="1"{!! $sel($appLayoutMode == '1') !!}>カスタムレイアウト</option>
				</select>
				<input type="button" id="app_layout_mode_btn" class="btn" style="color:red" value="設定する" />
				<input type="hidden" name="act_flg" value="app_layout_mode" />
			</form>
			<script type="text/javascript">
				$('#app_layout_mode_btn').click(function(){
					var r = confirm('この内容で設定してよろしいでしょうか？');
					if ( r ) {
						$('form[name="app_layout_mode_form"]').submit();
					}
				});
			</script>
		</td>
	</tr>
</table>

<p id="menuHyoujiKanri" class="h2Title">メニュー表示管理</p>
<table class="tableBox">
	<tr>
		<th><label><small>メニュー表示</small></label></th>
		<td colspan="3" style="text-align:left;">
<form method="post"  class="form" action="">
@foreach ($menu['categories'] as $category)
@if ($category['index'] !== 0)
<input type="checkbox" name="category{{ $category['index'] }}" value="1"@if ($category['row']->system_flg === '1') checked @endif />
@else
<input type="hidden" name="category{{ $category['index'] }}" value="1">
@endif
{{ $category['row']->system_name }}<br />
<input type="hidden" name="category_cd{{ $category['index'] }}" value="{{ $category['row']->cd }}">
<!-- ##################### 孫 ################## -->
<table>
<tr>
@foreach ($category['children'] as $child)
<td>{{ $child['row']->system_name }}
<input type="checkbox" name="tb_base_system_child_flg{{ $child['index'] }}" value="1"@if ($child['row']->system_flg === '1') checked @endif>
<input type="hidden" name="tb_base_system_child_cd{{ $child['index'] }}" value="{{ $child['row']->cd }}">
@if ($child['grandchildren'])
<table>
@foreach ($child['grandchildren'] as $grandchild)
<tr>
<td>
{{ $grandchild['row']->system_name }}
</td>
<td>
<input type="checkbox" name="tb_base_system_grandchild_flg{{ $grandchild['index'] }}" value="1"@if ($grandchild['row']->system_flg === '1') checked @endif>
<input type="hidden" name="tb_base_system_grandchild_cd{{ $grandchild['index'] }}" value="{{ $grandchild['row']->cd }}">
</td>
</tr>
@endforeach
</table>
@endif
</td>
@endforeach
</tr>
</table>
<br />
@endforeach
<input type="hidden" name="change_tb_base_system_row" value="{{ $menu['count'] }}">
<input type="submit" name="enter" id="enter" class="btn" style="color:red" value="変更" />
<input type="hidden" name="child_max" value="{{ $menu['childMax'] }}" />
<input type="hidden" name="grandchild_max" value="{{ $menu['grandchildMax'] }}" />
<input type="hidden" name="act" value="change_master_cate" />
</form>
		</td>
	</tr>
</table>

<p id="karteRenketsu" class="h2Title">カルテ連結処理</p>
<p class="h3Title">現在の設定</p>
来店機能
{{ $raitenFlg == 1 ? 'On' : 'Off' }}
 /
会計機能
{{ match ((string) $pointFlg) { '1' => 'ポイントOn', '2' => 'スタンプOn', default => 'Off' } }}
 /
カルテ機能
{{ $karteFlg !== '' ? $karteFlg : 'Off' }}

<table class="tableBox">
<tr>
<th style="width: 40%;">カルテ連結機能を使わない場合</th>
<td>
	<form action="" method="post" name="related_form1" onsubmit="return confirm('設定してよろしいですか？');">
	<input type="hidden" name="raiten_flg" value="" />
	<input type="hidden" name="point_flg" value="" />
	<input type="hidden" name="karte_flg" value="" />
	<input type="hidden" name="act_flg" value="related" />
	<input type="submit" value="連結機能を使わず設定" />
	</form>
</td>
</tr>
@foreach ([2 => ['来店・ポイント・カルテを使う場合', '1'], 3 => ['来店・スタンプ・カルテを使う場合', '2'], 4 => ['来店・カルテを使う場合（ポイントは無し）', '']] as $n => [$label, $point])
<tr>
<th>{{ $label }}</th>
<td>
	<form action="" method="post" name="related_form{{ $n }}" onsubmit="return confirm('設定してよろしいですか？');">
	<input type="hidden" name="raiten_flg" value="1" />
	<input type="hidden" name="point_flg" value="{{ $point }}" />
	追加項目のカテゴリcd-><input type="text" name="karte_flg" size="2" value="{{ $karteFlg }}" />
	<input type="hidden" name="act_flg" value="related" />
	<input type="submit" value="この内容で設定する" />
	</form>
</td>
</tr>
@endforeach
</table>

<table class="tableBox">
<tr>
<th>マイページのカルテ記録時間</th>
<td>
	<form action="" method="post" name="related_form5" onsubmit="return confirm('設定してよろしいですか？');">
	<div>
	<input type="radio" name="karute_time_disp_flg" value=""{!! $chk($karuteTimeDispFlg != '1') !!} />&nbsp;カルテ記録時間を表示する<br />
	<input type="radio" name="karute_time_disp_flg" value="1"{!! $chk($karuteTimeDispFlg == '1') !!} />&nbsp;カルテ記録時間を表示しない<br />
	<input type="hidden" name="act_flg" value="karute_time_disp_flg" />
	</div>
	<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
	</form>
</td>
</tr>
</table>
<br />

<p id="mypageCustomize" class="h2Title">マイページのカスタマイズ</p>
<p class="h3Title">マイクーポン関連</p>
<table class="tableBox">
<tr>
<th>クーポンGET時の<br />「あとから使う」ボタン</th>
<td>
	<form action="" method="post" name="mypage_form1" onsubmit="return confirm('設定してよろしいですか？');">
	<table style="width:100%;">
	@foreach ($atokaraShops as $shop)
	<tr>
		<th>{{ $shop->kaishamei }}</th>
		<td>
			<div>
			<input type="radio" name="atokara_disp_flg[{{ $shop->shop_cd }}]" value=""{!! $chk($shop->text_data != '1') !!} />&nbsp;表示する&nbsp;
			<input type="radio" name="atokara_disp_flg[{{ $shop->shop_cd }}]" value="1"{!! $chk($shop->text_data == '1') !!} />&nbsp;表示しない
			</div>
		</td>
	</tr>
	@endforeach
	</table>
	<input type="hidden" name="act_flg" value="atokara_disp_flg" />
	<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
	</form>
</td>
</tr>
</table>
<br />

<p id="mongonKanri" class="h2Title">文言管理</p>
<p class="h3Title">現在の設定</p>
{{ $word ?: 'カルテ' }}
<small>※デフォルトは「カルテ」です。</small>
<table class="tableBox">
@foreach ([1 => ['カルテに設定する', ''], 2 => ['来店情報に設定する', '来店情報']] as $n => [$label, $value])
<tr>
<th>{{ $label }}</th>
<td>
	<form action="" method="post" name="mongon_form{{ $n }}" onsubmit="return confirm('設定してよろしいですか？');">
	<input type="hidden" name="naiyou" value="{{ $value }}" />
	<input type="hidden" name="act_flg" value="mongon" />
	<div><input type="submit" value="設定する" /></div>
	</form>
</td>
</tr>
@endforeach
<tr>
<th>その他に設定する</th>
<td>
	<form action="" method="post" name="mongon_form3" onsubmit="return confirm('設定してよろしいですか？');">
	<input type="text" name="naiyou" value="" />&lt;-設定する文言を記入
	<input type="hidden" name="act_flg" value="mongon" />
	<div><input type="submit" value="設定する" /></div>
	</form>
</td>
</tr>
</table>

@if ($shiireYa)
<p id="shiire_ya_conf" class="h2Title">仕入やへのボタン制御</p>
<form action="" method="post" name="">
<table class="tableBox">
	<tr>
		<th>表示On/Off</th>
		<td>
			<select name="shiire-ya_btn_hyouji_flg">
			<option value=""{!! $sel($shiireYa->hyouji_flg != '1') !!}>表示しない</option>
			<option value="1"{!! $sel($shiireYa->hyouji_flg == '1') !!}>表示する</option>
			</select>
		</td>
	</tr>
	<tr>
		<th>リンクアドレス</th>
		<td>
			<input type="text" name="shiire-ya_link_address" value="{{ $shiireYa->text_data }}" size="100" /><br />
			<small>※http:// から入力してください。</small>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align: center;">
			<input type="submit" value="設定する" style="color: #EE0000;" />
			<input type="hidden" name="act_flg" value="shiire_ya_conf" />
		</td>
	</tr>
</table>
</form>
@endif

<p id="pos_koubai_conf" class="h2Title">顧客追加項目とPOS購買履歴連係の設定</p>
<form action="" method="post" name="">
<table class="tableBox">
	<tr>
		<th>購買履歴に連係するフィールド</th>
		<td>
			<select name="pos_koubai_tb_search_form_cd">
			<option value="">&nbsp;</option>
			@foreach ($posKoubaiFields as $field)
			<option value="{{ $field->tb_search_form_cd }}"{!! $sel((string) $field->tb_search_form_cd === (string) $a('pos_koubai_tb_search_form_cd')) !!}>【{{ $field->cate_name }}】{{ $field->form_name }}</option>
			@endforeach
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align: center;">
			<input type="submit" value="設定する" style="color: #EE0000;" />
			<input type="hidden" name="act_flg" value="pos_koubai_conf" />
		</td>
	</tr>
</table>
</form>

<!-- / .contents --></div>
@endsection

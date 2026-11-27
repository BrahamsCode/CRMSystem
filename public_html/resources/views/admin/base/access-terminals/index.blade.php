{{-- CRM端末管理: system/category0/config/slim_list.php --}}
@extends('layouts.legacy')

@section('title', 'CRM端末管理 - ' . $siteName)

@section('menu', 'base')

@section('css')
<style>
/* 固有スタイル */
#dbMess{
	text-align:right;
}
#closeBtn{
	margin: 5px 7px 0 0;
}
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/category0/config/js/setPopWindow.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

var setPopWindow
//更新ポップアップ (alta y borrado del DNS en el servidor de soporte, que vuelve a slim_href.php / slim_del_href.php)
function openPopWindow(dataCd){
	var marginTop = '80px';
	var width = '650px';
	var height = '250px';
	var url = "http://ac.crm-s.jp/support/system/slim/api_slim_update.php";
	setPopWindow = new SetPopWindow(marginTop,width,height);
	setPopWindow.setIframe(url,dataCd,'');
}

function delOpenPopWindow(dataCd,subdomain){
	var marginTop = '80px';
	var width = '650px';
	var height = '250px';
	var url = "http://ac.crm-s.jp/support/system/slim/api_slim_del.php";
	setPopWindow = new SetPopWindow(marginTop,width,height);
	setPopWindow.setIframe(url,dataCd+'<>'+subdomain,'');
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■CRM端末管理</p>
<form method="post" name="form1" id="form1" class="form" action="/system/category0/config/slim_list.php">
<fieldset>
<legend>CRM端末登録</legend>
</fieldset>
<div class="btnBox">
<input type="button" name="enter" id="revise" onClick="openPopWindow('')" value="CRM端末登録" />
<input type="hidden" name="domin" id="domin" value="{{ request()->getHost() }}" />
<p>
<span class="attention">※</span>CRM端末登録処理を実行した段階では、下部リストに端末設定は追加されません。<br />
実際にCRM端末にケータイをかざして、動作確認を行ったタイミングでリストに追加されます
</p>
</div>
</form>
<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■CRM端末の店舗コード転送マッピング</p>
<div class="form">
@if ($rows->isNotEmpty())
<form action="" method="post" name="tb_access_mapping_form" onsubmit="return confirm('設定してよろしいですか？');">
※一度端末を携帯にかざすことで利用不可の端末を使用可能の状態にすることができます
<table class="tableBox">
	<tr>
		<th>端末用シリアル</th>
		<th>店舗コード</th>
		<th>On/Off</th>
		<th>状態</th>
		<th>削除</th>
	</tr>
	@foreach ($rows as $i => $row)
	<tr>
		<td>
		{{ str_replace('.crm-s.net', '', (string) $row->subdomain) }}
		<input type="hidden" name="tb_access_mapping[{{ $i }}][cd]" value="{{ $row->cd }}" />
		</td>
		<td>
		@if ($shops->isNotEmpty())
		<select name="tb_access_mapping[{{ $i }}][tb_shop_cd]">
			@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}"@if ((string) $row->tb_shop_cd === (string) $shop->cd) selected="selected"@endif>{{ $shop->kaishamei }}</option>
			@endforeach
		<option value="-1"@if ((string) $row->tb_shop_cd === '-1') selected="selected"@endif>ID入力端末</option>
		</select>
		@endif
		</td>
		<td>
			<select name="tb_access_mapping[{{ $i }}][hyouji_flg]">
			<option value=""@if ($row->hyouji_flg !== '1') selected="selected"@endif>表示しない</option>
			<option value="1"@if ($row->hyouji_flg === '1') selected="selected"@endif>表示する</option>
			</select>
		</td>
		<td>
			@if ($row->ninshou_flg === '1')<span style='color:green'>使用可能</span>@else<span style='color:red'>使用不可</span>@endif
		</td>
		<td>
		@if (preg_match('/^acs[0-9].*\.crm-s\.net$/', (string) $row->subdomain))
			<input type="button" value="削除" onClick="delOpenPopWindow('{{ $row->cd }}','{{ $row->subdomain }}')">
		@else
			<input type="checkbox" name="tb_access_mapping[{{ $i }}][disp_flg]" value="9" />
		@endif
		</td>
	</tr>
	@endforeach
</table>
※削除にチェックボックスが出ているシリアルはチェックを入れて下の「設定する」ボタンをクリックして削除できますが、古いデータですのでDNSの削除処理は行われません。
<div>
<input type="submit" value="設定する" />
<input type="hidden" name="act_flg" value="change_tb_access_mapping" />
</div>
</form>
@endif
</div>
<!-- / .contents --></div>
@endsection

{{--
	内容設定 común de los envíos — legacy: system/category3/inc/mail_frm.php
	(envoltorios mail_mag/mail_new.php, decomail/mail_new.php; más adelante follow, auto, reminder, anketo, mycoupon)

	Variables: $mailType, $menu, $toolName, $activeAc, $isMaster, $shops, $staffShopCd, $senderTitle, $routes,
	  $in (campos del formulario), $searchUniqid, $searchDisplay, $carry (ocultos que se arrastran),
	  $tags (MailReplaceTags::forMailForm), $templateCategories, $templateMailType, $couponEnabled, $imageUrl, $nowYear,
	  $registerType ('update' en la edición de un programado), $displayParameters (follow/auto)
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$v = fn (string $k) => $in[$k] ?? '';
	$deco = str_contains($mailType, 'deco');
	$reminder = in_array($mailType, ['yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'], true);
	$recurring = in_array($mailType, ['follow', 'auto', 'followdeco', 'autodeco'], true);
	$plainImage = in_array($mailType, ['mail', 'follow', 'auto'], true);
	$registerType = $registerType ?? null;
	$displayParameters = $displayParameters ?? null;
	$readonly = ' type="text" readonly="readonly" style="background:#FFF; border: 1px solid #999; width: 8em; text-align: center;" onclick="this.select(0,this.value.length)"';
	$hiddenFields = function (array $fields) {
		$html = '';
		foreach ($fields as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $subKey => $subVal) {
					$html .= '<input type="hidden" name="'.e($key).'['.e($subKey).']" value="'.e(is_scalar($subVal) ? $subVal : '').'">';
				}
			} else {
				$html .= '<input type="hidden" name="'.e($key).'" value="'.e((string) $val).'">';
			}
		}
		return $html;
	};
@endphp

@section('title', $toolName . '内容設定' . ' - ' . $siteName)

@section('css')
<style type="text/css"><!--
#mailTemplateBody {
	width: 15em;
	padding: 8px;
}
#mailTemplateBody p {
	margin: 0;
	line-height: 1.2em;
}
--></style>
<link href="{{ asset('legacy/system/category3/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript">
var search_uniqid = '{{ $searchUniqid }}';
</script>
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/jquery.colorbox.js') }}"></script>
@if (! $reminder)
	@if (! $recurring)
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/reigai_kaiin.js') }}"></script>
	@else
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/reigai_kaiin2.js') }}"></script>
	@endif
@endif
@if (! $v('coupon_cd') && in_array($mailType, ['mail', 'follow', 'auto'], true))
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/staff_entry.js') }}"></script>
@elseif (in_array($mailType, ['deco', 'followdeco', 'autodeco', 'remdeco', 'posremdeco'], true))
<!-- TinyMCE -->
<script type="text/javascript" src="{{ asset('legacy/system/category3/decomail/js/tiny_mce/tiny_mce.js') }}"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "advanced",
		editor_deselector : "mceNoEditor",
		plugins : "inlinepopups,paste,emojidocomo,emojiau,emojisoftbank",
		theme_advanced_buttons1 : "forecolor,separator,justifyleft,justifycenter,justifyright,separator,link,unlink",
		theme_advanced_buttons2 : "emojidocomo,emojiau,emojisoftbank",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		convert_urls: false,
		convert_fonts_to_spans : false,
		formats : {
			forecolor : {inline : 'font',attributes : {color : '%value'}},
			fontsize : {inline : 'font',attributes : {size : '%value'}}
		},
		external_image_list_url : "js/image_list.js",
		extended_valid_elements : "td[*],div[*]",
		language:"ja",
		height : '400px'
	});
</script>
<!-- /TinyMCE -->
@endif

<script type="text/javascript">
@if ($v('pic_name') !== '' && $v('mailmagcd') === '')
var myTime = {{ (int) $v('pic_name') }};
@endif

var kaiin_max = parseInt('999');

$(document).ready(function() {
	textlength();

	//$active_ac: mailmag=1, anketo=8, mycoupon=7 ,follow=10, auto=11
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);

@if ($couponEnabled)
	$("#coupon").colorbox({opacity:0, width:500, height:500, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#dialog"});

	$("#btn_search").click(function(){
		send_cd = $("#cd_search").val();
		send_shop_cd = $("#shop_cd").val();
		send_title = $("#title_search").val();
		mail_type = '{{ $mailType }}';
		$("#result").load("{{ route('legacy.promocion.inc.ajax_search_coupon') }}",{
			cd:send_cd,
			shop_cd:send_shop_cd,
			title:send_title,
			mail_type:mail_type
		});
	});
@endif
});

function Check_form() {
@if (! $deco)
	if (document.mail_form.naiyou.value == "") {
		alert('{{ $toolName }}内容を入力して下さい。');
		return false;
	}
@endif
	return true;
}

function textlength() {
	var count = 0;
	text = escape( document.mail_form.naiyou.value );
	while (text) {
		if (text.charAt(0) == '%') {
			if (text.charAt(1).toLowerCase() == 'u') {
				count += 2;
				text = text.substring(6);
			} else {
				count += 1;
				text = text.substring(3);
			}
		} else {
			count += 1;
			text = text.substring(1);
		}
	}

	if ($('#FORM_tlen').length > 0) $('#FORM_tlen').val(count);
}

// -------------------- クーポン情報を追加 --------------------
function setCoupon(cd, title) {
	if (typeof(cd) == "undefined") cd = "";
	if (typeof(title) == "undefined") title = "";

	var kaijo = '';
	if (title != "") kaijo = '&emsp;<a onclick="setCoupon();" style="margin-left: 5px; color: #EE0000; text-decoration: underline;">解除</a><br/>';

	$("#coupon_detail").html(title+kaijo);
	$("#get_coupon_cd").attr('value',cd);
	$("#get_coupon_text").attr('value',title);
	$('#coupon').colorbox.close();
}

var lastBody = "";
function getTemplateTitleData(valor, mailtype) {
	$("#mailTemplateBody").html('');
	if (valor=="") {
		$("#mailTemplateSelect").html('').hide();
	} else {
		$("#mailTemplateSelect").load("{{ route('legacy.promocion.inc.ajax_get_template_titles') }}?cd="+valor+"&mail_type="+mailtype).show();
	}
}

function getTemplateBodyData(valor) {
	if (valor=="") {
		$("#mailTemplateBody").html('');
		lastBody = "";
	} else {
		$.get("{{ route('legacy.promocion.inc.ajax_get_template_body') }}?cd="+valor, function(resp){
			lastBody = resp;
			if (typeof(tinyMCE) == 'undefined') {
				$("#mailTemplateBody").html(resp.replace(/\n/g,'<br />'));
			} else {
				$("#mailTemplateBody").html(resp);
			}
		});
	}
}

function insertMailBody() {
	if (lastBody == "") return;

	if (confirm ("内容を置き換えてもよろしいですか？")) {
		if (typeof(tinyMCE) == 'undefined') {
			$("#naiyou").val(lastBody);
		} else {
			var ed = tinyMCE.get("naiyou");
			ed.setContent(lastBody);
		}
	} else {
		return false;
	}
}

function deletePic() {
	var conf = confirm("削除してもよろしいですか？\n\r※【メール配信確認】ボタンをクリックしてから反映されます。");
	if (conf) {
		$("#pic_name").remove();
		$("#gazou").html('');
		$("#mail_form").append('<input type="hidden" name="pic_name" id="pic_name" value="delete" />');
	}
}
</script>
@endsection

@section('content')
<input type="hidden" id="this_search_uniqid" value="{{ $searchUniqid }}" />

@if ($couponEnabled)
<div style="display:none;">
	<div id="dialog">
	<table class="tableBox" style="margin-bottom: 0px;">
	<tr>
	<th>店舗</th>
	<td colspan="3">
@if ($isMaster && $shops->count() > 1)
	<select name="shop_cd" id="shop_cd">
	<option value="">&nbsp;</option>
	@foreach ($shops as $shop)
	<option value="{{ $shop->cd }}" @selected($v('shop_cd') == $shop->cd)>{{ $shop->kaishamei }}</option>
	@endforeach
	</select>
@else
{{ $shops->firstWhere('cd', $staffShopCd)?->kaishamei }}
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $staffShopCd }}">
@endif
	</td>
	</tr>

	<tr>
	<th>タイトル</th>
	<td><input type="text" name="title_search" id="title_search" /></td>

	<th>コード</th>
	<td><input type="text" name="cd_search" id="cd_search" /></td>
	</tr>

	</table>
	<div class="btnBox"><input type="button" name="btn_search" id="btn_search" class="btnStyleS" value="検索" /></div>
		<div id="result" style="width:100%;height:325px;overflow:auto;"></div>
	</div>
</div>
@endif

<div class="contents">
<p class="h2Title">■{{ $toolName }}内容設定</p>
<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

	  <form action="{{ $registerType === 'update' ? $routes['editConfirm'] : $routes['confirm'] }}" method="post" enctype="multipart/form-data" name="mail_form" id="mail_form" class="form" onsubmit="return Check_form()" style="margin-bottom: 300px;">
	  @csrf

<table class="tableBox">
  <tr>
    <th><label for="title">{{ $toolName }}タイトル</label></th>
    <td><input type="text" name="title" id="title" class="writeArea" maxlength="100" value="{{ $v('title') }}" /></td>
  </tr>

  <tr>
    <th><label for="naiyou">{{ $toolName }}内容<em>※</em></label></th>
    <td>
	<p>
	@foreach ($tags['tags'] as $tag)
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{' . $tag['word'] . '}' }}"{!! $readonly !!} /></font> と入力された箇所には顧客の<font color="red">{{ $tag['label'] }}</font>が挿入されます。<br />
	@if (! empty($tag['note']))
	　 {{ $tag['note'] }}<br />
	@endif
	@endforeach

※ <font color="black" style="font-weight: bold;"><input value="{日後:●●}"{!! $readonly !!} /></font> と入力された箇所には<font color="red">配信日からの●●日後の日付</font>が挿入されます。<br />
※ 『●●』には半角数字をご入力ください。<br />

	@foreach ($tags['notes'] as $note)
	{{ $note }}<br />
	@endforeach
	@foreach ($tags['extra'] as $tag)
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{' . $tag['word'] . '}' }}"{!! $readonly !!} /></font> と入力された箇所には顧客の<font color="red">{{ $tag['label'] }}</font>が挿入されます。<br />
	@endforeach
	@if ($tags['survey'] !== null)
	<br />
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{アンケートURL[' . $tags['survey'] . ']}' }}"{!! $readonly !!} /></font> と入力された箇所には<font color="red">お客様アンケートのURL</font>が挿入されます。<br />
	@endif
	@foreach ($tags['reservation'] as $tag)
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{' . $tag['word'] . '}' }}"{!! $readonly !!} /></font> と入力された箇所には<font color="red">{{ $tag['label'] }}</font>が挿入されます。<br />
	@endforeach
	</p>

<table style="border:none;">
<tr>
<td style="border:none;">
    <textarea name="naiyou" rows="20" id="naiyou" class="writeArea mB5" wrap="soft" onKeyup="textlength()">{{ $v('naiyou') }}</textarea>
</td>
@if ($templateCategories->isNotEmpty())
<td style="border:none;">
<input type="button" onclick="insertMailBody();" value="　←挿入　" />
</td>
<td style="border:none;vertical-align:top;">
<p style="background-color:#CCCCCC;margin:1px 0;color:#FFFFFF;text-align:center;">{{ $toolName }}テンプレート</p>
<select id="mailTemplateCategorySelect" onchange="getTemplateTitleData(this.value, '{{ $templateMailType }}');" style="width:100%;">
	<option value="">▼カテゴリーを選択</option>
@foreach ($templateCategories as $category)
	<option value="{{ $category->cd }}">{{ $category->name }}</option>
@endforeach
</select><br />
<select id="mailTemplateSelect" onchange="getTemplateBodyData(this.value);" style="display:none;width:100%;">
</select>
<div id="mailTemplateBody" style="border:1px solid #CCCCCC;height:322px;margin-top:10px;overflow-y:auto;">
</div>
</td>
@endif
</tr>
</table>

@if (! $deco)
	<br />
現在の文字数&nbsp;<input type="text" id="FORM_tlen" name="FORM_tlen" size="5" class="inputS" readonly="readonly" /><br />
<br />
[ 注意事項 ]<br />
※古い機種では半角で500文字を越えると表示できない場合があります。<br />
※受信される機種の画面幅は多種多様ですので、<span style="color: #113366; font-weight: bold;">横幅に依存した文章にはご注意ください</span>。<br />
※半角カタカナや機種依存文字などを使用すると、端末によっては文字化けを起こす事があります。
@else
<br />
[ 注意事項 ]<br />
※<span style="color: #FF0000; font-weight: bold;">外部のサイトから画像を読み込むとメルマガが飛ばない可能性</span>があります。<br />
※受信される機種の画面幅は多種多様ですので、<span style="color: #113366; font-weight: bold;">横幅に依存した文章にはご注意ください</span>。<br />
※一部スマートフォンなどでは、画像が表示されない事があります。<br />
※一部の機種では、使用した画像などが全て表示されない場合があります。<br />
※デコメール、HTMLメール機能を備えていない端末では正しく表示されない場合があります。<br />
※<span style="color: #113366; font-weight: bold;">過去配信メールから、テキストコピーで文章を張り付けると本文改行されない</span>事がありますので、ご注意ください。<br />
※ブログなどの文章をコピーして貼り付けると、メール配信時に文章が正しく表示されない事があります。<br />
&emsp;そのため<span style="color: #113366; font-weight: bold;">入力は手入力で行うことを推奨</span>しています。
@endif
    </td>
  </tr>

@if ($plainImage)
  <tr>
    <th><label for="staff_name">画像</label></th>
    <td>
<div id="gazou">
@if ($v('pic_name') !== '' && $v('pic_name') !== 'delete' && $imageUrl)
<img src="{{ $imageUrl }}" alt="メルマガ写真" />
<input type="hidden" name="pic_name" id="pic_name" value="{{ $v('pic_name') }}" />
<br/>
<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像添付解除</a>
@elseif ($v('coupon_cd') !== '')
<img src="{{ asset('storage/system/category3/coupon/uploads/coupon/' . (int) $v('coupon_cd') . '.jpg') }}" alt="メルマガ写真" class="mB10" /><input type="hidden" name="coupon_cd" id="coupon_cd" value="{{ $v('coupon_cd') }}" />
@elseif ($v('p_s_path') !== '')
<img alt="メルマガ写真" src="{{ $v('p_s_path') }}" />
<input type="hidden" name="p_s_path" id="p_s_path" value="{{ $v('p_s_path') }}" />
<br/>
<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像添付解除</a>
@endif
</div>

@if ($v('coupon_cd') === '')
		<p id="photo">&nbsp;</p>
@endif
    </td>
  </tr>

  <tr style="display:none">
    <th><label for="staff_name">画像用<br>リンク</label></th>
    <td>
        <input type="text" name="pic_link" id="pic_link" value="{{ $v('pic_link') }}" class="writeArea">
        <p>[ 注意事項 ]<br>※画像が設定されている場合のみ、入力された値が利用されます。</p>
    </td>
  </tr>
@endif

  <tr>
    <th>振分条件</th>
    <td>
		{{ $searchDisplay }}
   </td>
  </tr>

@if ($couponEnabled)
  <tr>
    <th><label for="coupon_detail">添付クーポン</label></th>
	<td><span id="coupon_detail">{{ $v('get_coupon_text') }}@if ($v('get_coupon_cd') !== '')&emsp;<a onclick="setCoupon();" style="margin-left: 5px; color: #EE0000; text-decoration: underline;">解除</a><br/>@endif</span>
	<input type="button" name="coupon" id="coupon" class="btnStyleM" value="クーポン選択" />
	<input type="hidden" id="get_coupon_cd" name="get_coupon_cd" value="{{ $v('get_coupon_cd') }}" />
	<input type="hidden" id="get_coupon_text" name="get_coupon_text" value="{{ $v('get_coupon_text') }}" /></td>
  </tr>
@elseif ($mailType === 'mycoupon' && ($coupon ?? null))
  <tr>
    <th><label for="coupon_detail">添付クーポン</label></th>
	<td><span style="font-weight:bold">{{ $coupon->title }}</span><br/>{!! nl2br(e(mb_strimwidth((string) $coupon->comment, 0, 100, '...'))) !!}</td>
  </tr>
@endif

@if (! $reminder)
  <tr>
   <th>例外顧客</th>
   <td>
    <input type="button" name="reigai_btn" id="reigai_btn" class="btnStyleM" value="顧客選択" />
	<input type="hidden" id="reigai_kaiin_cds" name="reigai_kaiin_cds" value="{{ $v('reigai_kaiin_cds') }}" />
	<div id="reigai_kaiin_disp">
	</div>
   </td>
  </tr>
@endif

  <tr>
    <th><label for="tourokusya">フッター表記店舗</label></th>
    <td>
@if ($isMaster)
		<select name="tourokusya">
		<option value="">▼店舗選択</option>
		@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}" @selected((string) $v('tourokusya') === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
		@endforeach
		</select>
@else
		{{ $shops->firstWhere('cd', $staffShopCd)?->kaishamei }}
		<input type="hidden" name="tourokusya" id="tourokusya" value="{{ $staffShopCd }}" />
@endif
</td>
  </tr>
  <tr>
    <th><label for="bikou">備考</label></th>
    <td><textarea name="bikou" id="bikou" class="writeArea mceNoEditor" rows="3" onkeyup="resize_textarea(event)">{{ $v('bikou') }}</textarea></td>
  </tr>

@if (! array_key_exists('step2_jikan', $in))
  <tr>
    <th><label for="send_flg1">配信時間</label></th>
    <td>
	@if (! $recurring)
		@if ($registerType !== 'update')
			<input name="send_flg" id="send_flg1" type="radio" value="1" @checked($v('send_flg') !== '0')><label for="send_flg1">即時配信する</label>
			<input name="send_flg" id="send_flg2" type="radio" value="0" @checked($v('send_flg') === '0')><label for="send_flg2">時間指定する</label>
		@endif

		<select name="nen" id="nen" class="selectBox">
			@for ($i = $nowYear; $i < $nowYear + 5; $i++)
				<option value="{{ $i }}" @selected((string) $v('nen') === (string) $i)>{{ $i }}年</option>
			@endfor
		</select>

		<select name="tuki" id="tuki" class="selectBox">
			@for ($i = 1; $i <= 12; $i++)
				@php $d = sprintf('%02d', $i); @endphp
				<option value="{{ $d }}" @selected($v('tuki') === $d)>{{ $d }}月</option>
			@endfor
		</select>

		<select name="hi" id="hi" class="selectBox">
			@for ($i = 1; $i <= 31; $i++)
				@php $d = sprintf('%02d', $i); @endphp
				<option value="{{ $d }}" @selected($v('hi') === $d)>{{ $d }}日</option>
			@endfor
		</select>
	@endif

	<select name="jikan" id="jikan" class="selectBox">
		@for ($i = 0; $i <= 23; $i++)
			@php $d = sprintf('%02d', $i); @endphp
			<option value="{{ $d }}" @selected($v('jikan') === $d)>{{ $d }}時</option>
		@endfor
	</select>

	<select name="fun" id="fun" class="selectBox">
		@for ($i = 0; $i <= 55; $i += 5)
			@php $d = sprintf('%02d', $i); @endphp
			<option value="{{ $d }}" @selected($v('fun') === $d)>{{ $d }}分</option>
		@endfor
	</select>&emsp;{!! $displayParameters !!}

    </td>
  </tr>
@else
  <tr>
    <th><label for="send_flg1">配信時間</label></th>
    <td>{!! $displayParameters !!}
    </td>
  </tr>
@endif

</table>

<p>※{{ $toolName }}タイトルを空白にした場合は<span style="color: #113366; font-weight: bold;">「{{ $senderTitle }}からのお知らせ！」</span>が入ります。<br />
@if (! $recurring)※時間指定に過去を設定することはできません。<br />@endif
※「登録者」及び「備考」は任意の項目です。その情報はユーザーには送信されません。<br />
@if ($registerType !== 'update')※<span style="color: #113366; font-weight: bold;">抽出条件の設定し直しを行う場合</span>は、次の「{{ $toolName }}配信確認画面」から「抽出し直す」を選択してください。@endif
</p>

<div class="btnBox">
<input type="hidden" name="search_display" value="{{ $searchDisplay }}" />
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
<input type="hidden" name="mail_type" value="{{ $mailType }}" />
@if ($v('mailmagcd') !== '')
<input type="hidden" name="mailmagcd" id="mailmagcd" value="{{ $v('mailmagcd') }}" />
@endif
{!! $hiddenFields($carry) !!}
@if ($registerType === 'update' && ($canCancel ?? false))
<input type="button" name="dummy_btn" id="dummy_btn" class="btnStyleM" value="予定解除する" onClick="document.delete_btn.submit()">
@endif
@if ($displayParameters)
<input type="hidden" name="display_parameters" value="{{ $displayParameters }}" />
@endif
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $toolName }}配信確認へ">
</div>
</form>

@if ($registerType === 'update')
<form name="delete_btn" action="{{ $routes['cancel'] }}" method="post">
@csrf
<input type="hidden" name="mailmagcd" value="{{ $v('mailmagcd') }}">
</form>
@endif

<!-- / .contents --></div>
@endsection

{{--
    オススメ管理 (system/category6/user_hp/recommend.php): formulario de alta (o de modificación con ?cd=)
    y lista paginada con 表示 / 修正 / 削除.

    CONTRATO DE DATOS
        $menuName         string  tb_mobile_navi cd = 9 con mb_convert_kana('KV') (legacy $tb_mobile_navi_target_name)
        $recommendation   Recommendation|null  la オススメ en modificación (disp_flg = '1'), null en alta
        $editing          bool    llegó ?cd= (el legacy decide 修正/登録 por $_REQUEST['cd'], exista o no)
        $formVisible      bool    cKengen 1016 nivel 2 (alta) o 3 (modificación) o master; si no, style="display: none;"
                                  y, a diferencia del legacy, el formulario no se imprime (tampoco su AjaxUpload)
        $canEdit          bool    cKengen 1016 nivel 3 o master (checkbox 表示, botón 修正, 表示情報を更新)
        $canDelete        bool    cKengen 1016 nivel 4 o master (botón 削除 y el form_delete oculto)
        $showShopSelect   bool    master y más de una tienda: <select name="tb_shop_cd">; si no, nombre + hidden
        $shops            Collection<Shop>  tb_shop disp_flg <> '9' ORDER BY cd (cd, kaishamei)
        $sessionShopCd    int|null, $sessionShopName string  tienda de la sesión
        $imageUrl         string|null  imagen actual en modificación (<cd>_big si existe)
        $applicationUseFlg bool   admin_master.application_use_flg = 1: muestra 「アプリへのプッシュ通知」
        $pager            array   LegacyPageControl::paginate (items = Recommendation, kensu 10 por defecto)
        $service          RecommendationService  (listImage, articleLink, shopLabel para cada fila)
        $siteName         string  (MypageServiceProvider)

    Texto (CONVENCIONES §4): se guarda con LegacyText::input() (get_form2 de recommend_register.php), así que
    title/link/naiyou/pic_link/disp_date se imprimen con {!! !!}, como el legacy.

    Diferencias con el legacy: no está el bloque SNS連携 (SNS fuera de alcance); el JS de staff_entry.js
    va en línea con las URL de las rutas y el _token (AjaxUpload envía un <form> en un iframe, sin jQuery).
--}}
@extends('layouts.legacy')

@section('title', $menuName . '管理 - ' . $siteName)

@section('menu', 'mypage')

{{-- El legacy usa $useLatestJquery = true (inc/head.php): jQuery 1.11.2 + jQuery UI 1.11.4 --}}
@section('jquery', 'jquery-1.11.2.min.js')
@section('jquery_ui', 'jquery-ui-1.11.4.min.js')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".accordionBlock").each(function(i, elm){
		$(elm).accordion({ active: 1 })
	});
	$(".ac1").accordion({ active: 0 });
});

function submitChk () {
	if(document.form1.news_naiyou.value == ""){
		alert("内容が空白です。\n\n内容をご記入下さい。");
		return false;
	}else{

		var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
		return flg;

	}
}

function submitChk2 (val) {
	var element = eval("document.form_delete");

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	if(flg){
		element.cd.value = val;
		element.submit();
	}
}

function deletePic() {
	var disp = $("#revise").val();
	var conf = confirm("削除してもよろしいですか？\n\r※【"+disp+"】ボタンをクリックしてから反映されます。");
	if (conf) {
		$("#gazou").html('');
		$("#pic_name").remove();
		$("#form1").append('<input type="hidden" name="pic_name" id="pic_name" value="delete" />');
	}
}
</script>

@if ($formVisible)
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
{{-- js/staff_entry.js con las URL de las rutas y el _token --}}
<script type="text/javascript">
var picExt;
var piclastExt = '';

var mylastTime;

if(myTime == undefined){
	var myTime = 0;
}

$(document).ready(function(){
	new AjaxUpload('#photo', {
		action: '{{ route('legacy.mypage.recommend-upload') }}',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){

				mylastTime = myTime;

				piclastExt = picExt;

				picExt = ext;

				today = new Date();

				myTime = today.getTime();

				mytitle = $("#title").val();

				mynumber = $("#bangou").val();

				mynaiyou = $("#naiyou").val();

				mykigen = $("#kigen").val();

				this.setData({

					'_token': '{{ csrf_token() }}',

					'ext': picExt,

					'time' : myTime,

					'lastTime' : mylastTime,

					'lastExt' : piclastExt,

					'title' : mytitle,

					'number' : mynumber,

					'naiyou' : mynaiyou,

					'kigen' : mykigen

				});

			} else {

				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');

				return false;

			}

		},

		onComplete : function(file, ext){

			$("#pic_name").remove();

			$("#form1").append("<input type=\"hidden\" name=\"pic_name\" id=\"pic_name\" value=\""+myTime+"\" />");

			$('#gazou').load("{{ route('legacy.mypage.recommend-temp-picture') }}?ext=" + picExt + "&t="+myTime, function() {
				$('#gazou').append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像添付解除</a>');
			});

			$('#zip_search').click();

		}

	});

});
</script>
@endif
@endsection

@section('content')
@php
    // 日付: hoy, o el disp_date (Y/m/d) de la オススメ en modificación
    $dateParts = explode('/', (string) ($recommendation->disp_date ?? ''));
    [$year, $month, $day] = ($dateParts[0] ?? '') !== ''
        ? [(int) $dateParts[0], (int) ($dateParts[1] ?? 1), (int) ($dateParts[2] ?? 1)]
        : [(int) date('Y'), (int) date('n'), (int) date('j')];
    // create_num_options: valores < 10 con cero delante
    $pad = fn (int $n) => $n < 10 ? '0' . $n : (string) $n;
    $linkType = ($recommendation->link_type ?? '') !== '' ? $recommendation->link_type : 'http://';
    $hidden = ($recommendation->hyouji_flg ?? null) === '9';
@endphp
<div class="contents" @if (! $formVisible) style="display: none;" @endif>
@if ($formVisible)

<p class="h2Title">■{{ $menuName }}情報登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.mypage.recommendations.store') }}" onsubmit="return submitChk()">
@csrf


<fieldset>
<legend>{{ $menuName }}情報管理</legend>

<table class="tableBox">

<tr>
<th>登録店舗</th>
<td>
@if ($showShopSelect)
		<select name="tb_shop_cd">
		@if ($recommendation?->tb_shop_cd)<option value="{{ $recommendation->tb_shop_cd }}"  >{{ $shops->firstWhere('cd', $recommendation->tb_shop_cd)?->kaishamei }}</option>@endif
		<option value="">全店舗</option>
		@foreach ($shops as $shop)
<option value="{{ $shop->cd }}"  >{{ $shop->kaishamei }}</option>
		@endforeach
		</select>

@else

		{{ $sessionShopName }}
		<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $sessionShopCd }}" />

@endif
</td>
</tr>

<tr>
	<th>表示フラグ</th>
	<td>
	<input type="radio" name="hyouji_flg" id="hyouji_flg1" class="checkBtn" value=""@if (! $hidden) checked="checked"@endif /><label for="hyouji_flg1">表示</label><br />
	<input type="radio" name="hyouji_flg" id="hyouji_flg2" class="checkBtn" value="9"@if ($hidden) checked="checked"@endif /><label for="hyouji_flg2">非表示</label>
	</td>
</tr>

<tr>
<th>日付</th>
<td>
		<select name="news_year">
		@for ($i = min((int) date('Y'), $year); $i <= max((int) date('Y') + 1, $year); $i++)
<option value="{{ $pad($i) }}"@if ($i == $year) selected @endif>{{ $pad($i) }}</option>
		@endfor
		</select>年

		<select name="news_month">
		@for ($i = 1; $i <= 12; $i++)
<option value="{{ $pad($i) }}"@if ($i == $month) selected @endif>{{ $pad($i) }}</option>
		@endfor
		</select>月

		<select name="news_day">
		@for ($i = 1; $i <= 31; $i++)
<option value="{{ $pad($i) }}"@if ($i == $day) selected @endif>{{ $pad($i) }}</option>
		@endfor
		</select>日
</td>
</tr>

<tr>
	<th>タイトル</th>
	<td><input type="text" name="title" class="writeArea" value="{!! $recommendation->title ?? '' !!}" /></td>
</tr>
<tr>
	<th>リンク先</th>
	<td>

	<script type="text/javascript">

	$(document).ready(function(){

		$('#link_type').change(function(){
			$('#link_type_text').html($(this).val());
		});

	});

	</script>
	リンクの形式&nbsp;
	<select name="link_type" id="link_type">
	<option value="http://"@if ($linkType === 'http://') selected="selected"@endif>URL</option>
	<option value="tel:"@if ($linkType === 'tel:') selected="selected"@endif>電話番号</option>
	<option value="mailto:"@if ($linkType === 'mailto:') selected="selected"@endif>メールアドレス</option>
	</select>
	<br />
	<span id="link_type_text">{{ $linkType }}</span>&nbsp;<input type="text" name="link" class="writeArea" value="{!! $recommendation->link ?? '' !!}" style="ime-mode:disabled" /><br />
	<span class="textS">※タイトルがリンクになります。タイトルが無い場合はリンクが反映されません。</span>

	</td>
</tr>

<tr>
<th>内容</th>
<td>
		<textarea name="news_naiyou" id="news_naiyou" class="writeArea" rows="6" style="width: 15em;">
{!! $recommendation->naiyou ?? '' !!}</textarea><br />
<span class="textS">※表示される機種の画面幅は多種多様ですので、横幅に依存した文章にはご注意ください。</span>
</td>
</tr>

<tr>
<th>画像</th>
<td>
	<div id="gazou">
	@if ($imageUrl !== null)
	<img src="{{ $imageUrl }}" alt="お勧め" class="mB10" /><a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像添付解除</a>
	@endif
	</div>
	<p id="photo">&nbsp;</p><input type="hidden" name="pic_name" id="pic_name" value="" />
	@include('admin.mypage.partials.image-tooltip')
	<br/><span class="textS">※アップロード画像サイズは横幅240ピクセルに最適化されます。</span>
</td>
</tr>

<tr>
  <th><label for="staff_name">画像用リンク</label></th>
  <td>
      <input type="text" name="pic_link" id="pic_link" value="{!! $recommendation->pic_link ?? '' !!}" class="writeArea">
      <p>[ 注意事項 ]<br>※画像が設定されている場合のみ、入力された値が利用されます。</p>
  </td>
</tr>
@if ($applicationUseFlg)
<tr>
	<th>アプリへのプッシュ通知</th>
	<td>
		<label><input type="radio" name="push" value="1">ON</label>
		<label><input type="radio" name="push" value="0" checked="checked">OFF</label>
	</td>
</tr>
@else
	<input type="hidden" name="push" value="0">
@endif
</table>

</fieldset>



<div class="btnBox">
@if ($editing)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $recommendation?->cd }}" />
@else
<input type="submit" name="enter" id="revise" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>
@endif



<!-- / .contents --></div>

<div class="contents">
@if ($pager['total'] > 0)
<p class="h2Title">■{{ $menuName }}情報一覧</p>

<div class="form">
<form name="update_hyouji_flg" method="post" action="{{ route('admin.mypage.recommendations.index') }}">
@csrf
	<table class="tableBox2">
		<tr>
			<th style="width: 4em;">No</th>
			<th style="width: 4em;">日付</th>
			<th style="width: 4em;">内容</th>
			<th>登録店舗</th>
			<th style="width: 4em;">表示</th>
			<th style="width: 4em;">修正・削除</th>
		</tr>
@foreach ($pager['items'] as $row)
@php
    [$imagePath, $bigPath] = $service->listImage($row);
    $link = $service->articleLink($row->link_type, $row->link);
    $shopLabel = $service->shopLabel($row);
@endphp
		<tr @if ($loop->index % 2 != 0) class="tableBg"@endif>
			<td align="top">{{ $row->cd }}</td>
			<td align="top">{!! $row->disp_date !!}</td>
			<td align="top">
			<div style="overflow-y:scroll; width: 17em; max-height: 150px; border: 1px solid #999999; background: #FFFFFF;">
			<table style="width: 100%;">
			<tr>
		@if ($imagePath !== null)<td valign="top" width="80" style="margin:0; padding: 0;"><a href="{{ $bigPath }}" target="_blank"><img src="{{ $imagePath }}?pcc={{ time() }}" width="75" align="left" /></a></td>@endif
			<td valign="top" style="margin:0; padding: 0;">
                <span class="textS"><font size="-1">{!! $row->disp_date !!}</font></span><br />
		@if ($link !== '')<a href="{!! $link !!}" target="_blank">{!! $row->title !!}</a>@else{!! $row->title !!}@endif<br />
			<span class="textS"><font size="-1">{!! nl2br($row->naiyou) !!}</font></span><br clear="all" /></td>
			</tr>
			</table>

			</div></td>
			<td align="top">{{ $shopLabel ?? '全店舗' }}</td>

			<td align="center">
			@if ($canEdit)
				<input type="checkbox" name="hyouji_flg_array[{{ $row->cd }}]"@if ($row->hyouji_flg !== '9') checked @endif />
				<input type="hidden" name="update_cd[]" value="{{ $row->cd }}" />
			@else
				{{ $row->hyouji_flg !== '9' ? ' 表示中' : '非表示' }}
			@endif
			</td>

			<td align="top" class="textCenter" width="100">
			@if ($canEdit)<a href="{{ route('admin.mypage.recommendations.index', ['cd' => $row->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>@endif
			@if ($canDelete)<a href="javascript:void(0)" onclick="submitChk2({{ $row->cd }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>@endif
			</td>
		</tr>
@endforeach
	</table>
@if ($canEdit)
<div style="text-align:right;">
<input type="submit" value="表示情報を更新"/>
</div>
@endif


<input type="hidden" name="kensu" id="kensu" value="{{ $pager['kensu'] }}" />
<input type="hidden" name="page" id="page" value="{{ $pager['page'] }}" />
<input type="hidden" name="update_hyouji_flg" id="update_hyouji_flg" value="update" />
</form>

@if ($canDelete)
<form method="post" name="form_delete" id="form_delete" class="displayNone" action="{{ route('admin.mypage.recommendations.destroy') }}">
@csrf
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>
@endif

<!--/.form--></div>

<ul class="btnBox2">
@include('admin.mypage.partials.page-control', ['pageUrl' => route('admin.mypage.recommendations.index')])
</ul>
@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>データがありません。<br/><br/></p>
@endif


<!-- / .contents --></div>
@endsection

{{--
    求人情報 新規登録 (recruit_new.php, $mode = 'create') y 修正 (recruit_update.php, $mode = 'edit').

    CONTRATO DE DATOS
        $mode               'create' | 'edit'
        $recruit            Recruit|null   el 求人 en modificación (null en alta)
        $shops              Collection<Shop>  tb_shop disp_flg <> '9' ORDER BY cd; sin マスター solo la de la sesión
        $singleShop         Shop|null      con una sola tienda o sin マスター: se muestra su nombre + hidden
        $showAllShopsOption bool           マスター: añade <option value="-1">全店舗</option>
        $selectedShopCd     int|string|null  tienda elegida (old(), la del 求人 o la única)
        $categories         Collection<RecruitCategory>  職種 de esa tienda (disp_flg = '1', tienda NULL o igual)
        $categoryAlert      bool           alta sin 職種 registrados: alert() al final (solo recruit_new.php)
        $service            RecruitService (imageUrl, tempImageUrl)
        $errors             MessageBag     errores de RecruitRequest (td class="Ng" + <p class="m0">)
        $siteName           string         (MypageServiceProvider)
    Los valores de los campos salen de old() (vuelta con error) o del 求人.

    Diferencias con el legacy:
      - Con una sola tienda el desplegable de 職種 salía con opciones vacías (usaba $data_array en vez de $row):
        aquí lista los 職種 de la tienda.
      - El <title> de 修正 decía 「新規登録」 al abrirlo desde el detalle ($act sin 'up'): aquí dice 「修正」.
      - El JS de item_entry.js va en línea con las URL de las rutas y el _token.
--}}
@extends('layouts.legacy')

@section('title', '求人情報 ' . ($mode === 'create' ? '新規登録' : '修正') . ' - ' . $siteName)

@section('menu', 'mypage')

@section('css')
<style type="text/css">
#gazou1 img {
    vertical-align: bottom;
}
#gazou1 br {
    display: none;
}
#gazou2 img {
    vertical-align: bottom;
}
#gazou2 br {
    display: none;
}
</style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".ac3").accordion('option', 'active', 0);

	$("#tb_shop_cd").change(function(){
		send_val = $("#tb_shop_cd").val();
		$("#boshu_shokushu").html("<option>Loading...</option>");
		$("#boshu_shokushu").load("{{ route('legacy.mypage.recruit-category-options') }}",{
			shop_cd:send_val,
			_token:'{{ csrf_token() }}'
		});
	});
});

function deletePic(tg) {
	var conf = confirm("削除してもよろしいですか？\n\r※【この内容で確認する】ボタンをクリックしてから反映されます。");
	if (conf) {
		$("#pic_name"+tg).val("delete");
		$("#gazou"+tg).html('<img src="{{ asset('legacy/system/img/no_photo.gif') }}" alt="No Photo" class="mB10" />');
	}
}
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
{{-- js/item_entry.js con las URL de las rutas y el _token --}}
<script type="text/javascript">
var picExt = picExt2 = '';
var piclastExt = piclastExt2 = '';
var mylastTime = mylastTime2 =0;
var myTime = myTime2 = 0;

$(document).ready(function(){
	new AjaxUpload('#photo', {
		action: '{{ route('legacy.mypage.recruit-upload') }}',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){
				mylastTime = myTime;
				piclastExt = picExt;
				picExt = ext;
				today = new Date();
				myTime = today.getTime();
				this.setData({
					'_token': '{{ csrf_token() }}',
					'ext': picExt,
					'time' : myTime,
					'lastTime' : mylastTime,
					'lastExt' : piclastExt
				});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, ext){
			$("#pic_name1").remove();

			$.get("{{ route('legacy.mypage.recruit-temp-picture') }}?ext=" + picExt + "&t="+myTime, function(data){
				if (data != "ERROR") {
					$("#gazou1").html(data);
					$("#gazou1").append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic(1);">画像解除</a>');
					$("#form1").append("<input type=\"hidden\" name=\"pic_name1\" id=\"pic_name1\" value=\""+myTime+"\" />");
				} else {
					alert('画像サイズが大きすぎます。2MB以下推奨です。');
					$("#gazou1").html('<br><br><br><br><br><br>');
				}
			});
		}
	});

	new AjaxUpload('#photo2', {
		action: '{{ route('legacy.mypage.recruit-upload') }}',

		onSubmit : function(file , ext){

			if (ext && /^(jpg|png|jpeg|gif)$/.test(ext)){
				mylastTime2 = myTime2;
				piclastExt2 = picExt2;
				picExt2 = ext;
				today = new Date();
				myTime2 = today.getTime();
				this.setData({
					'_token': '{{ csrf_token() }}',
					'ext': picExt2,
					'time' : myTime2,
					'lastTime' : mylastTime2,
					'lastExt' : piclastExt2
				});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, ext){
			$("#pic_name2").remove();

			$.get("{{ route('legacy.mypage.recruit-temp-picture') }}?ext=" + picExt2 + "&t="+myTime2, function(data){
				if (data != "ERROR") {
					$("#gazou2").html(data);
					$("#gazou2").append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic(2);">画像解除</a>');
					$("#form1").append("<input type=\"hidden\" name=\"pic_name2\" id=\"pic_name2\" value=\""+myTime2+"\" />");
				} else {
					alert('画像サイズが大きすぎます。2MB以下推奨です。');
					$("#gazou2").html('<br><br><br><br><br><br>');
				}
			});
		}
	});
});
</script>
@endsection

@section('content')
@php
    $isCreate = $mode === 'create';
    $value = fn (string $field) => old($field, $recruit?->{$field});
    $ng = fn (string $field) => $errors->has($field) ? ' class="Ng"' : '';
    $noPhoto = '<img src="' . e(asset('legacy/system/img/no_photo.gif')) . '" alt="No Photo" class="mB10" />';
    $selectedCategory = old('boshu_shokushu', $recruit?->boshu_shokushu);
    $textareas = [
        'shigoto_naiyou' => '仕事内容', 'koyou_keitai' => '雇用形態', 'oubo_shikaku' => '応募資格', 'kinmu_chi' => '勤務地',
        'kinmu_jikan' => '勤務時間', 'kyuyo' => '給与', 'kyuka' => '休日・休暇', 'taigu' => '待遇・福利厚生',
    ];
@endphp
<div class="contents">
<p class="h2Title">■求人情報 {{ $isCreate ? '新規登録' : '修正' }}</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $isCreate ? route('admin.mypage.recruits.store') : route('admin.mypage.recruits.update', ['cd' => $recruit->cd]) }}">
@csrf

<fieldset>
<legend>求人情報</legend>

<table class="tableBox">

	<tr>
		<th><label for="boshu_headline">募集見出し<em>※</em></label></th>
		<td{!! $ng('boshu_headline') !!}>
		<input type="text" name="boshu_headline" id="boshu_headline" class="inputL" value="{{ $value('boshu_headline') }}" />
		@error('boshu_headline')<p class="m0">{{ $message }}</p>@enderror</td>
	</tr>

	<tr>
		<th><label for="name">店舗<em>※</em></label></th>
		<td{!! $ng('tb_shop_cd') !!}>

		@if ($singleShop !== null)
		{{ $singleShop->kaishamei }}
		<input type="hidden" name="tb_shop_cd" value="{{ $singleShop->cd }}">
		@else
		<select name="tb_shop_cd" id="tb_shop_cd">
		<option value="">▼店舗選択</option>
		@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @if ((string) $selectedShopCd === (string) $shop->cd) selected="selected"@endif>{{ $shop->kaishamei }}</option>
		@endforeach
		@if ($showAllShopsOption)
<option value="-1" @if ((string) $selectedShopCd === '-1') selected="selected"@endif>全店舗</option>
		@endif
		</select>
		@endif

		@error('tb_shop_cd')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

	<tr>
		<th><label for="boshu_shokushu">募集職種<em>※</em></label></th>
		<td{!! $ng('boshu_shokushu') !!}>
		<select name="boshu_shokushu" id="boshu_shokushu" class="inputL" />
		<option value="">▼職種選択</option>
		@foreach ($categories as $category)
<option value="{{ $category->cd }}" @if ((string) $selectedCategory === (string) $category->cd) selected="selected"@endif>{{ $category->shokushu }}</option>
		@endforeach
		</select>
		@error('boshu_shokushu')<p class="m0">{{ $message }}</p>@enderror</td>
	</tr>

	@foreach ($textareas as $field => $label)
	<tr>
		<th><label for="{{ $field }}">{{ $label }}</label></th>
		<td>
		<textarea cols="40" rows="4" name="{{ $field }}" id="{{ $field }}" maxlength="400">{{ $value($field) }}</textarea>
		</td>
	</tr>
	@endforeach

	<tr>
		<th>画像</th>
		<td class="textCenter" valign="top">
		@foreach ([1, 2] as $n)
		@php
		    $picName = (string) old('pic_name' . $n, '');
		    $tempUrl = ($picName !== '' && $picName !== 'delete') ? $service->tempImageUrl($picName) : null;
		    $alt = $n === 1 ? '求人情報写真１' : '求人情報写真２';
		@endphp
		<div align="{{ $isCreate ? 'left' : 'center' }}"@if ($isCreate) class="MB10"@endif>
			<div id="gazou{{ $n }}">@if ($tempUrl !== null)<img src="{{ $tempUrl }}" alt="{{ $alt }}" class="mB10" /><a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic({{ $n }});">画像解除</a>@elseif (! $isCreate && $picName !== 'delete' && $recruit->{'pic_flg' . $n} === '1')<img src="{{ $service->imageUrl($recruit->cd, $n) }}" alt="店舗画像" class="mB10" /><input type="hidden" name="pic_flg{{ $n }}" value="1"/><a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic({{ $n }});">画像解除</a>@else{!! $noPhoto !!}@endif</div>
			<p id="{{ $n === 1 ? 'photo' : 'photo2' }}"@if ($isCreate) style="margin-left: 45px;"@endif></p><input type="hidden" name="pic_name{{ $n }}" id="pic_name{{ $n }}" value="{{ $picName }}" />
		@if ($isCreate)
		@include('admin.mypage.partials.image-tooltip')
		@endif
		</div>
		@endforeach
		</td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>会社概要</legend>

<table class="tableBox">

	<tr>
		<th><label for="kaisha_name">名称</label></th>
		<td>
		<input type="text" name="kaisha_name" id="kaisha_name" class="inputL" value="{{ $value('kaisha_name') }}" />
		</td>
	</tr>

	<tr>
		<th><label for="add">所在地</label></th>
		<td>
		<textarea cols="40" rows="4" name="add" id="add" maxlength="400">{{ $value('add') }}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="tel">電話番号</label></th>
		<td>
		<input type="text" name="tel" id="tel" class="inputL" value="{{ $value('tel') }}" style="ime-mode:disabled" />
		</td>
	</tr>

	<tr>
		<th><label for="fax">ファックス番号</label></th>
		<td>
		<input type="text" name="fax" id="fax" class="inputL" value="{{ $value('fax') }}" style="ime-mode:disabled" />
		</td>
	</tr>

	<tr>
		<th><label for="jigyou_naiyou">事業内容</label></th>
		<td>
		<textarea cols="40" rows="4" name="jigyou_naiyou" id="jigyou_naiyou" maxlength="400">{{ $value('jigyou_naiyou') }}</textarea>
		</td>
	</tr>

</table>
</fieldset>

<fieldset>
<legend>応募について</legend>

<table class="tableBox">

	<tr>
		<th><label for="oubo_houhou">応募方法</label></th>
		<td>
		<textarea cols="40" rows="4" name="oubo_houhou" id="oubo_houhou" maxlength="400">{{ $value('oubo_houhou') }}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="mensetsuchi">面接地</label></th>
		<td>
		<textarea cols="40" rows="4" name="mensetsuchi" id="mensetsuchi" maxlength="400">{{ $value('mensetsuchi') }}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="tantou">担当</label></th>
		<td>
		<input type="text" name="tantou" id="tantou" class="inputL" value="{{ $value('tantou') }}" />
		</td>
	</tr>

</table>

</fieldset>

<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $recruit?->cd }}">
<input type="hidden" name="act" id="act" value="up">
<input type="submit" name="send" id="send" class="btn" value="この内容で登録する" />
</div>

</form>

<!-- / .form --></div>

<!-- / .contents --></div>
@if ($categoryAlert)
<script>
alert('募集職種が登録されていません。\n左メニューの求人職種管理より、募集される職種を登録した後に求人情報を登録して下さい。');
</script>
@endif
@endsection

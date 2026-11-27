{{-- 要望・お問い合わせ [入力]: system/contact/index.php (ventana emergente de お問合せ en la cabecera) --}}
@extends('layouts.legacy-popup')

@section('title', '要望・お問い合わせ - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function(){
	// テキストエリアに文字数制限をかける (400文字; BackSpace, flechas y Delete siguen funcionando)
	var textarea = $('#comment');
	textarea.bind("keydown keyup keypress click", function(e){
		var max  = 400
		  , code = e.keyCode;
		return ($(this).val().length < max || (code == 8 || code == 37 || code == 38 || code == 39 || code == 40 || code == 46));
	});
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■要望・お問い合わせ</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

@isset($error)
<div class="mB10" style="padding: 10px; border: 1px dotted #FF0000;">
<span class="attention">{{ $error }}<br /></span></div>
@endisset

<form method="post" name="form1" id="form1" class="form" action="/system/contact/index.php">
@csrf

<fieldset>
<legend>要望・お問い合わせフォーム</legend>
<table class="tableBox">

<tr>
<th><label for="kaishamei">会社名/屋号</label></th>
<td>{{ $data['kaishamei'] }}<input type="hidden" name="kaishamei" id="kaishamei" value="{{ $data['kaishamei'] }}" /></td>
</tr>

<tr>
<th><label for="tb_shop_cd">店舗名</label></th>
<td>
@if ($isMaster)
<select name="tb_shop_cd" id="tb_shop_cd">@foreach ($shops as $cd => $name)<option value="{{ $cd }}"{!! (string) $cd === $data['tb_shop_cd'] ? ' selected="selected"' : '' !!} >{{ $name }}</option>
@endforeach</select>
@else
{{ $shops[(int) $data['tb_shop_cd']] ?? '' }}<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $data['tb_shop_cd'] }}" />
@endif
</td>
</tr>

<tr>
<th><label for="tantou_name">担当者名</label></th>
<td><input type="text" name="tantou_name" id="tantou_name" class="inputL inputSmall" value="{{ $data['tantou_name'] }}" /></td>
</tr>

<tr>
<th><label for="tantou_name_kana">担当者名(カナ)</label></th>
<td><input type="text" name="tantou_name_kana" id="tantou_name_kana" class="inputL inputSmall" value="{{ $data['tantou_name_kana'] }}" /></td>
</tr>

<tr>
<th><label for="type1">お問い合わせ内容</label></th>
<td>
<ul class="textBox">
@foreach (\App\Http\Controllers\Admin\Nucleo\ContactController::TYPES as $i => $type)
<li><input type="radio" name="type" id="type{{ $i + 1 }}" class="checkBtn" value="{{ $type }}"{!! $data['type'] === $type ? ' checked="checked"' : '' !!} />
<label for="type{{ $i + 1 }}">{{ $type }}</label></li>
@endforeach
</ul>
</td>
</tr>

<tr>
<th><label for="comment">本文<em>※</em><br />（最大400文字）</label></th>
<td><textarea name="comment" id="comment" rows="4" cols="40" class="writeArea">{{ $data['comment'] }}</textarea></td>
</tr>

</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="domain" value="{{ $data['domain'] }}">
<input type="hidden" name="act" value="up">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="送信内容の確認">
</div>

</form>

@include('admin.nucleo.contact._close')

<!-- / .contents --></div>
@endsection

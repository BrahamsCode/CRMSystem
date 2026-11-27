{{--
    マイコレHOMEに追加するコメント (solo en mobile_mycolle_edit.php). POST normal con confirm (submitChk).
    Envía: home_comment[0], home_comment[<shop_cd>], mypage_config[comment_flg][-1], mypage_config[comment_flg][<shop_cd>], type=home_comment.
--}}
<div id="c6_user_top" class="contents">
<p class="h2Title">マイコレHOMEに追加するコメント</p>

<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.mypage.app-images.home-comment') }}" onsubmit="return submitChk()">
@csrf
<input type="hidden" name="mypage_config[comment_flg][-1]" value="1">
<fieldset>
<table class="tableBox">

<tr>
	<th>全店舗設定</th>
	<th>店舗別設定</th>
</tr>

<tr>
	<td style="vertical-align:top;">
		<div>【全店舗用】</div>
		<textarea name="home_comment[0]" id="home_comment" class="inputL" cols="100" rows="10">{{ $homeComments[0] ?? '' }}</textarea>
	</td>
	<td style="vertical-align:top;">
		@foreach ($shops as $shop)
			<div style="display:inline-block;margin-bottom: 5px">
				<div>【{{ $shop['kaishamei'] }}】</div>
				{{-- El legacy quita el \" solo en los textos por tienda --}}
				<textarea name="home_comment[{{ $shop['cd'] }}]" id="home_comment_{{ $shop['cd'] }}" class="inputL" cols="100" rows="10">{{ isset($homeComments[$shop['cd']]) ? str_replace('\"', '"', $homeComments[$shop['cd']]) : '' }}</textarea>
				<br>
				<label for="mypage_config_comment_flg_{{ $shop['cd'] }}">
					<input type="checkbox" name="mypage_config[comment_flg][{{ $shop['cd'] }}]" id="mypage_config_comment_flg_{{ $shop['cd'] }}" value="1" @if (($mypageConfig['comment_flg'][$shop['cd']] ?? null) == 1) checked="checked" @endif> 全店舗コメントを利用する
				</label>
			</div>
		@endforeach
	</td>
</tr>

</table>
</fieldset>

<div class="btnBox">
<input type="submit" value="この内容で変更する" />
<input type="hidden" name="type" id="type" value="home_comment" />
</div>

</form>

{{-- Formulario de recarga por tienda del legacy (POST a sí mismo). En esta pantalla no hay #shop_cd que lo dispare: código muerto conservado. --}}
<form name="reload_form" id="reload_form" method="post" action="{{ route('admin.mypage.app-images.edit', ['section' => $section]) }}#c6_user_top">
@csrf
<input type="hidden" name="shop_cd" id="reload_shop_cd" value="" />
</form>

<!-- / .contents --></div>

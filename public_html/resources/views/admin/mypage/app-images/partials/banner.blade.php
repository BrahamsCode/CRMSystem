{{--
    マイページアプリのバナー設定 (mobile_mycolle_edit_banner.php). Se guarda por AJAX (scripts/banner).
    Cada hueco: 自由選択 (imagen propia, .bannerCell1) o ﾃﾞﾌｫﾙﾄ選択 (imagen de hp/img/banner/, .bannerCell2,
    elegida con el plugin ImageSelect, que usa el texto de cada <option> como src).
--}}
<div class="contents">
	<p class="h2Title">マイページアプリのバナー画像設定</p>

	<form name="uploadBannerForm">
		<input type="hidden" name="mypage_config[banner][-1]" value="1">
		<div class="bannerList">
		@foreach ($slots as $slot)
			@php($isDefault = (int) $slot['cellStatus'] === 2)
			@php($filled = $slot['imageUrl'] !== null || $isDefault)
			<div class="bannerDisplay">
				<ul class="bannerStatusList">
					<li><label><input type="radio" value="1" name="mypage_config[cell_status][{{ $slot['index'] }}]" @if (! $isDefault) checked='checked' @endif>自由選択</label></li>
					<li><label><input type="radio" value="2" name="mypage_config[cell_status][{{ $slot['index'] }}]" @if ($isDefault) checked='checked' @endif>ﾃﾞﾌｫﾙﾄ選択</label></li>
				</ul>
				<div class="bannerCell1" @if ($isDefault) style='display:none;' @endif>
					<div class="bannerFrame">@if ($slot['imageUrl'] !== null)<img src="{{ $slot['imageUrl'] }}" style="width:100%;">@endif @if ($filled)<input type="hidden" name="pic[{{ $slot['index'] }}]" value="def">@endif</div>
					<div><input type="file" name="banner{{ $slot['index'] }}" class="addBanner" /></div>
				</div>
				<div class="bannerCell2" @if ($isDefault) style='display:block;' @endif>
					<select name="mypage_config_cell_img_{{ $slot['index'] }}">
						@foreach ($bannerCellList as $cellImage)
							<option value="{{ $cellImage }}" @if ($cellImage === $slot['cellImg']) selected @endif>{{ url('hp/img/banner/' . $cellImage) }}</option>
						@endforeach
					</select>
				</div>

				@if (count($shops))
					@include('admin.mypage.app-images.partials.shop-select', ['checkKey' => 'banner'])
				@endif
				@if ($filled)
					<div class="url_param"><input type="text" name="target_url[{{ $slot['index'] }}]" value="{{ $slot['linkUrl'] }}" placeholder="http, mailto, telから入力" style="width:96%;"></div>
					<div class="del_pic_wrap right"><input type="button" class="deleteBanner" target="{{ $slot['index'] }}" value="画像を削除する" style="font-size:80%;color:red; margin-top:5px;"></div>
				@endif
			</div>
		@endforeach
		</div>

		<div class="center MB10">
			<input type="button" id="bannerUploadBtn" class="btnStyle FS20 WD140" value="アップロード" />
			<input type="hidden" name="type" value="sendBannerData" />
		</div>
	</form>

	<div class="MB10">
		※ アップロードされた画像は、横幅800px基準でリサイズされ、中央基準に16:5の比率でトリミング(横800px 縦250px)されます。
	</div>
	<div class="MB10">
		※ デフォルトの選択画像から「マイページからコチラから」を選んだ場合は、URLの設定に関係なくマイページへのリンクになります。<br>
		※ デフォルトの選択画像から「キャッシュバッククーポン」を選んだ場合は、URLの設定に関係なく該当ページへのリンクになります。
	</div>

</div>
<div id="uploadBannerDialog"></div>

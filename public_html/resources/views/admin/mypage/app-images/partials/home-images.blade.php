{{--
    HOME メインイメージ ($home = 'main', mobile_mycolle_edit_main.php) y サムネイル
    ($home = 'thumbnail', mobile_mycolle_edit_thumbnail.php). Mismo HTML con otros nombres.
    Hueco 0 = 全店舗用 (columna izquierda); huecos 1..n = $shops en la columna 各店舗.
    El <input type="hidden" name="pic[...]"> de una imagen existente usa el shop_cd (0 en 全店舗用),
    pero el <input type="file" name="image<i>"> usa el índice i, como el legacy.
--}}
@php
    $isMain = $home === 'main';
    $flgKey = $isMain ? 'home_image_flg' : 'home_thum_flg';
    $inputClass = $isMain ? 'addMainImage' : 'addThumbnailImage';
    $deleteClass = $isMain ? 'deleteMainPic' : 'deleteThumbnailPic';
    $lastIndex = count($slots) - 1;
@endphp
<div class="contents">
	@if ($isMain)
		<p class="h2Title">マイページアプリのHOMEに表示されるメインイメージ</p>
	@else
		<p class="h2Title">マイページアプリのHOMEに表示されるサムネイル画像</p>
	@endif

	<form name="{{ $isMain ? 'uploadMainPicForm' : 'uploadThumbnailPicForm' }}">
		<input type="hidden" name="mypage_config[{{ $flgKey }}][-1]" value="1">
		<div class="imageList">
		@foreach ($slots as $slot)
			@php($picCd = $slot['shop'] !== null ? $slot['shop']['cd'] : 0)
			@if ((int) $slot['index'] === 0)
				<table class="tableBox MB10">
					<tr>
						<th>全店舗用</th>
						<th>各店舗</th>
					</tr>
					<tr>
						<td style="vertical-align: top;     padding-right: 5px;">
			@elseif ((int) $slot['index'] === 1)
					</td><td style="vertical-align: top;">
			@endif
				<div class="imageDisplay">
					@if ($slot['index'] > 0)
						@if ($slot['shop'] !== null)
							<div>【{{ $slot['shop']['kaishamei'] }}】</div>
						@endif
					@else
						<div>【全店舗用】</div>
					@endif
					<div class="imageFrame" @if (! $isMain) style="min-height: 190px;" @endif>@if ($slot['imageUrl'] !== null)<img src="{{ $slot['imageUrl'] }}" style="width:100%;"><input type="hidden" name="pic[{{ $picCd }}]" value="def">@endif</div>
					<div><input type="file" name="image{{ $slot['index'] }}" class="{{ $inputClass }}" /></div>
					@if ($slot['index'] > 0)
						<label for="mypage_config_{{ $flgKey }}_{{ $picCd }}">
							<input type="checkbox" name="mypage_config[{{ $flgKey }}][{{ $picCd }}]" id="mypage_config_{{ $flgKey }}_{{ $picCd }}" value="1" @if ($slot['useAllShopsImage']) checked="checked" @endif> 全店舗画像を利用する
						</label>
					@endif
					@if ($slot['imageUrl'] !== null)
						<div class="del_pic_wrap right"><input type="button" class="{{ $deleteClass }}" target="{{ $picCd }}" value="画像を削除する" style="font-size:80%;color:red; margin-top:5px;"></div>
					@endif
				</div>
			@if ((int) $slot['index'] === $lastIndex)
					</td></tr>
					</table>
			@endif
		@endforeach
		</div>

		<div class="center MB10">
			@if ($isMain)
				<input type="button" id="picMainUploadBtn" class="btnStyle FS20 WD140" value="アップロード"/>
				<input type="hidden" name="type" value="sendMainData" />
				<input type="hidden" name="dir" value="apli_home_image" />
			@else
				<input type="button" id="picThumbnailUploadBtn" class="btnStyle FS20 WD140" value="アップロード" />
				<input type="hidden" name="type" value="sendThumbnailData" />
				<input type="hidden" name="dir" value="apli_home_thumbnail" />
			@endif
		</div>
	</form>

	<div class="MB10">
		@if ($isMain)
			アップロードされた画像は、横幅800px基準でリサイズされ、中央基準に4:3の比率でトリミング(横800px 縦600px)されます。
		@else
			アップロードされた画像は、横幅128px基準でリサイズされ、中央基準に4:3の比率でトリミング(横128px 縦128px)されます。
		@endif
	</div>

</div>

<div id="uploadDialog"></div>

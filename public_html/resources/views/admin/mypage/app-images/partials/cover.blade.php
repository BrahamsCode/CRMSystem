{{-- マイページアプリのカバーフロー設定 (mobile_mycolle_edit_coverflow.php). Se guarda por AJAX (scripts/cover). --}}
<div class="contents">
	<p class="h2Title">マイページアプリのカバーフロー画像設定</p>


	<form name="uploadPicForm">
		<input type="hidden" name="mypage_config[cover][-1]" value="1">
		<div class="imageList">
		@foreach ($slots as $slot)
			@php($filled = $slot['imageUrl'] !== null)
			<div class="imageDisplay">
				<div class="imageFrame">@if ($filled)<img src="{{ $slot['imageUrl'] }}" style="width:100%;"><input type="hidden" name="pic[]" value="def">@endif</div>
				<div><input type="file" name="image{{ $slot['index'] }}" class="addImage" /></div>
				@if (count($shops))
					@include('admin.mypage.app-images.partials.shop-select', ['checkKey' => 'cover'])
				@endif
				@if ($filled)
					<div class="url_param"><input type="text" name="target_url[]" value="{{ $slot['linkUrl'] }}" placeholder="http, mailto, telから入力" style="width:96%;"></div>
					<div class="del_pic_wrap right"><input type="button" class="deletePic" target="{{ $slot['index'] }}" value="画像を削除する" style="font-size:80%;color:red; margin-top:5px;"></div>
				@endif
			</div>
		@endforeach
		</div>

		<div class="center MB10">
			<input type="button" id="picUploadBtn" class="btnStyle FS20 WD140" value="アップロード" />
			<input type="hidden" name="type" value="sendData" />
		</div>
	</form>

	<div class="MB10">
		アップロードされた画像は、横幅800px基準でリサイズされ、中央基準に4:3の比率でトリミング(横800px 縦600px)されます。
	</div>

</div>

<div id="uploadDialog"></div>

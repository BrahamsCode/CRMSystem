{{--
    マイコレのトップに顧客グループの表記を (solo en mobile_mycolle_edit.php).
    POST: mode=apli_customer_group_disp_upload, apli_customer_group_disp=1|0 (contents_master.int_data).
--}}
<div class="contents">
	<p class="h2Title">マイコレのトップに顧客グループの表記を</p>
	<form action="{{ route('admin.mypage.app-images.customer-group') }}" name="uploadFieldForm" method="POST">
		@csrf
		<input type="hidden" name="mode" value="apli_customer_group_disp_upload">
		<div>
			<div class="fieldRadio">
				<input type="radio" name="apli_customer_group_disp" value="1" id="field_1" @if ($customerGroupDisplay) checked='checked' @endif>
				<label for="field_1">表示する</label>
				<input type="radio" name="apli_customer_group_disp" value="0" id="field_0" @if (! $customerGroupDisplay) checked='checked' @endif>
				<label for="field_0">表示しない</label>
			</div>
		</div>

		<div class="center MB10">
			<input type="submit" value="この内容で変更する">
		</div>
	</form>
</div>

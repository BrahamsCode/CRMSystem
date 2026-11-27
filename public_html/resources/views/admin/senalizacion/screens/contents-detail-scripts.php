<script type="text/javascript">

<?php if ( !isset( $_detail_data['img_path'] ) || empty( $_detail_data['img_path'] ) ) { ?>
$(function() {
	$('#input_form').submit(function(){

		upfile = $('#input_form input[name=upfile]').val();
		
		if (upfile != '') {
			$(this).submit();
		} else {
			alert('ファイルを選択してください。');
		}
		return false;
	});
});
<?php } ?>
</script>

{{-- ショートカット管理 (登録できる機能一覧): system/category0/config/bookmark_list.php --}}
@extends('layouts.legacy')

@section('title', 'ショートカット管理 - ' . $siteName)

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);

	$(".submitBtn").click(function(){
		targetFrom = $(this).attr("frm");
		mess = $(this).attr("mess");

		jConfirm(mess,'確認', function(r){
			if(r == true){
				$("#"+targetFrom).submit();
			}
		});
	});
});
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■登録できる機能一覧</p>

		<div class="right"><a href="bookmark.php">ショートカット登録済み一覧へ</a></div>

		<div>
			画面上部に配置する、各機能へのショートカットを選択して登録します。<br />
			登録したい機能にチェックを入れて更新ボタンを押してください。
		</div>

		@if ($groups)
		<form action="" method="post" name="form1" id="form1">
			<input type="button" class="submitBtn btnStyleM" frm="form1" mess="更新してよろしいですか？" value="更新" />
			@foreach ($groups as $group)
			<table class="tableBox">
				<tr><td colspan="3" style="background: #555555; color: #FFFFFF;">{{ $group['name'] }}</td></tr>
				@foreach ($group['children'] as $child)
				<tr><td colspan="3" style="background: #F5F5F5;">{{ $child['name'] }}</td></tr>
				@foreach ($child['rows'] as $row)
				<tr>
					<td style="width: 20px;text-align: center;"><input type="checkbox" name="bookmark_{{ $row->cd }}" value="1"{!! (int) $row->bookmark_flg === 1 ? ' checked="checked"' : '' !!} /></td>
					<td style="width: 15em">{{ $row->title }}</td>
					<td>{{ $row->naiyou }}</td>
				</tr>
				@endforeach
				@endforeach
			</table>
			@endforeach

			<input type="button" class="submitBtn btnStyleM" frm="form1" mess="更新してよろしいですか？" value="更新" />
			<input type="hidden" name="act_flg" value="koushin" />
		</form>
		@else
		登録されているリストが存在しません。
		@endif

		<form action="" method="post" name="resetForm" id="resetForm" class="right">
			<input type="button" class="submitBtn btnStyleS" frm="resetForm" mess="初期化してよろしいですか？" value="設定を初期化する" />
			<input type="hidden" name="act_flg" value="reset" />
		</form>
	<!-- / .contents --></div>

	<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>
@endsection

{{--
	ポイント一括付与 — legacy: system/category3/point/csv_grant_points.php
	Variables: $posted (hubo envío), $successIds, $errorIds, $error
	El `sessId` del legacy lo sustituye el token CSRF; el resto del HTML y del JS es el mismo.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

{{-- El legacy imprimía `<?=$title?>一覧` con $title sin definir --}}
@section('title', '一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac13").accordion('option', 'active', 0);
	window.scroll(0,0);

	$.alerts.overlayOpacity = 0.5;

	// フォーム送信ボタン
	$('#sendBtn').click(function(){
		try {
			if ( $('#csvFile').get(0).files.length == 0  ) {
				jAlert("ファイルを選択して下さい。", "警告");
				return false;
			}
		} catch (ex) {}

		jConfirm("CSVファイルをアップロードして、ポイント一括付与処理を実行します\nよろしいですか？", "確認",
			function(ret){
				if (ret) {
					$('#form1').submit();
				} else {
					return false;
				}
			}
		);
	});
});
</script>
@endsection

@section('content')
<div class="contents">

	<p class="h2Title">■ポイント一括付与</p>

	<a href="{{ route('admin.promocion.points.grant-csv', ['mode' => 'base_csv']) }}" class="btnStyleM">ひな形ダウンロード</a><br><br>

	<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.points.grant-csv.store') }}" enctype="multipart/form-data">
		@csrf

		<table class="tableBox">
			<tr>
				<th>CSVインポート</th>
				<td>
					<div>
						<p>※ポイント付与リストCSVを選択して下さい</p>
						<input type="file" name="csvFile" id="csvFile" />
						<input type="hidden" name="act" value="csvUpload" />
					</div>
				</td>
			</tr>
		</table>

		<div style="margin-top:10px;text-align:center;">
			<input type="button" id="sendBtn" value="CSVでポイントを一括付与を実行する" class="btn btnStyleM" />
		</div>

	</form>

@if ($posted)
	<div style="margin:10px auto;text-align:center;">
	@if (count($successIds) > 0)
		<div style="padding:10px;border:2px solid #0000FF;color:#0000FF;background-color:#FFFFFF;"><p>下記の顧客ID（{{ count($successIds) }}件）にポイント付与しました。</p>
		<textarea style="height:200px;width:90%;resize:none;" readonly="readonly">{{ implode(',', $successIds) }}</textarea></div>
	@endif
	@if (count($errorIds) > 0)
		<div style="padding:10px;border:2px solid #FFCA28;color:#FFCA28;background-color:#FFFFFF;"><p>下記の顧客ID（{{ count($errorIds) }}件）に ポイント付与できませんでした。</p>
		<textarea style="height:200px;width:90%;resize:none;" readonly="readonly">{{ implode(',', $errorIds) }}</textarea></div>
	@endif
	@if ($error)
		<span style="display:block;padding:5px;border:2px solid #FF0000;color:#FF0000;background-color:#FFFFFF;">{{ $error }}</span>
	@endif
	</div>
@endif

<!-- / .contents --></div>
@endsection

{{-- Legacy: r/check.php (y alias reserve/check.php): 登録済み予約一覧 del socio de la sesión, con 取消 (js/check.js).
     Variables: $now (epoch, anti caché), $sess (id de sesión), $reserves (reservas vigentes: cd, shop_cd, shop_name, shop_tel,
     staff_name ya resuelto para フリー予約, menuDisplay, dispReserveTime), $backQuery (consulta para volver a index.php). --}}
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
		<meta name="format-detection" content="telephone=no">
		<title>Reserve</title>

		<link href="/common/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="/common/bootstrap-3.3.6-dist/css/bootstrap-dialog.min.css" rel="stylesheet">
		<link href="/common/icheck/skins/all.css" rel="stylesheet">
		<link href="css/main.css" rel="stylesheet">
		<link href="css/loading.css" rel="stylesheet">

		<script src="/common/jquery/jquery-1.11.3.min.js"></script>
		<script src="/common/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
		<script src="/common/bootstrap-3.3.6-dist/js/bootstrap-dialog.min.js"></script>
		<script src="/r/js/common.js"></script>
		<script src="/r/js/check.js?t={{ $now }}"></script>
	</head>
	<body>
		<input type="hidden" name="sess" id="sess" value="{{ $sess }}" />
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<h1>登録済み予約一覧</h1>
					@if ($reserves)
					<div class=" text-center" style="color:#ee0000;font-size:120%;font-weight:bold;padding:10px;">予約登録変更については、一度予約登録を取消のうえ予約登録しなおすか、お電話にてお問い合わせください。</div>
						@foreach ($reserves as $reserveData)
						<div class="col-xs-12">
							<div class="panel">
								<div class="panel-header">■<span class="reserveTime">{{ $reserveData['dispReserveTime'] }}</span></div>
								<div class="panel-body">
									<ul>
										<li><label>店舗</label>：{{ $reserveData['shop_name'] }}</li>
										<li><label>店舗TEL</label>：<a href="tel:{{ $reserveData['shop_tel'] }}">{{ $reserveData['shop_tel'] }}</a></li>
										<li><label>担当</label>：{{ $reserveData['staff_name'] }}</li>
										<li><label>メニュー</label>：{{ $reserveData['menuDisplay'] }}</li>
									</ul>
									<div class="text-right"><button class="btnCancelReserve btn btn-danger" scd="{{ $reserveData['shop_cd'] }}" cd="{{ $reserveData['cd'] }}">取消処理を行う</button></div>
								</div>
							</div>
						</div>
						@endforeach
					@else
					<div class="col-xs-12 text-center">予約がありません</div>
					@endif
				</div>

				<div class="col-xs-12">
					<a class="btn btn-primary" href="./index.php?{{ $backQuery }}">　戻る　</a>
				</div>

			</div>
		</div>

		<div class="loading_frame" style="display:none;"></div>
		<div class="windows8_wrap" style="display:none;"></div>
	</body>
</html>

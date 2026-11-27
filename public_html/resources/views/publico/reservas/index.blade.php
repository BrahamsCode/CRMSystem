{{-- Legacy: r/index.php (y alias reserve/index.php). Página propia (Bootstrap 3), sin el layout del sistema.
     Variables: $now (epoch, anti caché), $startDate/$endDate ('Y-m-d' de la semana mostrada), $sess (id de sesión),
     $shops ([cd => [cd, name, tel]]), $defaults (shop_cd, staff_cd, menu_cd pedidos), $koumoku (tb_koumoku del formulario
     por campo), $freeFields ([N => fila de tb_koumoku] activos), $mobile (sesión マイページ: user_cd, user_id, user_name,
     email1, tel), $customerReserves (reservas vigentes del socio), $token, $iphoneApp (bool).
     CSS relativos a la carpeta (r/ o reserve/, mismos archivos); JS siempre los de /r/ (contrato CRM: menu_cd = cd). --}}
@php
    $shows = fn (string $field) => (string) ($koumoku[$field]['koumoku_disp'] ?? '') === '1';
    $mark = fn (string $field) => (string) ($koumoku[$field]['koumoku_check'] ?? '') === '1' ? '<span>※</span>' : '　';
@endphp
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
		<title>Reserve</title>

		<link href="/common/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="/common/bootstrap-3.3.6-dist/css/bootstrap-dialog.min.css" rel="stylesheet">
		<link href="/common/icheck/skins/all.css" rel="stylesheet">
		<link href="css/main.css?t={{ $now }}" rel="stylesheet">
		<link href="css/loading.css?t={{ $now }}" rel="stylesheet">

		<script src="/common/jquery/jquery-1.11.3.min.js"></script>
		<script src="/common/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
		<script src="/common/bootstrap-3.3.6-dist/js/bootstrap-dialog.min.js"></script>
		<script src="/common/icheck/icheck.min.js"></script>
		<script src="/r/js/common.js"></script>
		<script src="/r/js/main.js?t={{ $now }}"></script>
		<script>
		var defaults = {!! json_encode($defaults, JSON_HEX_TAG | JSON_HEX_AMP) !!};
		var shopArray = {!! json_encode($shops, JSON_HEX_TAG | JSON_HEX_AMP) !!};
		</script>
		@if ($iphoneApp)
		<style>
		#reserveBtnArea {
			bottom : 50px;
		}
		.modal-footer {
			margin-bottom: 50px;
		}
		</style>
		@endif
	</head>
	<body>
		<div class="container">
			@if (! empty($mobile['user_cd']) && $customerReserves)
				<div class="row"><div class="col-xs-12 text-center" style="margin-top:10px;"><div style="background-color:#ffffff;border: 2px solid #a31616;padding:10px;"><p style="color:#ee0000;margin:0;">登録済み予約があります</p><p>予約日付：{{ $customerReserves[0]['reservation_date'] }}@if (count($customerReserves) > 1)、他{{ count($customerReserves) - 1 }}件@endif</p><button type="button" id="btnMyReserves" class="btn btn-primary">予約を確認する</button></div></div></div>
			@endif
			<div class="row">
				<div class="col-xs-12">
					<h1>予約フォーム</h1>
				</div>

				<form name="reserve_form" id="reserve_form" onClick="return false;">

					<input type="hidden" name="start_date" id="start_date" value="{{ $startDate }}" />
					<input type="hidden" name="end_date" id="end_date" value="{{ $endDate }}" />
					<input type="hidden" name="sess" id="sess" value="{{ $sess }}" />

					<div class="col-xs-12">
						<h2><span class="glyphicon glyphicon-home" aria-hidden="true"></span> 店舗選択</h2>
						<div id="selectShopAnnotation" class="annotation"></div>
						<div class="form-group" id="shop_choice">
							<ul class="reserveList">
								@foreach ($shops as $shop)<li><div class="radio"><input type="radio" name="shop_cd" id="shop_cd_{{ $shop['cd'] }}" value="{{ $shop['cd'] }}"><label for="shop_cd_{{ $shop['cd'] }}">{{ $shop['name'] }}</label></div></li>@endforeach
							</ul>
						</div>
					</div>

					<div id="errorMsgArea" class="col-xs-12" style="display:none;">
						<div style="background-color:#ffffff;border: 2px solid #a31616;padding:10px;text-align:center;font-size:150%;">
							<p id="errorMsg" style="color:#ee0000;margin:0;"></p>
						</div>
					</div>

					<div id="staffArea" class="col-xs-12">
						<h2><span class="glyphicon glyphicon-user" aria-hidden="true"></span> スタッフ選択</h2>
						<div id="selectStaffAnnotation" class="annotation"></div>
						<div style="padding:5px;min-height:26px;">
							<div id="reserveStatusDisp" style="color:#ff5d00;display:none;font-size:120%;font-weight:bold;">現在、指名なしの状態です。</div>
							<button type="button" id="freeReserveBtn" class="btn btn-info" style="display:none;">指名無しで予約する</button>
						</div>
						<div class="form-group" id="staff_choice">
						</div>
					</div>

					<div id="menuArea" class="col-xs-12">
						<h2><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> メニュー選択</h2>
						<div id="selectMenuAnnotation" class="annotation"></div>
						<div class="form-group" id="menu_choice">
						</div>
					</div>

				</form>

				<div id="scheduleResult" class="col-xs-12 mb80">
				</div>
			</div>
		</div>
		<div id="reserveBtnArea">
			<button class="btn btn-success form-control input-lg" id="reserveBtn" type="submit"><strong>予約内容の確認</strong></button>
		</div>

		<div id="confirmDialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none;">
			<div>
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="myModalLabel">予約確認・顧客情報入力</h4>
					</div>
					<div class="modal-body">
						<div id="customerFormAnnotation" class="annotation"></div>
						<div id="reserveDetails"></div>

						<h2 class="text-center">予約者情報を入力してください。</h2>
						<div id="customerData">
							<form id="customerForm">
							@if (isset($mobile['user_id']))

								<div class="row">
									<div class="col-sm-2"><span>※</span>お名前</div>
									<div class="col-sm-10">
										<p>{{ $mobile['user_name'] ?? '' }}　様</p>
										<input type="hidden" name="name" value="{{ $mobile['user_name'] ?? '' }}" />
									</div>
								</div>

								@if ($shows('mail'))
								<div class="row">
									<div class="col-sm-2">{!! $mark('mail') !!}メールアドレス</div>
									<div class="col-sm-10">
									@if (! empty($mobile['email1']))
										<p>{{ $mobile['email1'] }}</p>
										<input type="hidden" name="mail" value="{{ $mobile['email1'] }}" />
									@else
										<input class="form-control" type="text" name="mail" maxlength="50" value="" style="margin: 0 0 10px;" />
									@endif
									</div>
								</div>
								@endif

								@if ($shows('tel'))
								<div class="row">
									<div class="col-sm-2">{!! $mark('tel') !!}電話番号</div>
									<div class="col-sm-10">
									@if (! empty($mobile['tel']))
										<p>{{ $mobile['tel'] }}</p>
										<input type="hidden" name="tel" value="{{ $mobile['tel'] }}" />
									@else
										<input class="form-control" type="tel" name="tel" size="15" maxlength="15" value="" style="margin: 0 0 10px;" />
									@endif
									</div>
								</div>
								@endif

							@else

								<div class="row">
									<div class="col-sm-2"><span>※</span>お名前</div>
									<div class="col-sm-10">
										<input class="form-control" type="text" name="name" id="name" maxlength="50" value="" style="margin: 0 0 10px;" />
									</div>
								</div>

								@if ($shows('name_kana'))
								<div class="row">
									<div class="col-sm-2">{!! $mark('name_kana') !!}お名前（カナ）</div>
									<div class="col-sm-10">
										<input class="form-control" type="text" name="name_kana" id="name_kana" maxlength="50" value="" style="margin: 0 0 10px;" />
									</div>
								</div>
								@endif

								@if ($shows('mail'))
								<div class="row">
									<div class="col-sm-2">{!! $mark('mail') !!}メールアドレス</div>
									<div class="col-sm-10">
										<input class="form-control" type="text" name="mail" id="mail" maxlength="50" value="" style="margin: 0 0 10px;" />
									</div>
								</div>
								@endif

								@if ($shows('tel'))
								<div class="row">
									<div class="col-sm-2">{!! $mark('tel') !!}電話番号</div>
									<div class="col-sm-10">
										<input class="form-control" type="tel" name="tel" id="tel" size="15" maxlength="15" value="" style="margin: 0 0 10px;" />
									</div>
								</div>
								@endif

							@endif

								{{-- フリー項目 --}}
								@foreach ($freeFields as $n => $field)
								<div class="row">
									<div class="col-sm-2">{!! (string) $field['koumoku_check'] === '1' ? '<span>※</span>' : '　' !!}{{ $field['tb_koumoku_name'] }}</div>
									<div class="col-sm-10">
										<input class="form-control" type="text" name="free_koumoku[{{ $n }}]" id="free_koumoku_{{ $n }}" maxlength="50" value="" style="margin: 0 0 10px;" />
									</div>
								</div>
								@endforeach

								<input type="hidden" id="last_shopCd" value="" />
								<input type="hidden" name="customer_cd" id="customer_cd" value="{{ $mobile['user_cd'] ?? '' }}" />
								<input type="hidden" name="token" value="{{ $token }}">
							</form>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="btnSaveOk" class="btn btn-primary">予約する</button>
						<button type="button" id="btnClose" class="btn btn-default">戻る</button>
					</div>
				</div>
			</div>
		</div>

		<div class="loading_frame" style="display:none;"></div>
		<div class="windows8_wrap" style="display:none;"></div>
	</body>


</html>

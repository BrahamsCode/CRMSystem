{{--
    Legacy: system/category9/yoyaku/related_customer.php (顧客関連づけ: liga una reserva de no socio a un socio existente).
    Variables:
      $yoyakuCd     int       tb_yoyaku.cd
      $reservation  ?object   reserva (cd, name, name_kana, moushikomi_date) o null si no existe
      $siteName     string    $site_name
    El buscador llama a ajax_search_kaiin.php y el 確定 a ajax_related_customer.php (URL legacy, responde 1).
--}}
@extends('layouts.legacy-popup')

@section('title', '顧客関連づけ - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />

<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

.yoyakuShousai {
	display: inline-block;
}

.yoyakuShousai dt {
	display: inline-block;
}

.yoyakuShousai dd {
	display: inline-block;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>
<script type="text/javascript">

var preSearchWord = "";
var yoyakuCd = {{ $yoyakuCd }};

function setMember(cd, name){
	if(cd=="") return false;

	jConfirm( "この内容で登録してもよろしいですか？\n\n登録しない場合は['キャンセル']ボタンを押して下さい", "登録", function(ret){
		if (ret) {
			$.ajax({
				type: "POST",
				url: {{ \Illuminate\Support\Js::from(route('legacy.reservas.related-customer.store')) }},
				data: "yoyaku_cd="+yoyakuCd+"&customer_cd="+cd,

				success: function(data){
					if(data == 1){
						window.parent.location.reload(true);
					}
				}
			});
		}else{
			return false;
		}
	});

}


$(document).ready(function() {

	$("#easySearchWord").bind("keyup", function(e){
		if($("#easySearchWord").val() == preSearchWord) return false;
		preSearchWord = $("#easySearchWord").val();

		$("#result").html('<p style="text-align:center;color:red;">検索中</p>');
		var send_val = $("#easySearchWord").val();

		$.ajax({
			type: "POST",
			url: {{ \Illuminate\Support\Js::from(route('legacy.reservas.options.members')) }},
			data: "w="+encodeURIComponent(send_val),

			success: function(data){
				$("#result").html(data);
			}
		});
	});

});


</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

<div>
@if ($reservation)
<div><span style="font-size: 18px; font-weight: bold;">{{ $reservation->moushikomi_date }}</span>に予約の<span style="font-size: 18px; font-weight: bold;">{{ $reservation->name }}</span>@if ($reservation->name_kana)({{ $reservation->name_kana }})@endif様 に対して、既存会員との紐づけ処理を実行しようとしています。</div>
@endif
</div>

<div>
<input type="text" name="easySearchWord" id="easySearchWord" class="inputL" style="width: 98%; font-size: 20px;" /><br />
<small>※キーワードは ID 管理番号 名前 カナ 電話番号 メールアドレスから検索されます。</small>
</div>

<div id="result"></div>

<!-- / .contents --></div>
@endsection

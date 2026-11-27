{{--
    ペーパーサイネージ 一覧 (legacy: system/category7/card_signage/list.php).

    Contrato de datos:
      $cards     LengthAwarePaginator<PaperCard>  tb_url_list con disp_flg = '1' ORDER BY cd DESC; perPage = $kensu
                 (cd, id, title, hyouji_flg, url; url ya guardada con entidades → se imprime con {!! !!})
      $kensu     int     filas por página (10 por defecto)
      $host      string  host del servidor ($_SERVER['SERVER_NAME'] del legacy) para http://<host>/ptag/<id>
      $siteName  string  (núcleo) nombre del sitio

    Rutas:
      GET|POST admin.terminales.paper-cards.index      (form_search; ?qrCodeDownloadData=&fileName= descarga el QR)
      GET      admin.terminales.paper-cards.create
      POST     admin.terminales.paper-cards.edit       (ficha 詳細 cargada por AJAX en #toolTip)
      GET      admin.terminales.paper-cards.aggregate  (?cd=)
      GET      admin.terminales.paper-cards.csv
      GET      admin.terminales.paper-cards.qr         (?d=: JPEG del QR, en lugar de category3/mail_mag/qr_img.php)
      POST     legacy.terminales.paper-card-control    (/system/category7/card_signage/control.php)
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('body_class', 'main_terminal_master')

@section('title', 'ペーパーサイネージ - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});

//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

var paperCardEditUrl = "{{ route('admin.terminales.paper-cards.edit') }}";
var paperCardControlUrl = "{{ route('legacy.terminales.paper-card-control') }}";
var paperCardQrUrl = "{{ route('admin.terminales.paper-cards.qr') }}";
var paperCardListUrl = "{{ route('admin.terminales.paper-cards.index') }}";



function tooltipIni () {

	$("#toolTip").dialog({
		autoOpen: false,
		open: function(event, ui){
		},
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: 'Infomation',
		width: 600,
		height: 350,
		buttons: {
			'閉じる': function() {
				$(this).dialog('close');
			}
		}
	});

	$(".pop").click(function(e){
		tooltipAct(this, e);
	});
}


function tooltipAct (target, event) {

	send_cd = $(target).attr("cd");


	$("#toolTip").load(paperCardEditUrl,{
		cd:send_cd,
		ePageX:event.pageX,
		ePageY:event.pageY
	}, function() {
		$("#toolTip").dialog('open');

		function tooltipClose(){
			$("#toolTip").html("");
		}


		$("#popClose").click(function(){
			tooltipClose();
		});


		$("#check_url_btn").click(function(){
			send_id = $("#id").val();
			send_type = "check";

			$("#result").load(paperCardControlUrl,{
				id:send_id,
				type:send_type
			});
		});


		$("#touroku_btn").click(function(){
			send_cd = $("#cd").val();
			send_id = $("#id").val();
			send_title = $("#title").val();
			send_url = $("#url").val();
			send_returnfunc = 1;
			send_type = "update";

			$("#result").load(paperCardControlUrl,{
				cd:send_cd,
				id:send_id,
				title:send_title,
				url:send_url,
				returnfunc:send_returnfunc,
				type:send_type
			});
		});


		$("#delete_btn").click(function(){

			confirmStr = "本当に削除してよろしいですか？";

			if(confirm(confirmStr)){
				send_cd = $("#cd").val();
				send_returnfunc = 1;
				send_type = "delete";

				$("#result").load(paperCardControlUrl,{
					cd:send_cd,
					returnfunc:send_returnfunc,
					type:send_type
				});
			}
		});

		$("#hyouji_btn").click(function(){
			send_cd = $("#cd").val();
			send_hyouji_flg = $("#hyouji_flg").val();
				send_returnfunc = 1;
			send_type = "hyouji_switch";

			$("#result").load(paperCardControlUrl,{
				cd:send_cd,
				hyouji_flg:send_hyouji_flg,
				returnfunc:send_returnfunc,
				type:send_type
			});
		});

	});

}




$(document).ready(function(){
	tooltipIni();
});



// QR
$(document).ready(function(){
	$('.qr').bind('click', function(){

		var id = $(this).attr('data-id');

		$("#qrDialog").dialog({
			autoOpen: false,
			open: function(event, ui){
			},
			resizable: false,
			draggable: false,
			closeOnEscape: false,
			modal: true,
			title: 'Infomation',
			width: 600,
			height: 330,
			buttons: {
				'閉じる': function() {
					$('#qrDialog').dialog('close');
				}
			}
		});

		dialogOpen(this);
	});

	$('.qrCodeDownload').live('click', function() {

		location.href = paperCardListUrl + '?qrCodeDownloadData=' + encodeURIComponent($(this).attr('data-obj')) + '&fileName=' + encodeURIComponent($(this).attr('data-id'));

	});

});

function dialogOpen(elm, id){
	data = $(elm).attr('data');
	var id = $(elm).attr('data-id');
	src = paperCardQrUrl + '?d=' + encodeURIComponent(data) + '&t=J';
	html = '<a href="'+data+'" target="_blank"><img width="140" height="140" src="'+src+'" />';
	html += '<br />'+$(elm).attr('linkadd')+'</a>';
	html += '<p class="center"><a href="'+$(elm).attr('uri')+'" target="_blank">'+$(elm).attr('uri')+'</a></p>';
	html += '<br />';
	html += '<p class="center"><button type="button" class="btnStyle qrCodeDownload" data-obj="' + data + '" data-id="' + id + '">ダウンロード</button></p>';

	$("#qrDialog").html(html);
	$("#qrDialog").dialog('open');

}


</script>
@endsection

@section('content')
<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.terminales.paper-cards.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="1" />
</form>

<div class="contents">


<div class="h2Title h2TitleLeft MB10 OFH">ペーパーサイネージ</div>
<div class="h3Title h2TitleLeft MB10 OFH">登録映像一覧</div>

<div class="OFH">
	<div class="fLeft">
		<a class="btnStyle FS20" href="{{ route('admin.terminales.paper-cards.create') }}">新規追加</a>
	</div>

@if ($cards->total() > 0)
	<div class="fRight">
		@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])
		<p><span class="attention">{{ $cards->firstItem() }}件～{{ $cards->lastItem() }}件目/{{ $cards->total() }}件中</span>	</p>
	</div>
@endif
</div>

@if ($cards->total() > 0)



<div class="form CB">

<table class="tableBox TLFix">
	<tr>
		<th style="width: 30px;"></th>
		<th style="width: 90px;">ID</th>
		<th>管理用名称</th>
		<th style="width: 60px;">稼働区分</th>
		<th style="width: 50px;">QR</th>
		<th style="width: 50px;">集計</th>
		<th style="width: 50px;"></th>
	</tr>
@foreach ($cards as $card)
	@if ($loop->index % 2 != 0)
	<tr class="tableBg">
	@else
	<tr>
	@endif
		<td>{{ $card->cd }}</td>
		<td>{{ $card->id }}</td>
		<td>{{ $card->title }}</td>
		<td>
		{{ $card->isRunning() ? '稼働' : '停止' }}
		</td>
		<td class="center">
			{{-- url se guarda con htmlspecialchars(ENT_QUOTES) (control.php): se imprime tal cual, como el legacy --}}
			<input type="button" class="qr btnStyle" value="QR"
				data="http://{{ $host }}/ptag/{{ $card->id }}"
				linkadd="{{ $host }}/ptag/{{ $card->id }}"
				uri="{!! $card->url !!}" data-id="{{ $card->id }}"
			/>
		</td>
		<td class="center">
			<a class="btnStyle" href="{{ route('admin.terminales.paper-cards.aggregate', ['cd' => $card->cd]) }}">集計</a>
		</td>
		<td class="center"><input type="button" class="pop btnStyle" value="詳細" cd="{{ $card->cd }}" /></td>
	</tr>
@endforeach
</table>

</div>

<ul class="btnBox2">
@include('admin.terminales.paper-cards.partials.pagination', ['paginator' => $cards, 'baseUrl' => route('admin.terminales.paper-cards.index')])
</ul>

<div>
	<a class="btnStyle" href="{{ route('admin.terminales.paper-cards.csv') }}">集計データCSV出力</a>
</div>



@else
<div class="form CB">
該当するデータがみつかりません。
</div>
@endif

<div id="toolTip"></div>





<div id="qrDialog" style="text-align: center;"></div>

<!-- / .contents --></div>
@endsection

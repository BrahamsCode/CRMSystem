{{--
    ペーパーサイネージ カード登録 (legacy: system/category7/card_signage/new.php).
    El alta va por AJAX a control.php (type=new); con «成功» vuelve al listado.

    Contrato de datos: $siteName (núcleo). Rutas: POST legacy.terminales.paper-card-control, GET admin.terminales.paper-cards.index.
    Diferencia: el legacy armaba el cuerpo a mano ("id=…&title="+title+"&url="+url) sin codificar, y una URL con
    «&» o «#» se cortaba; aquí se envía como objeto (jQuery lo codifica).
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
</script>

<script type="text/javascript">

$(document).ready(function(){
	$("#touroku_btn").click(function(){
		send_id = "";
		send_url = $("#url").val();
		title = $("#title").val();
		send_type = "new";

		$.ajax({
			type: "POST",
			url: "{{ route('legacy.terminales.paper-card-control') }}",
			data: { id: send_id, title: title, url: send_url, type: send_type },
			beforeSend: function(){
			},
			success: function(d){
				if(d=="成功"){
					location.href = "{{ route('admin.terminales.paper-cards.index') }}";
				}else{
					$("#result").html(d);
				}
			},
			error: function(){
			},
			complete: function(){
			}
		});
	});
});

</script>
@endsection

@section('content')
<div class="contents">

<div id="result"></div>

<form action="#" method="post" name="touroku_form">

<div class="h2Title h2TitleLeft MB10 OFH">カード登録</div>
<div class="h3Title h2TitleLeft MB10 OFH">カード登録フォーム</div>

<table class="tableBox">
	<tr>
		<th class="WD80">管理用名称</th>
		<td>
			<input type="text" name="title" id="title" class="FS20 WD140" value="" />
		</td>
	</tr>
	<tr>
		<th>URL</th>
		<td>
			<input type="text" name="url" id="url" class="FS20 WDFL" value="" />
		</td>
	</tr>
</table>

<div class="center btnBox">
	<input type="button" id="touroku_btn" class="MT10 FS20 btnStyle" value="登録" />
</div>

<div>
	※URLはhttp://から入力してください。
</div>

</form>


<!-- / .contents --></div>
@endsection

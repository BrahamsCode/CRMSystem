{{--
    ICカード管理 / カード管理 (legacy: system/category7/felica/card_list.php).

    Datos:
      $conditions     array  condiciones guardadas en la sesión (search_id, search_name, search_name_kana,
                             sch_joint_flg, andor)
      $searched       bool   llegó search=each_search (sin buscar: «検索して下さい»)
      $cards          LengthAwarePaginator<FelicaCard>|null  con kaishamei y customer (cd, id, name)
      $kensu          int
      $canChooseShop  bool   master: <select> de tienda por fila; si no, texto + hidden
      $shops          Collection<Shop>  opciones del <select> (cd, kaishamei)
      $sessionShop    int|null
      session('felica_card_errors')  list<string>  filas que S2 no dejó asignar

    Cambios respecto al legacy (ver Doc/coordinacion): la paginación y 表示件数 repiten search=each_search;
    el «フェリカ未登録» del colorbox envía si está marcado (el legacy mandaba siempre "1");
    setMember escapa el nombre antes de insertarlo.
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('title', 'カード管理  - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/category7/felica/css/add.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/category7/felica/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript" src="{{ asset('legacy/system/category7/felica/js/jquery.colorbox.js') }}"></script>


<script type="text/javascript">
//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

var main_row;
$(document).ready(function(){

	// 会員検索子窓


	$(".kaiin").click(function(){
		$('#result').html('');
		main_row = $(this).attr("main_row");
	});

	$(".kaiin").colorbox({opacity:0, width:500, height:500, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#dialog"});

	$("#btn_search").click(function(){


		send_id = $("#member_id").val();
		send_name = $("#member_name").val();
		felica_no = $("#felica_no").is(":checked") ? $("#felica_no").val() : "";
		shop_cd = $("#shop_cd"+main_row).val();

		$("#result").load("{{ route('legacy.terminales.felica-search-kaiin') }}",{
			shop_cd:shop_cd,
			name:send_name,
			felica_no:felica_no,
			id:send_id
		});
	});
});


// #################### 検索結果から会員情報を挿入 ####################

function setMember(cd, name) {
	$("#kaiin_name"+main_row).html($('<div/>').text(name).html()+'&nbsp;<span style="padding: 2px 3px; border:1px solid #EE0000; background: #FEE; color:#EE0000;">修正</span>&nbsp;&nbsp;');
	$("#kaiin_cd"+main_row).attr('value',cd);
	$('.kaiin').colorbox.close();

}

</script>




<script type="text/javascript">
$(function(){

	$(".delcheck").click(function(){

		var cd = $(this).attr('delcd');
		var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

		if(flg){

			$("#row_tr" + cd).html("<td colspan=\"8\" align=\"center\"><img src=\"{{ asset('legacy/system/img/loading.gif') }}\" width=\"100\" /></td>");
			$("#row_tr" + cd).load("{{ route('legacy.terminales.felica-del-card') }}",{
				cd:cd
			});
		}
		return false;
	});
});

</script>
@endsection

@section('content')
@php
    $joint = $conditions['sch_joint_flg'] ?? '';
    $andor = $conditions['andor'] ?? '';
@endphp

<form method="post" name="form_search" id="form_search" class="movie_programNone" action="{{ route('admin.terminales.felica-cards.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="1" />
<input type="hidden" name="search" value="each_search" />
</form>


<div class="contents">


<p class="h2Title">■カード管理 </p>




<!-- フォーム -->
<!-- /////////// 検索フォーム↓↓↓  //////////// -->

<form action="{{ route('admin.terminales.felica-cards.index') }}" method="post">
@csrf
<table class="parts_table_l">
  <tr>
    <th>会員ID</th>
    <td bgcolor="#FFFFFF"><input type="text" name="search_id" value="{{ $conditions['search_id'] ?? '' }}" style="ime-mode:inactive;" onfocus="this.style.backgroundColor='#FFF'" onBlur="this.style.backgroundColor='#FFFFCC'"></td>
  </tr>

  <tr>
    <th>会員名</th>
    <td bgcolor="#FFFFFF"><input type="text" name="search_name" value="{{ $conditions['search_name'] ?? '' }}" style="ime-mode:active;" onfocus="this.style.backgroundColor='#FFF'" onBlur="this.style.backgroundColor='#FFFFCC'"></td>
  </tr>

  <tr>
    <th>会員名（かな）</th>
    <td bgcolor="#FFFFFF"><input type="text" name="search_name_kana" value="{{ $conditions['search_name_kana'] ?? '' }}" style="ime-mode:active;" onfocus="this.style.backgroundColor='#FFF'" onBlur="this.style.backgroundColor='#FFFFCC'"></td>
  </tr>

  <tr>
    <th>会員情報入力</th>
    <td bgcolor="#FFFFFF">

	全て<input type="radio" name="sch_joint_flg" value="全"@if ($joint === '全') checked @endif>&nbsp;
	未入力のみ:<input type="radio" name="sch_joint_flg" value="未"@if ($joint === '未' || $joint === '') checked @endif>&nbsp;
	入力済のみ:<input type="radio" name="sch_joint_flg" value="済"@if ($joint === '済') checked @endif>

</td>
  </tr>

<tr>
<td bgcolor="#E6E6E6" colspan="2" align="left" class="f14">AND:<input type="radio" name="andor" value=""@if ($andor === '') checked @endif> OR:<input type="radio" name="andor" value="1"@if ($andor === '1') checked @endif>
<input type="submit" value="検　索" style="width:200px"> <a href="{{ route('admin.terminales.felica-cards.index', ['sesClear' => 'card']) }}">検索条件クリア</a></td>
</tr>
</table>
<input type="hidden" name="search" value="each_search" >
</form><br>



<!-- /////////// 検索フォーム↑↑↑  //////////// -->

@foreach ((array) session('felica_card_errors', []) as $error)
<p><span class="attention">{{ $error }}</span></p>
@endforeach

@if ($cards && $cards->total() > 0)
@php
    $rowCount = $cards->count();
    $from = ($cards->currentPage() - 1) * $cards->perPage();
@endphp
@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])

<p><span class="attention">{{ $cards->total() }}件</span>の該当結果がありました。</p>


<p><span class="attention">{{ $from + 1 }}件～{{ $from + $rowCount }}件目</span></p>



<!-- 呼び出す子窓のフォームの検索 -->
<div style="display:none;">
	<div id="dialog">
		&nbsp;
		フェリカ未登録:&nbsp;<input type="checkbox" name="felica_no" id="felica_no" value="1" checked="checked" />&nbsp;&nbsp;&nbsp;
		名前:&nbsp;<input type="text" name="member_name" id="member_name" />&nbsp;&nbsp;&nbsp;
		ID:&nbsp;<input type="text" name="member_id" id="member_id" /><br/>
		&nbsp;<input type="button" name="btn_search" id="btn_search" value="検索" />
		<br/><br/>
		<div id="result" style="width:100%;height:372px;overflow:auto;"></div>
	</div>
</div>
<!-- 呼び出す子窓のフォームの検索END -->

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.felica-cards.update') }}">
@csrf

<table class="tableBox2">
  <tr>
    <th></th>
    <th>ID</th>
    <th>店舗</th>
    <th>会員</th>
    <th>タイプ</th>
    <th>更新日</th>
    <th>シリアル</th>

    <th>削除</th>
  </tr>
@foreach ($cards as $card)
@php($row = $loop->index)
@if ($row % 2 != 0)
  <tr class="tableBg" id="row_tr{{ $card->cd }}">
@else
  <tr id="row_tr{{ $card->cd }}">
@endif
<td>{{ $from + 1 + $row }}</td>
    <td align="center">{{ $card->cd }}</td>

    <td align="left">

@if ($canChooseShop)
		<select name="shop_cd{{ $row }}" id="shop_cd{{ $row }}">
		@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @if ((string) $shop->cd === (string) $card->tb_shop_cd) selected="selected" @endif >{{ $shop->kaishamei }}</option>
		@endforeach
		</select>

@else

		{{ $card->kaishamei }}
		<input type="hidden" name="shop_cd{{ $row }}" id="shop_cd{{ $row }}" value="{{ $sessionShop }}" />

@endif


<input type="hidden" name="felica_no{{ $row }}" id="felica_no{{ $row }}" value="{{ $card->cid }}" />
<input type="hidden" name="main_table_cd{{ $row }}" value="{{ $card->cd }}" />
</td>


    <td align="right">
		<span id="kaiin_name{{ $row }}" style="float: left; margin-top: 0.2em;">@if ($card->tb_customer_cd !== null)【{{ $card->customer?->id }}】 <span style="color:#113366;">{{ $card->customer?->name }}</span>&nbsp;&nbsp;&nbsp;@endif</span><input type="button" name="kaiin" id="kaiin" main_row="{{ $row }}" class="kaiin" value="会員検索" /><input type="hidden" id="kaiin_cd{{ $row }}" name="kaiin_cd{{ $row }}" value="{{ $card->tb_customer_cd }}" />



</td>


    <td align="left">{{ $card->typ_exp }}</td>
    <td align="left">{{ $card->legacyDate('update_date')?->format('Y/m/d h時i分s秒') }}</td>
    <td align="left">{{ $card->cid }}</td>


    <td align="center"><input type="submit" delcd="{{ $card->cd }}" class="btn delcheck" value="削除する"></td>
  </tr>
@endforeach
</table>



<div class="btnBox">
<input type="hidden" name="cnt" value="{{ $rowCount }}" />
<input type="hidden" name="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" value="{{ $cards->currentPage() }}" />
<input type="submit" name="act" id="act" class="btn" value="更新する" />
</div>


</form>




<ul class="btnBox2">
@include('admin.terminales.partials.pagination', ['paginator' => $cards, 'baseUrl' => route('admin.terminales.felica-cards.index'), 'extraQuery' => ['search' => 'each_search']])
</ul>


@elseif ($searched)
見つかりませんでした
@else
検索して下さい
@endif
<!-- / .contents --></div>
@endsection

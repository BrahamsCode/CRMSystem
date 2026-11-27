{{-- Legacy: system/category1/config/card_new.php (カード管理). Tres estados en la misma página, como el legacy:
     error de asignación ($cardError), 生徒選択一覧 ($candidates) o la lista de tarjetas ($cards).
     Variables:
       $siteName    nombre del sitio
       $mode        tb_felica_mode.mode de la fila cd = 2 ('' sin fila)
       $cardError   string|null: felica_no del mensaje 「カード … は既に登録されているカードです。」
       $cards       LengthAwarePaginator|null de filas tb_user_card (+ customer_id, customer_name); 10 por página
       $cardFlg     '' | '0' | '1': filtro 未割当 / 割当済 / 全て
       $candidates  LengthAwarePaginator|null de Customer sin tarjeta (生徒選択一覧); 20 por página
       $felicaNo, $cardNo  tarjeta que se asigna (生徒選択一覧) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '初期設定管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac3").accordion('option', 'active', 0);

});

</script>

<script type="text/javascript">
<!--
function modeSelect(i){
	document.mode_action.felica_mode[i].checked = 'check'
	document.mode_action.submit();
}

--></script>
@endsection

@php
    $checked = in_array($mode, ['9', '1', '2'], true) ? $mode : '0';
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■カードリーダーモード設定</p>

<form name="mode_action" id="mode_action" class="form" action="{{ route('admin.clientes.cards.mode') }}" method="post">
@csrf

<fieldset>
<legend>モードセレクト</legend>

<ul class="modeSelect">
<li{!! $mode === '0' ? ' class="modeSelected"' : '' !!} onclick="modeSelect(0)">運用モード</li><!--
<li onclick="modeSelect(1)">運用出勤強制モード</li>
<li onclick="modeSelect(2)">運用退勤強制モード</li>-->
<li{!! $mode === '9' ? ' class="modeSelected"' : '' !!} onclick="modeSelect(3)">登録モード</li>
</ul>

<div class="displayNone">
<ul>
<li><input type="radio" name="felica_mode" id="felica_mode1" value="0" @checked($checked === '0')>運用モード</li>
<li><input type="radio" name="felica_mode" id="felica_mode2" value="1" @checked($checked === '1')>運用出勤強制モード</li>
<li><input type="radio" name="felica_mode" id="felica_mode3" value="2" @checked($checked === '2')>運用退勤強制モード</li>
<li><input type="radio" name="felica_mode" id="felica_mode4" value="9" @checked($checked === '9')>登録モード</li>
</ul>
<input type="hidden" name="felica_mode_up" value="up">
<input type="submit" value="モード変更">
</div><!--class="displayNone" end-->

</fieldset>

</form><!--name="mode_action" end-->


@if ($cardError !== null)
<p class="h2Title">■カード管理</p>
<p align="center">カード <font color=red>{{ $cardError }}</font> は既に登録されているカードです。ご確認ください。</p>
<form method="GET" action="{{ route('admin.clientes.cards') }}">
<input type="submit" value="戻る">
</form>
@elseif ($candidates === null)
<p class="h2Title">■カード管理</p>

@if ($cards->total() > 0)
<p><span class="text3L attention">{{ $cards->total() }}</span>件見つかりました。</p>

<table class="tableBox2">
<tr><th>　</th><th>No.</th><th>カードUID</th><th>登録日</th><th colspan=2>関連付け</th></tr>
@foreach ($cards as $card)
@if ($loop->index % 2 == 1)
<tr class="bg">
@else
<tr>
@endif


<td>{{ $loop->iteration }}</td>
<td>{{ $card->card_no }}</td>
<td>{{ $card->felica_no }}</td>
<td>{{ ctype_digit((string) $card->update) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $card->update, 'Asia/Tokyo')->format('Y/m/d') : '' }}</td>
@if ((string) $card->staff_cd !== '')
<td align="right">{{ $card->customer_id }}</td><td>{{ $card->customer_name }}</td>

@else
<td colspan="2" align="center">
<form method="POST" action="{{ route('admin.clientes.cards.select') }}">
@csrf
<input name="felica_no" value="{{ $card->felica_no }}" type="hidden">
<input name="card_no" value="{{ $card->card_no }}" type="hidden">
<input name="felica_add" value="up" type="hidden">
<input type="submit" value="生徒登録">
</form>
</td>
@endif
</tr>



@endforeach
</table>


<div class="btnBox form">
<form name="data_koushin" id="data_koushin" class="" action="{{ route('admin.clientes.cards') }}" method="get">
<ul>
<li><input type="radio" name="card_flg" id="card_flg1" class="checkBtn" value="1" @checked($cardFlg === '1')><label for="card_flg1">割当済</label></li>
<li><input type="radio" name="card_flg" id="card_flg2" class="checkBtn" value="0" @checked($cardFlg === '0')><label for="card_flg2">未割当</label></li>
<li><input type="radio" name="card_flg" name="card_flg3" class="checkBtn" value="" @checked($cardFlg === '')><label for="card_flg3">全て</label></li>
<li><input type="submit" value="情報更新"></li>
</ul>
</form>

</div><!--class="btnBox" end-->

<ul class="btnBox2">
{{-- Paginador propio de card_new.php (imágenes 戻る/次へ y todas las páginas); conserva card_flg (el legacy lo perdía) --}}
{{ \App\Support\LegacyHtml::pager(route('admin.clientes.cards') . '?card_flg=' . $cardFlg, $cards->currentPage(), $cards->perPage(), $cards->total(), '&se_flg=1&act=up') }}
</ul>

@else
現在割り当てられている登録はありません。
@endif


@else

<p class="h2Title">■生徒選択一覧</p>


@if ($candidates->total() > 0)
<p>カードナンバー:<span class="attention">{{ $cardNo }}</span> カードUID:<span class="attention">{{ $felicaNo }}</span>に割当てる生徒を選択してください。</p>
@else
<p>現在選択できるリストはありません。</p>
<div class="btnBox">
<form method="GET" action="{{ route('admin.clientes.cards') }}">
</form>
</div>
@endif
<div class="form">

@if ($candidates->total() > 0)

<table class="tableBox2">
  <tr>
    <th>生徒ID</th>
    <th>名前</th>
    <th>電話番号</th>
    <th>住所</th>
    <th>メールアドレス</th>
    <th>&nbsp;</th>
  </tr>


@foreach ($candidates as $customer)
	@if ($loop->index % 2 <> 0)
		<tr class="tableBg">
	@else
	  <tr>
	@endif
		<td>{{ $customer->id }}</td>
		<td><span title="{{ $customer->name_kana }}">{{ $customer->name }}</span></td>
		<td>{{ $customer->tel }}</td>
		<td>{{ $customer->pref }}<span title="{{ $customer->add_kana }}">{{ $customer->add }}</span></td>
		<td><a href="mailto:{{ $customer->email1 }}">{{ explode('@', (string) $customer->email1)[0] }}@...</a></td>

		<td align="center">
		<form action="{{ route('admin.clientes.cards.store') }}" method="post">
		@csrf
		<input type="hidden" name="staff_cd" value="{{ $customer->cd }}">
		<input type="hidden" name="felica_no" value="{{ $felicaNo }}">
		<input type="hidden" name="felica_set_flg" value="up">
		<input type="submit" value="登録">
		</form></td>

		</tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.clientes.config.partials.page-control', [
    'paginator' => $candidates,
    'pageUrl' => fn ($p) => route('admin.clientes.cards.select', ['page' => $p, 'felica_no' => $felicaNo, 'card_no' => $cardNo, 'felica_add' => 'up', 'kensu' => $candidates->perPage()]),
])
</ul>


<div class="btnBox">
<form method="GET" action="{{ route('admin.clientes.cards') }}">
<input type="submit" value="カード登録に戻る">
</form>
</div>

@endif
</div>
@endif

<!-- / .contents --></div>
@endsection

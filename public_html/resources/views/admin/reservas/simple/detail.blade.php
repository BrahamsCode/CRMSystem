{{-- Legacy: system/category3/reservation/reserve_detail.php (?reserve_cd=&page&dd&shop_cd&kensu): 予約詳細.
     Variables:
       $reservation   SimpleReservation (user_name, user_tel, user_mail, reserve_people con entidades HTML: {!! !!})
       $customer      ?object socio (id) — el legacy solo mostraba 会員番号 con kaiin_cd en la URL (nunca); aquí si es socio
       $shopName      ?string tienda
       $staff         ?object 指名 (name)
       $categoryName  ?string 予約内容 (con entidades)
       $listParams    parámetros de la lista que reenvían 戻る y los botones (el legacy reenviaba todo $_REQUEST)
       $canSeeMail    bool permiso 20-1 o master
       $canEdit       bool permiso 1026-3 o master (修正する)
       $canCancel     bool permiso 1026-4 o master (取り消し)
       $siteName      nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約詳細 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	$('body, html').animate({ scrollTop: 0 }, 0);
});
</script>


<script type="text/javascript">


function submitChk (shori_txt) {

		var hon_text = "";

		if(shori_txt == '予約確定'){
			hon_text = "\n\n\n予約確定を行った後は、電話で予約確定の連絡をして下さい\n\n";
		}

   	var flg = confirm ( shori_txt+"してもよろしいですか？\n\n"+shori_txt+"しない場合は[キャンセル]ボタンを押して下さい"+hon_text);

    return flg;
}

function submitPerm () {
    var flg = confirm ( "予約確定してもよろしいですか？\n\n予約確定しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}

function submitRaiten () {
    var flg = confirm ( "来店処理してもよろしいですか？\n\n来店処理しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}
</script>
@endsection

@php
    $at = $reservation->reservedAt();
    $flg = (string) $reservation->reserve_flg;
    $visited = (string) $reservation->raiten_flg === '1';
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■予約詳細</p>

<div class="form">

<fieldset>
<legend>予約情報</legend>
<table class="tableBox">

@if ($customer)
	<tr>
		<th>会員番号</th>
		<td>{{ $customer->id }}</td>
	</tr>
@endif

	<tr>
		<th>店舗</th>
		<td>{{ $shopName }}</td>
	</tr>

	<tr>
		<th>名前</th>
		<td>{!! $reservation->user_name !!}</td>
	</tr>

	<tr>
		<th>電話番号</th>
		<td>{!! $reservation->user_tel !!}</td>
	</tr>

	<tr>
		<th>メールアドレス</th>
		<td>{!! $canSeeMail ? $reservation->user_mail : '**********' !!}</td>
	</tr>

	<tr>
		<th>予約年月日</th>
		<td>{{ $at?->format('Y/m/d') }}</td>
	</tr>

	<tr>
		<th>時間</th>
		<td>{{ $at?->format('H:i') }}</td>
	</tr>

@if ((string) $reservation->reserve_people !== '')
	<tr>
		<th>人数</th>
		<td>{!! $reservation->reserve_people !!}</td>
	</tr>
@endif

	<tr>
		<th>予約内容</th>
		<td>{!! $categoryName !!}</td>
	</tr>

@if ((string) $reservation->reserve_nomination !== '')
	<tr>
		<th>指名</th>
		<td>{{ $staff?->name }}</td>
	</tr>
@endif

</table>
</fieldset>

</div>

<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ route('admin.reservas.simple.index') }}">
@csrf
<input type="image" name="reserve_list_btn" id="reserve_list_btn" src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" value="戻る" />
<input type="hidden" name="se_flg" id="se_flg" value="1" />
@foreach ($listParams as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
</form>

<div class="btnBox">

<ul>

@if ($flg !== '9' && ! $visited) {{-- 取り消しでなければ --}}
@if ($canEdit)
<li style="display: inline;">
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.simple.create') }}" style="display: inline;">
@csrf
<input type="hidden" name="reserve_cd" id="reserve_cd" value="{{ $reservation->cd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="enter" id="enter" class="btn" value="修正する" />
</form>
</li>
@endif

@if ($canCancel)
<li style="display: inline;">
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.reservas.simple.status', $reservation->cd) }}" onsubmit="return submitChk('取り消し')" style="display: inline;">
@csrf
@foreach ($listParams as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="hidden" name="act" id="act" value="del" />
<input type="submit" name="enter" id="enter" class="btn" value="取り消し" />
</form>
</li>
@endif
@endif
@if (! $visited) {{-- 来店していなければ --}}
@if ($flg === '1') {{-- 仮予約の場合 --}}
<li style="display: inline;">
<form method="post" name="form3" id="form3" class="form" action="{{ route('admin.reservas.simple.status', $reservation->cd) }}" onsubmit="return submitChk('予約確定')" style="display: inline;">
@csrf
@foreach ($listParams as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="hidden" name="act" id="act" value="kakutei" />
<input type="submit" name="enter" id="enter" class="btn" value="予約確定" />
</form>
</li>
@endif
@endif
@if ($flg === '2' && ! $visited) {{-- 本予約の場合 --}}
<li style="display: inline;">
<form method="post" name="form3" id="form4" class="form" action="{{ route('admin.reservas.simple.status', $reservation->cd) }}" onsubmit="return submitChk('来店処理')" style="display: inline;">
@csrf
@foreach ($listParams as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="hidden" name="act" id="act" value="visit" />
<input type="submit" name="enter" id="enter" class="btn" value="来店処理" />
</form>
</li>
@endif
</ul>

</div>




<!-- / .contents --></div>
@endsection

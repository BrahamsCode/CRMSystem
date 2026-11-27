{{-- Legacy: system/category3/reservation/reserve_new_complete.php (?reserve_cd=): 予約新規登録 完了.
     Variables:
       $reservation   SimpleReservation (user_name, user_tel, user_mail, reserve_people con entidades HTML: {!! !!})
       $customer      ?object socio (id) — el legacy solo mostraba 会員番号 con kaiin_cd en la URL (nunca); aquí si es socio
       $shopName      ?string tienda
       $staff         ?object 指名 (name)
       $categoryName  ?string 予約内容 (con entidades)
       $canSeeMail    bool permiso 20-1 o master
       $siteName      nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約新規登録 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@php $at = $reservation->reservedAt(); @endphp

@section('content')
<div class="contents">
<p class="h2Title">■予約新規登録 完了</p>



<div class="btnBox">

<ul>
@can('kengen', [1026, 2])<li style="display: inline;"><a href="{{ route('admin.reservas.simple.customer-search') }}"><img src="{{ asset('legacy/system/img/next_touroku_off.gif') }}" alt="続けて新規登録する" /></a></li>@endcan
@can('kengen', [1026, 1])<li style="display: inline;"><a href="{{ route('admin.reservas.simple.calendar') }}"><img src="{{ asset('legacy/system/img/next_kensaku_off.gif') }}" alt="予約一覧から検索する" /></a></li>@endcan
</ul>

</div>


<div class="form">

<p>登録情報は以下の通りです。</p>

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
		<th>携帯電話番号</th>
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


<div class="btnBox">

<ul>
@can('kengen', [1026, 2])<li style="display: inline;"><a href="{{ route('admin.reservas.simple.customer-search') }}"><img src="{{ asset('legacy/system/img/next_touroku_off.gif') }}" alt="続けて新規登録する" /></a></li>@endcan<!--
-->@can('kengen', [1026, 1])<li style="display: inline;"><a href="{{ route('admin.reservas.simple.calendar') }}"><img src="{{ asset('legacy/system/img/next_kensaku_off.gif') }}" alt="顧客一覧から検索する" /></a></li>@endcan
</ul>

</div>




<!-- / .contents --></div>
@endsection

{{-- Legacy: system/category3/reservation/reserve_new_confirm.php (rama «エラーないとき»: confirmación).
     Variables:
       $data, $act, $customer, $isMember, $ninsuFlg, $shimeiFlg, $canSeeMail, $siteName  (ver form.blade.php)
       $shopName      nombre de la tienda elegida
       $categoryName  予約内容 elegido (con entidades: {!! !!})
       $staffName     指名 elegido
       $hidden        campos que reenvían «書き直す» (a reserve_new.php) y «この内容で予約する» (al registro) --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', ($act === 'new' ? '予約新規登録' : '予約登録情報 修正') . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents form">
<p class="h2Title">■{{ $act === 'new' ? '予約新規登録' : '予約登録情報 修正' }}</p>

	<fieldset>
	<legend>予約情報</legend>
	<table class="tableBox">

	@if ($isMember)
		<tr>
			<th>会員番号</th>
			<td>{{ $customer?->id }}</td>
		</tr>
	@endif

		<tr>
			<th>店舗</th>
			<td>{{ $shopName }}</td>
		</tr>

		<tr>
			<th>名前</th>
			<td>{{ $data['name'] }}</td>
		</tr>

		<tr>
			<th>携帯電話番号</th>
			<td>{{ $data['tel_display'] ?? $data['tel'] }}</td>
		</tr>

		<tr>
			<th>メールアドレス</th>
			<td>{{ $canSeeMail ? $data['email'] : '**********' }}</td>
		</tr>

		<tr>
			<th>予約年月日</th>
			<td>{{ $data['hiduke'] }}</td>
		</tr>

		<tr>
			<th>時間</th>
			<td>{{ $data['hour1'] }}時{{ $data['min1'] }}分</td>
		</tr>

	@if ($ninsuFlg)
		<tr>
			<th>人数</th>
			<td>@if ($data['ninsuu'] !== ''){{ $data['ninsuu'] }}人@endif</td>
		</tr>
	@endif

	@if ($data['category_cd'] !== '')
		<tr>
			<th>予約内容</th>
			<td>{!! $categoryName !!}</td>
		</tr>
	@endif

	@if ($shimeiFlg && $data['staff_cd'] !== '')
		<tr>
			<th>指名</th>
			<td>{{ $staffName }}</td>
		</tr>
	@endif

	</table>
	</fieldset>

	<div class="btnBox">

	<form method="post" action="{{ route('admin.reservas.simple.create') }}">
	@csrf
	<input type="submit" name="modoru" value="書き直す" class="existBtn" />
	@foreach ($hidden as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}">
	@endforeach
	</form>

	<form method="post" action="{{ route('admin.reservas.simple.store') }}">
	@csrf
	<input type="submit" value="この内容で予約する" class="existBtn" />
	@foreach ($hidden as $key => $val)
	<input type="hidden" name="{{ $key }}" value="{{ $val }}">
	@endforeach
	</form>

	</div>

<!-- / .contents --></div>
@endsection

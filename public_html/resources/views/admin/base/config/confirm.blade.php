{{-- 基本情報確認・変更 / 基本情報登録 の確認: config_confirm.php sin errores --}}
@php
    $v = fn ($f) => $data[$f] ?? '';
@endphp
@extends('layouts.legacy')

@section('title', $setup ? '基本情報登録 - ' . $siteName : '基本情報登録 -総合業務管理システム-')

@if ($setup)
@section('top_page', '1')
@else
@section('menu', 'base')
@endif

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $setup ? '基本情報登録' : '基本情報確認・変更' }}</p>

<div class="form">

<fieldset>
<legend>会社情報</legend>
<table class="tableBox">
	<tr>
		<th><small>会社名/屋号(カナ)</small></th>
		<td colspan="3"><small>{{ $v('kaishamei_kana') }}</small></td>
	</tr>
	<tr>
		<th>会社名/屋号</th>
		<td colspan="3">{{ $v('kaishamei') }}</td>
	</tr>
	<tr>
		<th><small>代表者名(カナ)</small></th>
		<td colspan="3"><small>{{ $v('daihyoushamei_kana') }}</small></td>
	</tr>
	<tr>
		<th>代表者名</th>
		<td colspan="3">{{ $v('daihyoushamei') }}</td>
	</tr>
	<tr>
		<th>登記年月日</th>
		<td colspan="3">{{ $v('toukibi') }}</td>
	</tr>
	<tr>
		<th>業種</th>
		<td colspan="3">{{ $v('gyoushu') }}</td>
	</tr>
	<tr>
		<th>郵便番号</th>
		<td colspan="3">{{ $v('zip') }}</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="3">{{ $v('pref') }}</td>
	</tr>
	<tr>
		<th>市区町村(カナ)</th>
		<td colspan="3"><small>{{ $v('add_kana') }}</small></td>
	</tr>
	<tr>
		<th>市区町村</th>
		<td colspan="3">{{ $v('add') }}</td>
	</tr>
	<tr>
		<th>丁目・番地</th>
		<td colspan="3">{{ $v('add_number') }}</td>
	</tr>
	<tr>
		<th><small>マンション・ビル名(カナ)</small></th>
		<td colspan="3"><small>{{ $v('add_build_kana') }}</small></td>
	</tr>
	<tr>
		<th>マンション・ビル名</th>
		<td colspan="3">{{ $v('add_build') }}</td>
	</tr>
	<tr>
		<th>電話番号</th>
		<td>{{ $v('tel') }}</td>
		<th>ファックス番号</th>
		<td>{{ $v('fax') }}</td>
	</tr>
	<tr>
	  	<th>給与丸め</th>
	  	<td colspan="3">{{ $v('kyuyo_marume') }}分</td>
  	</tr>
	<tr>
		<th>始業時間</th>
		<td>{{ $v('start_work_time1') }}時&nbsp;{{ $v('start_work_time2') }}分</td>
  	<th>休憩時間</th>
  	<td>{{ $v('break_time') }}分</td>
  </tr>
</table>
</fieldset>

@unless ($setup)
<fieldset>
<legend>ホームページ情報</legend>
<table class="tableBox">
	<tr>
		<th>ホームページ表示名称</th>
		<td colspan="3">{{ $v('hp_hyouji_name') }}</td>
	</tr>
	<tr>
		<th>ホームページ表示電話番号</th>
		<td colspan="3">{{ $v('hp_hyouji_tel') }}</td>
	</tr>
	<tr>
		<th>店舗ホームページアドレス</th>
		<td colspan="3">{{ $v('shop_url') }}</td>
	</tr>
	<tr>
		<th>店舗メールアドレス</th>
		<td colspan="3">{{ $v('email') }}</td>
	</tr>
</table>
</fieldset>
@endunless

<fieldset>
<legend>管理者情報</legend>
<table class="tableBox">
	<tr>
		<th>担当者</th>
		<td colspan="3">{{ $v('tantou') }}</td>
	</tr>
	<tr>
		<th>担当者 電話番号</th>
		<td>{{ $v('tantou_tel') }}</td>
		<th>担当者 ファックス番号</th>
		<td>{{ $v('tantou_fax') }}</td>
	</tr>
	<tr>
		<th>担当者 メールアドレス</th>
		<td colspan="3">{{ $v('tantou_email') }}</td>
	</tr>
	<tr>
		<th>ID</th>
		<td>master</td>
		<th>パスワード</th>
		<td>{{ $v('pass') }}</td>
	</tr>
</table>
</fieldset>

<div class="btnBox">
<form method="post" action="config.php">
@foreach ($data as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}">
@endforeach
<input type="submit" name="modoru" value="書き直す" class="existBtn" />
</form>
<form method="post" action="config_register.php">
@foreach ($data as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}">
@endforeach
<input type="hidden" name="act" value="up" />
<input type="submit" name="enter" value="この内容で登録する" class="existBtn" />
</form>
</div>

</div>

<!-- / .contents --></div>
@endsection

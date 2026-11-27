{{-- Legacy: system/category5/staff/staff_recover_confirm.php (削除を取り消す → 復元する).
     Diferencias con el legacy (variables sin asignar allí): se muestran 都道府県, 雇用形態, 役職 y
     携帯電話番号1/2 (mtel1/mtel2), y la foto por cd (como staff_detail).
     Variables: $staff (borrado), $shopName, $photoUrl, $duplicates ['id' => bool, 'email1' => bool] --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ登録情報')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
@if (array_filter($duplicates))
alert("すでに再登録されたメールアドレスまたはIDが存在するため復元できません。");
@endif
});
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■スタッフ登録情報</p>
<p>登録情報は以下の通りです。</p>

<div class="form">
<fieldset>
<legend>スタッフ情報1</legend>
<table class="tableBox">
	<tr>
		<th>スタッフID</th>
		<td @if ($duplicates['id']) class="Ng" @endif>{!! $staff->id !!}@if ($duplicates['id'])<p class="m0">※既に使用されているIDです。</p>@endif</td>
		<th><label for="shop_cd">店舗</label></th>
		<td>{!! $shopName !!}</td>
		<th>スタッフ写真</th>
	</tr>
	<tr>
		<th><small>名前(カナ)</small></th>
		<td colspan="3">{!! $staff->name_kana !!}</td>
		<td rowspan="8" class="textCenter" valign="top">
		<div id="gazou">@if ($photoUrl === null)<img src="/system/img/no_photo.gif" alt="No Photo" class="mB10" />@else<img src="{{ $photoUrl }}" alt="スタッフ写真" class="mB10" />@endif</div></td>
	</tr>
	<tr>
		<th>名前</th>
		<td colspan="3">{!! $staff->name !!}</td>
	</tr>
	<tr>
		<th>性別</th>
		<td>{!! $staff->sex !!}</td>
		<th>血液型</th>
		<td>{!! $staff->bloodtype !!}</td>
	</tr>
	<tr>
		<th>生年月日</th>
		<td>{!! $staff->birthday !!}</td>
		<th>年齢</th>
		<td>{{ $staff->age }}歳</td>
	</tr>
	<tr>
		<th>郵便番号</th>
		<td colspan="3">{!! $staff->zip !!}</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="3">{!! $staff->pref !!}</td>
	</tr>
	<tr>
		<th>市区町村(カナ)</th>
		<td colspan="3">{!! $staff->add_kana !!}</td>
	</tr>
	<tr>
		<th>市区町村</th>
		<td colspan="3">{!! $staff->add !!}</td>
	</tr>
	<tr>
		<th>丁目・番地</th>
		<td colspan="4">{!! $staff->add_number !!}</td>
	</tr>
	<tr>
		<th><small>マンション・ビル名(カナ)</small></th>
		<td colspan="4">{!! $staff->add_build_kana !!}</td>
	</tr>
	<tr>
		<th>マンション・ビル名</th>
		<td colspan="4">{!! $staff->add_build !!}</td>
	</tr>
	<tr>
		<th>メールアドレス1</th>
		<td @if ($duplicates['email1']) class="Ng" @endif>{!! $staff->email1 !!}@if ($duplicates['email1'])<p class="m0">※既に使用されているメールアドレスです。</p>@endif</td>
		<th>メールアドレス2</th>
		<td colspan="2">{!! $staff->email2 !!}</td>
	</tr>
	<tr>
		<th>メールアドレス(携帯1)</th>
		<td>{!! $staff->mmail1 !!}</td>
		<th>メールアドレス(携帯2)</th>
		<td colspan="2">{!! $staff->mmail2 !!}</td>
	</tr>
	<tr>
		<th>電話番号</th>
		<td>{!! $staff->tel !!}</td>
		<th>FAX番号</th>
		<td colspan="2">{!! $staff->fax !!}</td>
	</tr>
	<tr>
		<th>携帯電話番号1</th>
		<td>{!! $staff->mtel1 !!}</td>
		<th>携帯電話番号2</th>
		<td colspan="2">{!! $staff->mtel2 !!}</td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>スタッフ情報2</legend>
<table class="tableBox">
	<tr>
		<th>入社日</th>
		<td>{!! $staff->entranceday !!}</td>
		<th>退社日</th>
		<td>{!! $staff->retirementday !!}</td>
	</tr>
	<tr>
		<th>所属部署</th>
		<td colspan="3">{!! $staff->belongpost !!}</td>
	</tr>
	<tr>
		<th>雇用形態</th>
		<td>{!! $staff->employmentsystem !!}</td>
		<th>役職</th>
		<td>{!! $staff->officialposition !!}</td>
	</tr>
	<tr>
		<th>給料単価</th>
		<td>{!! $staff->salary !!}円</td>
		<th>有給日数</th>
		<td>{!! $staff->paidholiday !!}日</td>
	</tr>
	<tr class="tableBox">
		<th>備考欄</th>
		<td colspan="3">{!! $staff->remarks !!}</td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>交通費情報</legend>
<table class="tableBox">
	<tr>
		<th>最寄り駅</th>
		<td colspan="3">{!! $staff->near_station1 !!}線 {!! $staff->near_station2 !!}駅から {!! $staff->near_station3 !!} {!! $staff->near_station4 !!}分</td>
	</tr>
	<tr>
		<th>交通機関</th>
		<td colspan="3">{!! $staff->traf_transport !!}</td>
	</tr>
	<tr>
		<th>区間</th>
		<td colspan="3">{!! $staff->traf_section1_1 !!} ～ {!! $staff->traf_section1_2 !!}</td>
	</tr>
	<tr>
		<th>往復料金</th>
		<td>{!! $staff->traf_charge !!} 円</td>
		<th>通勤定期代</th>
		<td>{!! $staff->traf_regularfee !!} 円</td>
	</tr>
	<tr>
		<th>交通費備考欄</th>
		<td colspan="3">{!! $staff->traf_remarks !!}</td>
	</tr>
</table>
</fieldset>

@unless (array_filter($duplicates))
<div class="btnBox">
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.operaciones.staff.restore', $staff->cd) }}">
@csrf
<input type="submit" name="delete" id="delete" class="btn" value="復元する">
</form>
</div>
@endunless

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection

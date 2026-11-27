{{-- Lista de direcciones de prueba que carga test_mail_address por AJAX — legacy: system/category3/mail_mag/ajax_test_mail_address_new.php --}}
@php
	$rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
@endphp
@if ($addresses->isNotEmpty())

<table class="tableBox4">

<tr>
<th>名前</th>
<th>メールアドレス</th>
<th style="width:40px; text-align:center;">編集</th>
<th style="width:40px; text-align:center;">削除</th>
<th style="width:30px; text-align:center;">順番</th>
</tr>
@foreach ($addresses as $address)
@php
	$editUrl = route('admin.promocion.test-addresses.index', array_filter(['cd' => $address->cd, 'shop' => $isMaster ? $shopCd : null]));
@endphp
<tr>
<td>{{ $address->name }}</td>
<td>{{ $address->email }}</td>
<td><input type="button" value="編集" onclick="location.href='{{ $editUrl }}';" /></td>
<td><input type="button" value="削除" onclick="submitChk2({{ $address->cd }}); return false;" /></td>
<td>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankup', '{{ $address->cd }}', '{{ $address->rank }}')">▲</div>
<div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankdown', '{{ $address->cd }}', '{{ $address->rank }}')">▼</div>

<form method="post" name="form_{{ $address->cd }}" id="form_{{ $address->cd }}" class="displayNone" action="{{ route('legacy.promocion.mail_mag.test_mail_address_register') }}">
@csrf
<input type="hidden" name="act" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $address->cd }}">
<input type="hidden" name="rank" class="btn" value="{{ $address->rank }}">
<input type="hidden" name="tb_shop_cd" class="btn" value="{{ $address->tb_shop_cd }}">
</form>

</td>
</tr>
@endforeach
</table>

@else
データがありません。
@endif

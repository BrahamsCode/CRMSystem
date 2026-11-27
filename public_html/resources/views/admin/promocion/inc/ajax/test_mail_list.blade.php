{{-- テストメール送信先の選択 (inc/ajax_testMailList.php). Variables: $addresses, $selected (cds) --}}
<div style="height:400px;overflow:auto;">
<table class="tableBox2" style="table-layout:fixed;">
@if ($addresses->isNotEmpty())
<tr style="line-height:15px;">
<th width="10%" style="line-height:15px;">送信</th>
<th width="45%" style="line-height:15px;">名前</th>
<th width="45%" style="line-height:15px;">メールアドレス</th>
</tr>
@foreach ($addresses as $row => $address)
<tr style="line-height:15px;background-color:{{ $row % 2 == 0 ? '#FFFFFF' : '#EEEEEE' }}">
	<td><input type="checkbox" name="testMail[{{ $address->cd }}]" class="testMail" value="{{ $address->cd }}" @checked(in_array((int) $address->cd, $selected, true)) onclick="setCds(this)"></td>
	<td>{{ $address->name }}</td>
	<td>{{ $address->email }}</td>
</tr>
@endforeach
</table>
</div>
<ul class="btnBox2">
</ul>
<div style="text-align:center;">
<input type="button" id="testMail_kettei" name="testMail_kettei" class="btnStyleM" value="　テストメールを送信する　" onclick="testMailKettei()" />
</div>
<div id="testMailMsg" style="color:red;font-weight:bold;text-align:center;">
</div>
@else
<p>データが見つかりませんでした</p>
@endif

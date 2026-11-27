{{-- 例外顧客の選択 (inc/ajax_reigai_kaiin.php). Variables: $rows, $page, $maxPage, $total, $excluded (cds) --}}
<div>
<table class="tableBox2" style="table-layout:fixed;">
@if ($rows->isNotEmpty())
<tr style="line-height:15px;">
<th width="7%" style="line-height:15px;">送信</th>
<th width="13%" style="line-height:15px;">会員番号</th>
<th width="35%" style="line-height:15px;">名前</th>
<th width="45%" style="line-height:15px;">メールアドレス</th>
</tr>
@foreach ($rows as $row => $customer)
<tr style="line-height:15px;background-color:{{ $row % 2 == 0 ? '#FFFFFF' : '#EEEEEE' }}">
	<td><input type="checkbox" name="reigai_kaiin[{{ $customer->cd }}]" class="reigai_kaiin" value="{{ $customer->cd }}" @checked(! in_array((int) $customer->cd, $excluded, true)) onclick="fnc_SetCds(this)"></td>
	<td>{{ $customer->id }}</td>
	<td>{{ $customer->name }}</td>
	<td>{{ $customer->email1 }}</td>
</tr>
@endforeach
</table>
</div>
<ul class="btnBox2">
@if ($page != 1 && 15 < $total)
<li class="btn"><a href="javascript:fnc_SetKaiinReigaiDialog({{ $page - 1 }}, reigai_kaiin_cds);" ><img src="{{ asset('legacy/system/img/') }}btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="javascript:fnc_SetKaiinReigaiDialog({{ $l }}, reigai_kaiin_cds);" >{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="javascript:fnc_SetKaiinReigaiDialog({{ $page + 1 }}, reigai_kaiin_cds);" ><img src="{{ asset('legacy/system/img/') }}btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
<div style="text-align:center;">
<input type="button" id="reigai_kaiin_kettei" name="reigai_kaiin_kettei" class="btnStyleM" value="決定" onclick="fnc_setKaiinKettei()" />
<br />
<span style="color:red;font-weight:bold;">※決定ボタンを押さないと反映されません</span>
</div>
@else
<p>データが見つかりませんでした</p>
</table>
</div>
@endif

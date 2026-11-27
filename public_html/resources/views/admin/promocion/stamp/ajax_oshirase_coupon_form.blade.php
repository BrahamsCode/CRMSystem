{{--
	ajax_oshirase_coupon.php con stamp_count: formulario del aviso de esa tienda y número de sellos.
	Variables: $row (tb_stamp_oshirase_coupon o null), $selected (cd de cupón marcados), $coupons (cupones de canje de la tienda
	  y de 全店舗, por 消費数), $surnameEnabled, $stampEnabled, $visitsEnabled
--}}
@php
	$readonly = ' type="text" readonly="readonly" style="background:#FFF; border: 1px solid #999; width: 6em; text-align: center;" onclick="this.select(0,this.value.length)"';
	$tag = fn (string $t) => '※ <font color="black" style="font-weight: bold;"><input value="{' . $t . '}"' . $readonly . ' /></font>';
@endphp
<table class="tableBox8" style="margin-bottom: 5px;">
	<tr>
	<th width="75"></th>
	<td>
	<select name="hyouji_flg" id="hyouji_flg">
	<option value="1">適用する</option>
	<option value=""@if ((string) ($row->hyouji_flg ?? '') !== '1') selected="selected"@endif>適用しない</option>
	</select>
	</td>
	</tr>
	<tr>
	<th>メール内容</th>
	<td>
	<p>{!! $tag('名前') !!} と入力された箇所には顧客の<font color="red">名前</font>が挿入されます。<br />
	@if ($surnameEnabled)
	{!! $tag('苗字') !!} と入力された箇所には顧客の<font color="red">苗字</font>が挿入されます。<br />
	@endif
	{!! $tag('ID') !!} と入力された箇所には顧客の<font color="red">ID番号</font>が挿入されます。<br />
	{!! $tag('誕生月日') !!} と入力された箇所には顧客の<font color="red">誕生月日</font>が挿入されます。<br />
	{!! $tag('住所') !!} と入力された箇所には顧客の<font color="red">住所</font>が挿入されます。<br />
	{!! $tag('電話番号') !!} と入力された箇所には顧客の<font color="red">電話番号</font>が挿入されます。<br />
	@if ($stampEnabled)
	{!! $tag('スタンプ数') !!} と入力された箇所には顧客の<font color="red">スタンプ数</font>が挿入されます。<br />
	@endif
	@if ($visitsEnabled)
	{!! $tag('最終来店日') !!} と入力された箇所には顧客の<font color="red">最終来店</font>が挿入されます。<br />
	　 来店履歴が無い場合はそのまま表示されてしまいますので、ご注意ください。(フォローメール推奨)
	@endif
	</p>
	<div style="width: 15em; margin: 0; border: 2px solid #66666;">
	<textarea name="naiyou" id="naiyou" cols="50" rows="10" style="width: 15em;">{{ $row->naiyou ?? '' }}</textarea><br />
	<div style="text-align: center;">↓↓↓↓↓↓↓↓↓↓↓↓↓↓<br />
	以下クーポン情報が並びます。</div>
	</div>
	</td>
	</tr>
</table>
以下より、紹介したいスタンプ用クーポンを選択してください。
@forelse ($coupons as $coupon)
	@php
		$limit = (string) $coupon->limit_flg === '5' ? date('Y/m/d', (int) $coupon->time_limit) : $coupon->time_limit;
		$limits = ['', '発行から1週間', '発行から1ヶ月', '発行から3ヶ月', '発行から' . $limit . '日間', '指定日付：' . $limit . 'まで'];
		$isSelected = in_array((string) $coupon->cd, $selected, true);
	@endphp

<table class="tableBox8" style="margin-bottom: 5px;{{ $isSelected ? 'background: #FFEEEE;' : '' }}">
	<tr>
	<th rowspan="4" style="width: 1.5em; text-align: center;">
	<label for="coupon_master{{ $coupon->cd }}">
	<input type="checkbox" name="coupon_master[]" id="coupon_master{{ $coupon->cd }}" class="couponMasterCheck" value="{{ $coupon->cd }}" @if ($isSelected) checked="checked"@endif/>@if ($isSelected)<br />設<br />定<br />中@endif
	</label>
	</th>

	<th style="width: 5em;">ID</th>
	<td style="width: 3em;">{{ $coupon->cd }}</td>
	<th style="width: 4em;">消費数</th>
	<td>{{ $coupon->shouhi_point }}</td>
	</tr>

	<tr>
	<th>有効期限</th>
	<td colspan="3">{{ $limits[(int) $coupon->limit_flg] ?? '' }}</td>
	</tr>

	<tr>
	<th>タイトル</th>
	<td colspan="3">{{ $coupon->title }}</td>
	</tr>

	<tr>
	<th>内容</th>
	<td colspan="3">{!! nl2br(e($coupon->comment)) !!}</td>
	</tr>
</table>
@empty
クーポンが登録されていません。
@endforelse

<div class="center" style="margin-bottom: 5px;">
<input type="button" id="sendBtn" class="btnStyleM" value="この内容で登録する" onclick="sendFunc();" />
</div>

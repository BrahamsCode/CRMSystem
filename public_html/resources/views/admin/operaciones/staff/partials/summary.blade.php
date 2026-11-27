{{-- Ficha de solo lectura de staff_entry_confirm.php (sin errores) y staff_entry_complete.php (mismo marcado).
     Variables: $data (crudo), $imageUrl, $shopNames, $groupNames (cd => nombre), $hasHomepage, $isAdminAccount --}}
@php($v = fn (string $key) => $data[$key] ?? '')
<fieldset>
<legend>スタッフ情報1</legend>
<table class="tableBox">
	<colgroup>
		<col style="width:7em;">
		<col style="width:auto;">
		<col style="width:7em;">
		<col style="width:auto;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="id">スタッフID</label></th>
		<td colspan="3">{{ $v('id') }}</td>
		<th>スタッフ写真</th>
	</tr>
	<tr>
		<th><label for="shop_cd">店舗</label></th>
		<td colspan="3">{{ $shopNames[(int) $v('shop_cd')] ?? '' }}</td>
		<td rowspan="9" class="textCenter" valign="top">
		<div id="gazou">@if ($imageUrl)<img src="{{ $imageUrl }}" alt="スタッフ写真" class="mB10" />@else<img src="/system/img/no_photo.gif" alt="No Photo" class="mB10" />@endif</div>
		</td>
	</tr>
	<tr>
		<th><label for="kana"><small>名前(カナ)</small></label></th>
		<td colspan="3"><small>{{ $v('name_kana') }}</small></td>
	</tr>
	<tr>
		<th><label for="name">名前</label></th>
		<td colspan="3">{{ $v('name') }}</td>
	</tr>
	<tr>
		<th><label for="sex">性別</label></th>
		<td>{{ $v('sex') }}</td>
		<th><label for="bloodtype">血液型</label></th>
		<td>{{ $v('bloodtype') }}</td>
	</tr>
	<tr>
		<th><label for="birthday">生年月日</label></th>
		<td>{{ $v('birthday') }}</td>
		<th><label for="age">年齢</label></th>
		<td>{{ $v('age') }}歳</td>
	</tr>
	<tr>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="3">{{ $v('zip') }}</td>
	</tr>
	<tr>
		<th><label for="pref">都道府県</label></th>
		<td colspan="3">{{ $v('pref') }}</td>
	</tr>
	<tr>
		<th><label for="add_kana"><small>市区町村(カナ)</small></label></th>
		<td colspan="3"><small>{{ $v('add_kana') }}</small></td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="3">{{ $v('add') }}</td>
	</tr>
	<tr>
		<th><label for="add_number">丁目・番地</label></th>
		<td colspan="4">{{ $v('add_number') }}</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>ビル名(カナ)</small></label></th>
		<td colspan="4"><small>{{ $v('add_build_kana') }}</small></td>
	</tr>
	<tr>
		<th><label for="add_build">ビル名</label></th>
		<td colspan="4">{{ $v('add_build') }}</td>
	</tr>
	<tr>
		<th><label for="email1">メールアドレス1</label></th>
		<td>{{ $v('email1') }}</td>
		<th><label for="email2">メールアドレス2</label></th>
		<td colspan="2">{{ $v('email2') }}</td>
	</tr>
	<tr>
		<th><label for="mmail1">メールアドレス(携帯1)</label></th>
		<td>{{ $v('mmail1') }}</td>
		<th><label for="mmail2">メールアドレス(携帯2)</label></th>
		<td colspan="2">{{ $v('mmail2') }}</td>
	</tr>
	<tr>
		<th><label for="tel">電話番号</label></th>
		<td>{{ $v('tel') }}</td>
		<th><label for="fax">FAX番号</label></th>
		<td colspan="2">{{ $v('fax') }}</td>
	</tr>
	<tr>
		<th><label for="mtel1">携帯電話番号1</label></th>
		<td>{{ $v('mtel1') }}</td>
		<th><label for="mtel2">携帯電話番号2</label></th>
		<td colspan="2">{{ $v('mtel2') }}</td>
	</tr>
</table>
</fieldset>

<fieldset style="margin-top: 10px;">
<legend>スタッフ情報2</legend>
<table class="tableBox">
	<colgroup>
		<col style="width:7em;">
		<col style="width:auto;">
		<col style="width:7em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="entranceday">入社日</label></th>
		<td>{{ $v('entranceday') }}</td>
		<th><label for="retirementday">退社日</label></th>
		<td>{{ $v('retirementday') }}</td>
	</tr>
	<tr>
		<th><label for="belongpost">所属部署</label></th>
		<td colspan="3">{{ $v('belongpost1') !== '' ? $v('belongpost1') : $v('belongpost') }}</td>
	</tr>
	<tr>
		<th><label for="employmentsystem">雇用形態</label></th>
		<td>{{ $v('employmentsystem') }}</td>
		<th><label for="officialposition">役職</label></th>
		<td>{{ $v('officialposition') }}</td>
	</tr>
	<tr>
		<th><label for="salary">給料単価</label></th>
		<td>{{ $v('salary') }}円</td>
		<th><label for="paidholiday">有給日数</label></th>
		<td>{{ $v('paidholiday') }}日</td>
	</tr>
	<tr>
		<th>歩合</th>
		<td colspan="3">{{ $v('kingaku_buai') }}%</td>
	</tr>
	<tr>
		<th>指名許諾</th>
		<td colspan="3">{{ $v('reserve_flg') === '1' ? '受ける' : '受けない' }}</td>
	</tr>
	<tr>
		<th>共通アカウント</th>
		<td>{{ $v('common_flg') === '1' ? 'する' : 'しない' }}</td>
		<th>表示優先度（並び順）</th>
		<td>{{ $v('rank') }}</td>
	</tr>
	<tr class="tableBox">
		<th><label for="remarks">備考欄</label></th>
		<td colspan="3">{{ $v('remarks') }}</td>
	</tr>
</table>
</fieldset>

<fieldset style="margin-top: 10px;">
<legend>交通費情報</legend>
<table class="tableBox">
	<colgroup>
		<col style="width:7em;">
		<col style="width:auto;">
		<col style="width:7em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="near_station1">最寄り駅</label></th>
		<td colspan="3">{{ $v('near_station1') }}線 {{ $v('near_station2') }}駅から {{ $v('near_station3') }} {{ $v('near_station4') }}分</td>
	</tr>
	<tr>
		<th><label for="traf_transport">交通機関</label></th>
		<td colspan="3">{{ $v('traf_transport') }}</td>
	</tr>
	<tr>
		<th><label for="traf_section">区間</label></th>
		<td colspan="3">{{ $v('traf_section1_1') }} ～ {{ $v('traf_section1_2') }}</td>
	</tr>
	<tr>
		<th><label for="traf_charge">往復料金</label></th>
		<td>{{ $v('traf_charge') }} 円</td>
		<th><label for="traf_regularfee">通勤定期代</label></th>
		<td>{{ $v('traf_regularfee') }} 円</td>
	</tr>
	<tr>
		<th><label for="traf_remarks">交通費備考欄</label></th>
		<td colspan="3">{{ $v('traf_remarks') }}</td>
	</tr>
</table>
</fieldset>

@if ($hasHomepage)
<fieldset style="margin-top: 10px;">
<legend>ホームページ情報</legend>
<table class="tableBox">
	<colgroup>
		<col style="width:7em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th>ホームページ表示</th>
		<td colspan="4">{{ $v('hyouji_flg') === '1' ? '表示する' : '表示しない' }}</td>
	</tr>
	<tr>
		<th>写真表示</th>
		<td colspan="4">
			{{ $v('photo_hyouji_flg') === '1' ? '表示する' : '表示しない' }}
		</td>
	</tr>
	<tr>
		<th><label for="comment">コメント</label></th>
		<td colspan="4">{!! nl2br(e($v('comment'))) !!}</td>
	</tr>
</table>
</fieldset>
@endif

<fieldset style="margin-top: 10px;">
<legend>ログイン</legend>
<table class="tableBox">
	<colgroup>
		<col style="width:7em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
	<th>ログインID</th>
	<td>@if ($isAdminAccount)管理者用ID@else{{ $v('id') }}@endif</td>
	</tr>
	<tr>
	<th>パスワード</th>
	<td>@if ($isAdminAccount)管理者用パスワード@else{{ $v('login_pass') }}@endif</td>
	</tr>
@unless ($isAdminAccount)
	<tr>
		<th>グループ</th>
		<td>
			{{ $groupNames[(int) $v('tb_staff_group_cd')] ?? '' }}
		</td>
	</tr>
@endunless
</table>
</fieldset>

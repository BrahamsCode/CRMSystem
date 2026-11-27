{{-- Legacy: system/category1/inc/detail_tab.php (pestaña 顧客情報; incluye detail_tab2.php y detail_tab3.php al final).
     Variables:
       $customer      modelo tb_customer (columnas legacy) + shoukaisha_name
       $shopList      array cd => kaishamei de tb_shop (disp_flg != 9)
       $staffEnabled  bool: módulo スタッフ管理 activo (muestra la fila 指名担当者)
       $staffName     string|null: tb_staff.name de $customer->tb_staff_cd
       $rankRows      array|null: filas de ランク (una por módulo 顧客ランク% activo): ['title' => '会計金額によるランク'|'来店回数によるランク',
                      'now' => nombre de la ランク actual, 'pre' => nombre de la anterior]. null/vacío = sin módulo de ランク.
       + las de tabs/tab2 y tabs/tab3.
     Los campos enmascarados usan el permiso legacy $kengen->myKengen[N][1] → @can('kengen', [N, 1]) (master incluido en el Gate). --}}
@php
    // 誕生日設定
    $kojinBirth = (string) ($customer->kojin_birth ?? '');
    if ($kojinBirth != '' && $kojinBirth != '//') {
        $kojinBirthParts = explode('/', $kojinBirth) + ['', '', ''];
        if ($kojinBirthParts[0] == '') {
            $kojinBirthParts[0] = '****';
        }
        $kojinBirthDisp = $kojinBirthParts[0] . '/' . $kojinBirthParts[1] . '/' . $kojinBirthParts[2];
    } else {
        $kojinBirthDisp = '---';
    }
    // Fechas epoch → 'Y/m/j H時i分' (sin 1970 si no hay valor)
    $epochDisp = fn ($v) => is_numeric($v) && $v > 0
        ? \Illuminate\Support\Carbon::createFromTimestamp($v, 'Asia/Tokyo')->format('Y/m/j H時i分')
        : '';
@endphp
<div class="tabBody">

<table class="tableBox" style="margin-bottom: 10px;">

	<tr>
		<th>会員番号@if ($customer->type)({{ $customer->type }})@endif</th>
		<td>{{ $customer->id }}</td>
		<th>管理番号</th>
		<td>{{ $customer->kanri_id }}</td>
		<th>登録店舗</th>
		<td>{{ $shopList[$customer->tb_shop_cd ?? ''] ?? '' }}</td>
	</tr>

	<tr style="font-size: 8px;">
		<th style="padding: 0 5px;">カナ</th>
		<td colspan="5" style="padding: 0 5px;">{{ $customer->name_kana }}</td>
	</tr>

	<tr>
		<th>名前</th>
		<td colspan="5" style="font-size: 18px;">{{ $customer->name }}</td>
	</tr>

	<tr>
		<th>住所</th>
		<td colspan="5">@can('kengen', [9, 1]){{ $customer->zip }}@else*******@endcan<br />
		@can('kengen', [10, 1]){{ $customer->pref }}@else****@endcan
		<span title="@can('kengen', [11, 1]){{ $customer->add_kana }}@else**********@endcan">
		@can('kengen', [14, 1]){{ $customer->add }}@else**********@endcan
		</span>
		@can('kengen', [15, 1]){{ $customer->add_number }}@else*******@endcan
		<span title="@can('kengen', [12, 1]){{ $customer->add_build_kana }}@else**********@endcan">
		@can('kengen', [13, 1]){{ $customer->add_build }}@else**********@endcan
		</span>
		</td>
	</tr>

	<tr>
		<th>メールアドレス</th>
		<td colspan="5">
		@can('kengen', [20, 1]){{ $customer->email1 }}@else**********@endcan
		@if ($customer->email2)<br />
		(<small>サブアドレス：</small>@can('kengen', [21, 1]){{ $customer->email2 }})@else**********)@endcan
		@endif</td>
	</tr>

	<tr>
		<th>電話番号</th>
		<td>@can('kengen', [18, 1]){{ $customer->tel }}@else**********@endcan</td>
		<th>ファックス番号</th>
		<td colspan="3">@can('kengen', [19, 1]){{ $customer->fax }}@else**********@endcan</td>
	</tr>

@if ($customer->type != '法人')
	<tr>
		<th>生年月日</th>
		<td>@can('kengen', [23, 1]){{ $kojinBirthDisp }}@else****/**/**@endcan</td>
		<th>性別</th>
		<td>@can('kengen', [24, 1]){{ $customer->kojin_sex }}@else*****@endcan</td>
		<th>職業</th>
		<td>@can('kengen', [28, 1]){{ $customer->job }}@else*****@endcan</td>
	</tr>
@endif

{{-- 指名担当者 (módulo スタッフ管理) --}}
@if ($staffEnabled ?? false)
	<tr>
		<th>指名担当者</th>
		<td colspan="5">@if (($staffName ?? '') != ''){{ $staffName }}@else&nbsp;@endif</td>
	</tr>
@endif

	<tr>
		<th>紹介者</th>
		<td colspan="5">{{ $customer->shoukaisha_name }}</td>
	</tr>



{{-- 顧客ランク (el legacy imprime <th>/<td> sin <tr>; se conserva igual) --}}
@if (! empty($rankRows))
<th>ランク</th><td colspan="5">
<table style="display: inline-block;">
	<tr>
	<th style="width: auto;">&nbsp;</th>
	<th style="width: auto;">今回 ← 前回</th>
	</tr>
@foreach ($rankRows as $rankRow)
	<tr>
	<th style="width: auto;">{{ $rankRow['title'] }}</th>
	<td><span style="color: #336699; font-weight: bold; font-size: 16px;">{{ $rankRow['now'] }}</span> ← {{ $rankRow['pre'] }}</td>
	</tr>
@endforeach
</table>
@endif
</table>




<table class="tableBox" style="margin-bottom: 10px;">

	<tr>
		<th style="width:87px;">登録年月日</th>
		<td>{{ $epochDisp($customer->touroku_date) }}</td>
		<th style="width:87px;">最終情報更新日</th>
		<td>{{ $epochDisp($customer->update_date) }}</td>
	</tr>


	<tr>
		<th>ログインID</th>
		<td>
		@can('kengen', [1, 1]){{ $customer->login_id }}@else**********@endcan
		</td>
		<th>パスワード</th>
		<td>
		@can('kengen', [2, 1]){{ $customer->login_pass }}@else**********@endcan
		</td>
	</tr>


	<tr>
		<th>端末登録</th>
@if ($customer->felica_no)
		<td ><span style="color: #115599; font-weight: bold;">端末登録有</span>
			<form method="post" name="felica_separate" id="felica_separate" class="form" action="{{ route('admin.clientes.felica.destroy', $customer->cd) }}">
			@csrf
			@method('DELETE')
			<input type="button" name="felica_separate_form" id="felica_separate_form" class="btnStyle" value="関連付けを解除する" onclick="submitChk2();" />
				{{-- POSTデータを全て返す (como el legacy, para volver a la misma ficha) --}}
				@foreach (request()->except(['_token', '_method']) as $key => $val)
				@continue(is_array($val))
				<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
				@endforeach
			<input type="hidden" name="separate_flg" id="separate_flg" value="separate" />
			</form>

		</td>
		<th>カードUID</th>
		<td>{{ $customer->felica_no }}</td>
@else
		<td colspan="3">端末登録無</td>
@endif
	</tr>

</table>
</div>

@include('admin.clientes.tabs.tab2')

@include('admin.clientes.tabs.tab3')

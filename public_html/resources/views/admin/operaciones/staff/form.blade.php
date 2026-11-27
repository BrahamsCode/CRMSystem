{{-- Legacy: system/category5/staff/staff_entry.php (スタッフ新規登録 / スタッフ登録情報 修正) y la rama con
     errores de staff_entry_confirm.php (mismo formulario con class="Ng" y mensajes de LegacyCheck).
     Los valores de $data están en crudo: se escapan al pintarlos.
     En lugar de category5/staff/js/staff_entry.js (URL relativas inc/upload.php e inc/disp_pic.php) la subida
     de la foto se configura aquí con las rutas de Laravel; mismo comportamiento (AjaxUpload, 画像解除).
     Variables: $data, $act ('new'|'up'), $staff, $isAdminAccount, $check (LegacyCheck), $imageUrl, $shops,
       $groups, $hasHomepage, $belongposts, $employmentSystems, $officialPositions --}}
@extends('layouts.legacy')

@php
    $pageTitle = $act === 'new' ? 'スタッフ新規登録' : 'スタッフ登録情報 修正';
    $isConfirm = $check->fails();
    $tip = fn (int $n) => \App\Support\LegacyHtml::tooltip($n);
    $v = fn (string $key) => $data[$key] ?? '';
@endphp

@section('menu', 'operaciones')

@section('title', $pageTitle.' - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
// <body onLoad="document.forms[0].name_kana.focus();"> del legacy (el layout no admite atributos en <body>)
$(window).on('load', function() { document.form1.name_kana.focus(); });
</script>
<script type="text/javascript" src="/system/js/ajaxupload-3.5.js"></script>
<script type="text/javascript" src="/system/js/jquery.maskedinput-1.2.2.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$("#zip").mask("9999999",{placeholder:""});
	$("#zip_search").click(function() {
		$("#add").attr("value","");
		zip = $("#zip").attr("value");
		$.getJSON("/system/category5/staff/zip.php",{
						zip: zip
		}, change_addr)
	});

	// スタッフ写真 (staff_entry.js + inc/upload.php + inc/disp_pic.php)
	new AjaxUpload('#photo', {
		action: '{{ route('admin.operaciones.staff.photo.upload') }}',
		name: 'userfile',
		onSubmit : function(file , ext){
			if (ext && /^(jpg|png|jpeg|gif)$/i.test(ext)){
				this.setData({'_token': '{{ csrf_token() }}'});
			} else {
				alert ('jpg png jpeg gif以外のファイルをアップロード出来ません');
				return false;
			}
		},
		onComplete : function(file, response){
			response = $.trim(String(response).replace(/<[^>]*>/g, ''));
			if (/^[0-9]+$/.test(response)) {
				$("#gazou").html('<br/><br/><img src="{{ url('/admin/operaciones/staff/photo') }}/' + response + '" /><br/><br/>');
				$("#gazou").append('<a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像解除</a>');
				$("#pic_name").val(response);
			} else {
				alert('画像サイズが大きすぎます。2MB以下推奨です。');
				$("#gazou").html('<br><br><br><br><br><br>');
			}
		}
	});
});
function change_addr(data) {
		$("#pref").attr("value",data.zip_name);
		$("#add").attr("value",data.town_name);
		$("#add_kana").attr("value",data.town_name_kana);
}
function deletePic() {
	var conf = confirm("削除してもよろしいですか？\n\r※【この内容で確認する】ボタンをクリックしてから反映されます。");
	if (conf) {
		$("#pic_name").val("delete");
		$("#gazou").html('<img src="/system/img/no_photo.gif" alt="No Photo" class="mB10" />');
	}
}
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $pageTitle }}@if ($isConfirm) 確認@endif</p>
<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.operaciones.staff.confirm') }}">
@csrf
<fieldset>
<legend>スタッフ情報1</legend>
<table class="tableBox">
	<tr>
@php
    $singleShop = $shops->count() === 1;
    $columns = $singleShop ? ' colspan="3"' : '';
    $dispClass = $singleShop ? ' class="displayNone"' : '';
    $displayId = sprintf('%06d', (int) $v('id'));
@endphp
		<th><label for="id">スタッフID</label></th>
		<td{!! $columns !!}{!! $check->ng('id') !!}>{{ $displayId }}<input type="hidden" name="id" id="id" class="inputS" value="{{ $displayId }}" style="ime-mode:disabled" />{!! $check->messages('id') !!}</td>
		<th{!! $dispClass !!}><label for="shop_cd">店舗<em>※</em></label></th>
		<td{!! $dispClass !!}{!! $check->ng('shop_cd') !!}>
@can('nucleo.master')
		<select name="shop_cd">
@foreach ($shops as $cd => $name)
		<option value="{{ $cd }}" @selected((string) $cd === $v('shop_cd'))>{{ $name }}</option>
@endforeach
		</select>{!! $check->messages('shop_cd') !!}
@else
		{{ auth('admin')->user()->shop?->kaishamei }}
		<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $v('shop_cd') }}" />
@endcan
		</td>
		<th style="width:20%;text-align:center;">スタッフ写真</th>
	</tr>
	<tr>
		<th><label for="name_kana"><small>名前(カナ)</small></label></th>
		<td colspan="3"{!! $check->ng('name_kana') !!}><input type="text" name="name_kana" id="name_kana" class="inputL inputSmall" value="{{ $v('name_kana') }}" />
		{{ $tip(4) }}{!! $check->messages('name_kana') !!}
		</td>
		<td rowspan="9" class="textCenter" valign="top" style="width:20%;text-align:center;">
		<div id="gazou" style="text-align:center;">@if ($imageUrl)<img src="{{ $imageUrl }}" alt="スタッフ写真" class="mB10" /><a style="margin-left: 5px; color: #EE0000; text-decoration: underline;" onclick="deletePic();">画像解除</a>@else<img src="/system/img/no_photo.gif" alt="No Photo" class="mB10" />@endif</div>
		<p id="photo">&nbsp;</p><input type="hidden" name="pic_name" id="pic_name" value="{{ $v('pic_name') }}" />
		{{ $tip(13) }}
		</td>
	</tr>
	<tr>
		<th><label for="name">名前<em>※</em></label></th>
		<td colspan="3"{!! $check->ng('name') !!}><input type="text" name="name" id="name" class="inputL" value="{{ $v('name') }}" />
		{{ $tip(3) }}{!! $check->messages('name') !!}
		</td>
	</tr>
	<tr>
		<th><label for="sex1">性別</label></th>
		<td>
		<ul class="checkBtn">
		<li><input type="radio" name="sex" id="sex1" value="男性" @checked($v('sex') === '男性') /><label for="sex1">男性</label></li>
		<li><input type="radio" name="sex" id="sex2" value="女性" @checked($v('sex') === '女性') /><label for="sex2">女性</label></li>
		</ul>
		</td>
		<th>血液型</th>
		<td>
		<select name="bloodtype" id="bloodtype" class="selectBox">
		<option value="">&nbsp;</option>
@foreach (['A', 'B', 'O', 'AB'] as $type)
		<option value="{{ $type }}" @selected($v('bloodtype') === $type)>{{ $type }}</option>
@endforeach
		</select>型
		</td>
	</tr>
	<tr>
		<th><label for="birthday">生年月日</label></th>
		<td{!! $check->ng('birthday') !!}><input type="text" name="birthday" id="birthday" class="inputM" value="{{ $v('birthday') }}" style="ime-mode:disabled" />
		{{ $tip(1) }}{!! $check->messages('birthday') !!}
		</td>
		<th><label for="age">年齢</label></th>
		<td{!! $check->ng('age') !!}><input type="text" name="age" id="age" class="input3S" value="{{ $v('age') }}" style="ime-mode:disabled" />歳
		{{ $tip(0) }}{!! $check->messages('age') !!}
		</td>
	</tr>
	<tr>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="3"{!! $check->ng('zip') !!}><input type="text" name="zip" id="zip" class="inputM" value="{{ $v('zip') }}" style="ime-mode:disabled" />		   <input type="button" name="zip_search" id="zip_search" value="住所検索" />
		{{ $tip(0) }}{!! $check->messages('zip') !!}
		</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="3"><select name="pref" id="pref" class="selectBox">
		{{ \App\Support\LegacyHtml::prefOptions($v('pref')) }}
		</select></td>
	</tr>
	<tr>
		<th><label for="add_kana"><small>市区町村(カナ)</small></label></th>
		<td colspan="3"{!! $check->ng('add_kana') !!}><input type="text" name="add_kana" id="add_kana" class="writeArea inputSmall" value="{{ $v('add_kana') }}" />
		{{ $tip(41) }}{!! $check->messages('add_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="3"><input type="text" name="add" id="add" class="writeArea" value="{{ $v('add') }}" />
		{{ $tip(40) }}
		</td>
	</tr>
	<tr>
		<th><label for="add_number">丁目・番地</label></th>
		<td colspan="3">
		<input type="text" name="add_number" id="add_number" class="inputL" value="{{ $v('add_number') }}" />
		{{ $tip(2) }}
		</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>マンション・ビル名(カナ)</small></label></th>
		<td colspan="4"{!! $check->ng('add_build_kana') !!}><input type="text" name="add_build_kana" id="add_build_kana" class="writeArea inputSmall" value="{{ $v('add_build_kana') }}" />
		{{ $tip(11) }}{!! $check->messages('add_build_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_build">マンション・ビル名</label></th>
		<td colspan="4"><input type="text" name="add_build" id="add_build" class="writeArea" value="{{ $v('add_build') }}" />
		{{ $tip(10) }}
		</td>
	</tr>
	<tr>
		<th><label for="email1">メールアドレス1</label></th>
		<td{!! $check->ng('email1') !!}><input type="text" name="email1" id="email1" class="inputL" value="{{ $v('email1') }}" style="ime-mode:disabled" />{!! $check->messages('email1') !!}</td>
		<th><label for="email2">メールアドレス2</label></th>
		<td colspan="2"{!! $check->ng('email2') !!}><input type="text" name="email2" id="email2" class="inputL" value="{{ $v('email2') }}" style="ime-mode:disabled" />{!! $check->messages('email2') !!}</td>
	</tr>
	<tr>
		<th><label for="mmail1">メールアドレス(携帯1)</label></th>
		<td{!! $check->ng('mmail1') !!}><input type="text" name="mmail1" id="mmail1" class="inputL" value="{{ $v('mmail1') }}" style="ime-mode:disabled" />{!! $check->messages('mmail1') !!}</td>
		<th><label for="mmail2">メールアドレス(携帯2)</label></th>
		<td colspan="2"{!! $check->ng('mmail2') !!}><input type="text" name="mmail2" id="mmail2" class="inputL" value="{{ $v('mmail2') }}" style="ime-mode:disabled" />{!! $check->messages('mmail2') !!}</td>
	</tr>
	<tr>
		<th><label for="tel">電話番号</label></th>
		<td{!! $check->ng('tel') !!}><input type="text" name="tel" id="tel" class="inputM" value="{{ $v('tel') }}" style="ime-mode:disabled" />
		{{ $tip(0) }}{!! $check->messages('tel') !!}
		</td>
		<th><label for="fax">ファックス番号</label></th>
		<td colspan="2"{!! $check->ng('fax') !!}><input type="text" name="fax" id="fax" class="inputM" value="{{ $v('fax') }}" style="ime-mode:disabled" />
		{{ $tip(0) }}{!! $check->messages('fax') !!}
		</td>
	</tr>
	<tr>
		<th><label for="mtel1">携帯電話番号1</label></th>
		<td{!! $check->ng('mtel1') !!}><input type="text" name="mtel1" id="mtel1" class="inputM" value="{{ $v('mtel1') }}" style="ime-mode:disabled" />		{{ $tip(0) }}{!! $check->messages('mtel1') !!}
		</td>
		<th><label for="mtel2">携帯電話番号2</label></th>
		<td colspan="2"{!! $check->ng('mtel2') !!}><input type="text" name="mtel2" id="mtel2" class="inputM" value="{{ $v('mtel2') }}" style="ime-mode:disabled" />		{{ $tip(0) }}{!! $check->messages('mtel2') !!}
		</td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>スタッフ情報2</legend>
<table class="tableBox">
	<tr>
		<th><label for="entranceday">入社日</label></th>
		<td{!! $check->ng('entranceday') !!}><input type="text" name="entranceday" id="entranceday" class="inputM" value="{{ $v('entranceday') }}" style="ime-mode:disabled" />
		{{ $tip(1) }}{!! $check->messages('entranceday') !!}
		</td>
		<th><label for="retirementday">退社日</label></th>
		<td{!! $check->ng('retirementday') !!}><input type="text" name="retirementday" id="retirementday" class="inputM" value="{{ $v('retirementday') }}" style="ime-mode:disabled" />
		{{ $tip(1) }}{!! $check->messages('retirementday') !!}
		</td>
	</tr>
	<tr>
		<th><label for="belongpost">所属部署</label></th>
		<td colspan="3">
		<select name="belongpost" id="belongpost" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼所属部署選択', 'options' => $belongposts, 'selected' => $v('belongpost')])
		</select>
		<input type="text" name="belongpost1" id="belongpost1" class="inputL" value="{{ $v('belongpost1') }}" />
		{{ $tip(50) }}
		</td>
	</tr>
	<tr>
		<th>雇用形態</th>
		<td>
		<select name="employmentsystem" id="employmentsystem" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼雇用形態選択', 'options' => $employmentSystems, 'selected' => $v('employmentsystem')])
		</select></td>
		<th>役職</th>
		<td>
		<select name="officialposition" id="officialposition" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼役職選択', 'options' => $officialPositions, 'selected' => $v('officialposition')])
		</select></td>
	</tr>
	<tr>
		<th><label for="salary">給料単価</label></th>
		<td{!! $check->ng('salary') !!}><input type="text" name="salary" id="salary" class="inputM" value="{{ $v('salary') }}" style="ime-mode:disabled" />円
		{{ $tip(0) }}{!! $check->messages('salary') !!}
		</td>
		<th><label for="paidholiday">有給日数</label></th>
		<td{!! $check->ng('paidholiday') !!}><input type="text" name="paidholiday" id="paidholiday" class="inputS" value="{{ $v('paidholiday') }}" style="ime-mode:disabled" />日
		{{ $tip(0) }}{!! $check->messages('paidholiday') !!}
		</td>
	</tr>
	<tr>
		<th><label for="kingaku_buai">歩合</label></th>
		<td colspan="3"><input type="text" name="kingaku_buai" id="kingaku_buai" class="inputM" value="{{ $v('kingaku_buai') }}" style="ime-mode:disabled" />%</td>
	</tr>
	<tr>
		<th>指名許諾</th>
		<td colspan="3">
		<ul>
		<li><label for="reserve_flg1"><input type="radio" name="reserve_flg" id="reserve_flg1" class="checkBtn" value="0" @checked($v('reserve_flg') !== '1') />受けない</label></li>
		<li><label for="reserve_flg2"><input type="radio" name="reserve_flg" id="reserve_flg2" class="checkBtn" value="1" @checked($v('reserve_flg') === '1') />受ける</label></li>
		</ul>
		</td>
	</tr>
	<tr>
		<th>共通アカウント</th>
		<td>
		<ul>
		<li><label for="common_flg1"><input type="radio" name="common_flg" id="common_flg1" class="checkBtn" value="" @checked($v('common_flg') !== '1') />しない</label></li>
		<li><label for="common_flg2"><input type="radio" name="common_flg" id="common_flg2" class="checkBtn" value="1" @checked($v('common_flg') === '1') />する</label></li>
		</ul>
		</td>
		<th><label for="rank">表示優先度（並び順）</label></th>
		<td{!! $check->ng('rank') !!}><input type="text" name="rank" id="rank" class="inputS" value="{{ $v('rank') }}" style="ime-mode:disabled" />
		{{ $tip(0) }}{!! $check->messages('rank') !!}
		</td>
	</tr>
	<tr>
		<th><label for="remarks">備考欄</label></th>
		<td colspan="3"><textarea name="remarks" id="remarks" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ $v('remarks') }}</textarea></td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>交通費情報</legend>
<table class="tableBox">
	<tr>
		<th><label for="near_station1">最寄り駅</label></th>
		<td colspan="3"{!! $check->ng('near_station4') !!}>
		<ul class="textBox">
		<li><input type="text" name="near_station1" id="near_station1" class="inputM" value="{{ $v('near_station1') }}" />線</li>
		<li><input type="text" name="near_station2" id="near_station2" class="inputM" value="{{ $v('near_station2') }}" />駅から</li>
		<li>
		<select name="near_station3" id="near_station3">
		<option value="">&nbsp;</option>
@foreach (['徒歩', '自転車', 'バス'] as $way)
		<option @selected($v('near_station3') === $way)>{{ $way }}</option>
@endforeach
		</select>
		</li>
		<li><input type="text" name="near_station4" id="near_station4" class="input3S" value="{{ $v('near_station4') }}" style="ime-mode:disabled" />分</li>
		</ul>{!! $check->messages('near_station4') !!}</td>
	</tr>
	<tr>
		<th><label for="traf_transport">交通機関</label></th>
		<td colspan="3"><input type="text" name="traf_transport" id="traf_transport" class="writeArea" value="{{ $v('traf_transport') }}" /></td>
	</tr>
	<tr>
		<th><label for="traf_section1_1">区間</label></th>
		<td colspan="3"><input type="text" name="traf_section1_1" id="traf_section1_1" class="inputM" value="{{ $v('traf_section1_1') }}" />
		～
		<input type="text" name="traf_section1_2" id="traf_section1_2" class="inputM mL5" value="{{ $v('traf_section1_2') }}" /></td>
	</tr>
	<tr>
		<th><label for="traf_charge">往復料金</label></th>
		<td{!! $check->ng('traf_charge') !!}><input type="text" name="traf_charge" id="traf_charge" class="inputM" value="{{ $v('traf_charge') }}" style="ime-mode:disabled" />
		円
		{{ $tip(0) }}{!! $check->messages('traf_charge') !!}
		</td>
		<th><label for="traf_regularfee">通勤定期代</label></th>
		<td{!! $check->ng('traf_regularfee') !!}><input type="text" name="traf_regularfee" id="traf_regularfee" class="inputM" value="{{ $v('traf_regularfee') }}" style="ime-mode:disabled" />
		円
		{{ $tip(0) }}{!! $check->messages('traf_regularfee') !!}
		</td>
	</tr>
	<tr>
		<th><label for="traf_remarks">交通費備考欄</label></th>
		<td colspan="3"><textarea name="traf_remarks" id="traf_remarks" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ $v('traf_remarks') }}</textarea></td>
	</tr>
</table>
</fieldset>

@if ($hasHomepage)
<fieldset>
<legend>ホームページ情報</legend>
<table class="tableBox">
	<tr>
		<th>ホームページ表示</th>
		<td colspan="4">
		<ul>
		<li><label for="hyouji_flg1"><input type="radio" name="hyouji_flg" id="hyouji_flg1" class="checkBtn" value="1" @checked($v('hyouji_flg') === '1') />表示する</label></li>
		<li><label for="hyouji_flg2"><input type="radio" name="hyouji_flg" id="hyouji_flg2" class="checkBtn" value="9" @checked($v('hyouji_flg') === '9') />表示しない</label></li>
		</ul>
		</td>
	</tr>
	<tr>
		<th>写真表示</th>
		<td colspan="4">
		<ul>
		<li><label for="photo_hyouji_flg1"><input type="radio" name="photo_hyouji_flg" id="photo_hyouji_flg1" class="checkBtn" value="1" @checked($v('photo_hyouji_flg') === '1') />表示する</label></li>
		<li><label for="photo_hyouji_flg2"><input type="radio" name="photo_hyouji_flg" id="photo_hyouji_flg2" class="checkBtn" value="9" @checked($v('photo_hyouji_flg') === '9') />表示しない</label></li>
		</ul>
		</td>
	</tr>
	<tr>
		<th><label for="comment">コメント</label></th>
		<td colspan="4"><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ $v('comment') }}</textarea></td>
	</tr>
</table>
</fieldset>
@endif

<fieldset>
<legend>ログイン</legend>
<table class="tableBox">
	<tr>
	<th>ログインID</th>
	<td>@if ($isAdminAccount)管理者用ID@else{{ $displayId }}@endif</td>
	</tr>
	<tr>
	<th>パスワード</th>
	<td{!! $check->ng('login_pass') !!}>@if ($isAdminAccount)管理者用パスワード<input type="hidden" name="login_pass" id="login_pass" value="dummy" />@else<input type="text" name="login_pass" id="login_pass" class="inputL" value="{{ $v('login_pass') }}" style="ime-mode:disabled" />@endif{!! $check->messages('login_pass') !!}</td>
	</tr>
@unless ($isAdminAccount)
	<tr>
		<th>グループ</th>
		<td>
			<select name="tb_staff_group_cd" id="tb_staff_group_cd">
			<option value="">▼グループ選択</option>
@foreach ($groups as $cd => $name)
			<option value="{{ $cd }}" @selected((string) $cd === $v('tb_staff_group_cd'))>{{ $name }}</option>
@endforeach
			</select>
		</td>
	</tr>
@endunless
</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $staff?->cd }}">
<input type="hidden" name="act" id="act" class="btn" value="{{ $act }}">
<input type="submit" name="enter" id="enter" class="btnStyleM" value="この内容で確認する" />
</div>
</form>

<!-- / .contents --></div>
@endsection

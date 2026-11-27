{{-- Legacy: system/category5/staff/staff_detail.php (スタッフ登録情報).
     tb_staff se guarda con get_form2 (ya escapado): los valores se pintan tal cual, como el legacy.
     Variables:
       $staff       Staff (también borrado)
       $tab         '' | '2' | '3' | '4'
       $hasHomepage hay pestaña ホームページ情報
       $shopName    nombre de su tienda
       $photoUrl    URL de la foto o null
       $back        act/page/kensu para volver a la lista --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ登録情報 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
<script type="text/javascript"><!--
function submitChk () {
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");
	return flg;
}
function submitChk2 () {
	var flg2 = confirm ( "カード関連付けを解除してもよろしいですか？\n\n解除しない場合は['キャンセル']ボタンを押して下さい");
	return flg2;
}
--></script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■スタッフ登録情報</p>

<div class="form">
@php
    // El legacy pinta en gris las pestañas que NO están activas
    $tabStyle = fn (string $t) => $tab !== $t ? ' style="background-color: #A2AEB3"' : '';
    $tabUrl = fn (string $t) => route('admin.operaciones.staff.show', ['cd' => $staff->cd] + $back + ($t === '' ? [] : ['tab' => $t]));
@endphp
<p class="tabHeadline"{!! $tabStyle('') !!}><a href="{{ $tabUrl('') }}">スタッフ情報1</a></p><!--
--><p class="tabHeadline"{!! $tabStyle('2') !!}><a href="{{ $tabUrl('2') }}">スタッフ情報2</a></p><!--
--><p class="tabHeadline"{!! $tabStyle('3') !!}><a href="{{ $tabUrl('3') }}">交通費情報</a></p>@if ($hasHomepage)<!--
--><p class="tabHeadline"{!! $tabStyle('4') !!}><a href="{{ $tabUrl('4') }}">ホームページ情報</a></p>
@endif

<div class="tabBody">
@if ($tab === '')
<table class="tableBox">
	<tr>
		<th>スタッフID</th>
		<td>{!! $staff->id !!}</td>
		<th>店舗</th>
		<td>{!! $shopName !!}</td>
		<th>スタッフ写真</th>
	</tr>
	<tr>
		<th>名前</th>
		<td colspan="3"><span title="{!! $staff->name_kana !!}">{!! $staff->name !!}</span></td>
		<td rowspan="6" class="textCenter" valign="top">
		<div id="gazou">@if ($photoUrl === null)<img src="/system/img/no_photo.gif" alt="No Photo" class="mB10" />@else<img src="{{ $photoUrl }}" alt="スタッフ写真" class="mB10" />@endif</div>
		</td>
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
		<td>{{ $staff->age }}</td>
	</tr>
	<tr>
		<th>住所</th>
		<td colspan="3">{!! $staff->zip !!}<br />
		{!! $staff->pref !!}
		<span title="{!! $staff->add_kana !!}">{!! $staff->add !!}</span>
		{!! $staff->add_number !!}
		<span title="{!! $staff->add_build_kana !!}">{!! $staff->add_build !!}</span>
		</td>
	</tr>
	<tr>
		<th>メールアドレス1</th>
		<td colspan="3">
		<ul class="bulletPoint">
		<li>登録アドレス ： {!! $staff->email1 !!}</li>
		<li>サブアドレス ： {!! $staff->email2 !!}</li>
		<li>携帯電話 ： {!! $staff->mmail1 !!}</li>
		<li>携帯電話 ： {!! $staff->mmail2 !!}</li>
		</ul>
		</td>
	</tr>
	<tr>
		<th>連絡先</th>
		<td colspan="3">
		<ul class="bulletPoint">
		<li>電話番号 ： {!! $staff->tel !!}</li>
		<li>ファックス番号 ： {!! $staff->fax !!}</li>
		<li>携帯電話番号1 ： {!! $staff->mtel1 !!}</li>
		<li>携帯電話番号2 ： {!! $staff->mtel2 !!}</li>
		</ul>
		</td>
	</tr>
</table>
@elseif ($tab === '2')
<table class="tableBox">
	<tr>
		<th><label for="entranceday">入社日</label></th>
		<td width="30%">{!! $staff->entranceday !!}</td>
		<th><label for="retirementday">退社日</label></th>
		<td>{!! $staff->retirementday !!}</td>
	</tr>
	<tr>
		<th><label for="belongpost">所属部署</label></th>
		<td colspan="3">{!! $staff->belongpost !!}</td>
	</tr>
	<tr>
		<th>雇用形態</th>
		<td>{!! $staff->employmentsystem !!}</td>
		<th>役職</th>
		<td>{!! $staff->officialposition !!}</td>
	</tr>
	<tr>
		<th><label for="salary">給料単価</label></th>
		<td>{!! $staff->salary !!}</td>
		<th><label for="paidholiday">有給日数</label></th>
		<td>{!! $staff->paidholiday !!}</td>
	</tr>
	<tr>
		<th>歩合</th>
		<td colspan="3">{{ (float) $staff->kingaku_buai }}%</td>
	</tr>
	<tr>
		<th>カード関連付け</th>
@if ($staff->felica_no)
		<td>割り当て済み
			<form method="post" name="felica_separate" id="felica_separate" class="form" action="{{ route('admin.operaciones.staff.card.destroy', $staff->cd) }}" onsubmit="return submitChk2()">
			@csrf
			@method('DELETE')
			<input type="submit" name="felica_separate_form" id="felica_separate_form" value="関連付けを解除する" />
			</form>
		</td>
		<th>カードUID</th>
		<td>{!! $staff->felica_no !!}</td>
@else
		<td colspan="3">未割り当て</td>
@endif
	</tr>
	<tr>
		<th>指名許諾</th>
		<td colspan="3">{{ $staff->reserve_flg == '1' ? '受ける' : '受けない' }}</td>
	</tr>
	<tr>
		<th>共通アカウント</th>
		<td>{{ $staff->common_flg == '1' ? 'する' : 'しない' }}</td>
		<th>表示優先度（並び順）</th>
		<td>{{ $staff->rank }}</td>
	</tr>
	<tr class="tableBox">
		<th><label for="remarks">備考欄</label></th>
		<td colspan="3">{!! $staff->remarks !!}</td>
	</tr>
</table>
@elseif ($tab === '3')
<table class="tableBox">
	<tr>
		<th><label for="near_station1">最寄り駅</label></th>
		<td colspan="3">
		<ul class="textBox">
		<li>{!! $staff->near_station1 !!}線</li>
		<li>{!! $staff->near_station2 !!}駅から</li>
		<li>{!! $staff->near_station3 !!}</li>
		<li>{!! $staff->near_station4 !!}分</li>
		</ul></td>
	</tr>
	<tr>
		<th><label for="traf_transport">交通機関</label></th>
		<td colspan="3">{!! $staff->traf_transport !!}</td>
	</tr>
	<tr>
		<th><label for="traf_section1_1">区間</label></th>
		<td colspan="3">{!! $staff->traf_section1_1 !!}
		～
		{!! $staff->traf_section1_2 !!}</td>
	</tr>
	<tr>
		<th><label for="traf_charge">往復料金</label></th>
		<td>{!! $staff->traf_charge !!}
		円</td>
		<th><label for="traf_regularfee">通勤定期代</label></th>
		<td>{!! $staff->traf_regularfee !!}
		円</td>
	</tr>
	<tr>
		<th><label for="traf_remarks">交通費備考欄</label></th>
		<td colspan="3">{!! $staff->traf_remarks !!}</td>
	</tr>
</table>
@elseif ($tab === '4')
<table class="tableBox">
	<tr>
		<th>ホームページ表示</th>
		<td colspan="4">{{ $staff->hyouji_flg == '1' ? '表示する' : '表示しない' }}</td>
	</tr>
	<tr>
		<th>写真表示</th>
		<td colspan="4">
			{{ $staff->photo_hyouji_flg == '1' ? '表示する' : '表示しない' }}
		</td>
	</tr>
	<tr>
		<th><label for="comment">コメント</label></th>
		<td colspan="4">{!! nl2br((string) $staff->comment) !!}</td>
	</tr>
</table>
@endif
</div>

<!-- / .form --></div>

<form method="get" name="send_staff_list" id="send_staff_list" class="form mT10" action="{{ route('admin.operaciones.staff.index') }}#result">
<input type="submit" id="send_staff_list_btn" class="btn backBtnStyle" value="戻る" />
@foreach ($back as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
</form>

<div class="btnBox">
@if (! $staff->isLegacyDeleted())
@can('kengen.1024.3')
<form method="get" name="form1" id="form1" class="form" action="{{ url('/admin/operaciones/staff/'.$staff->cd.'/edit') }}">
<input type="submit" id="act" class="btnStyle" value="修正する" />
</form>
@endcan
@endif
@can('kengen.1024.4')
@if ($staff->cd !== 1)
@if ($staff->isLegacyDeleted())
<form method="get" name="form2" id="form2" class="form" action="{{ route('admin.operaciones.staff.restore-confirm', $staff->cd) }}">
<input type="submit" id="delete" class="btnStyle" value="削除を取り消す">
</form>
@else
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.operaciones.staff.destroy', $staff->cd) }}" onsubmit="return submitChk()">
@csrf
@method('DELETE')
<input type="submit" name="delete" id="delete" class="btnStyle" value="削除する">
</form>
@endif
@endif
@endcan
</div>

<!-- / .contents --></div>
@endsection

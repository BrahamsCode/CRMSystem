{{--
    求人情報 詳細 (recruit_detail.php, $mode = 'detail') y 完了 (recruit_complete.php, $mode = 'complete'):
    misma ficha con otros botones.

    CONTRATO DE DATOS
        $mode          'detail' | 'complete'
        $recruit       Recruit|null   (el legacy muestra la ficha vacía si el cd no existe)
        $registerType  string|null    'new' | 'update' (complete): 'new' añade 「続けて新規登録する」
        $shopName      string|null    kaishamei de la tienda; null = 全店舗 (tb_shop_cd = -1)
        $categoryName  string         shokushu del 募集職種
        $canEdit       bool           cKengen 1023 nivel 3 o master (修正する)
        $canDelete     bool           cKengen 1023 nivel 4 o master (削除する)
        $service       RecruitService (imageUrl)
        $siteName      string         (MypageServiceProvider)
    En 完了 el título es siempre 「修正」 (el legacy llega con act=up tanto en alta como en modificación).
    En 詳細 el legacy titulaba 「新規登録」 ($act sin 'up'); aquí 「詳細」, como el h2.
--}}
@extends('layouts.legacy')

@section('title', '求人情報 ' . ($mode === 'detail' ? '詳細' : '修正') . ' - ' . $siteName)

@section('menu', 'mypage')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".ac3").accordion('option', 'active', 0);
});
</script>
@if ($mode === 'detail')

<script type="text/javascript"><!--
function submitChk () {
    var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}

--></script>
@endif
@endsection

@section('content')
@php
    $isDetail = $mode === 'detail';
    $field = fn (string $name) => (string) ($recruit?->{$name} ?? '');
    $multiline = fn (string $name) => nl2br(e($field($name)));
    $photo = function (int $n) use ($recruit, $service) {
        if ($recruit !== null && $recruit->{'pic_flg' . $n} === '1') {
            return '<img src="' . e($service->imageUrl($recruit->cd, $n)) . '" alt="' . ($n === 1 ? '求人情報写真１' : '求人情報写真２') . '" class="mB10" />';
        }

        return '<img src="' . e(asset('legacy/system/img/no_photo.gif')) . '" alt="No Photo" class="mB10" />';
    };
@endphp
<div class="contents">
<p class="h2Title">■求人情報 {{ $isDetail ? '詳細' : '修正' }}</p>

<div class="form">
<fieldset>
<legend>求人情報</legend>

<table class="tableBox">

	<tr>
		<th>募集見出し</th>
		<td>{{ $field('boshu_headline') }}</td>
	</tr>

	<tr>
		<th>店舗</th>
		<td>{{ $shopName ?? '全店舗' }}</td>
	</tr>

	<tr>
		<th>募集職種</th>
		<td>{{ $categoryName }}</td>
	</tr>

	<tr>
		<th>仕事内容</th>
		<td>{!! $multiline('shigoto_naiyou') !!}</td>
	</tr>

	<tr>
		<th>雇用形態</th>
		<td>{!! $multiline('koyou_keitai') !!}</td>
	</tr>

	<tr>
		<th>応募資格</th>
		<td>{!! $multiline('oubo_shikaku') !!}</td>
	</tr>

	<tr>
		<th>勤務地</th>
		<td>{!! $multiline('kinmu_chi') !!}</td>
	</tr>

	<tr>
		<th>勤務時間</th>
		<td>{!! $multiline('kinmu_jikan') !!}</td>
	</tr>

	<tr>
		<th>給与</th>
		<td>{!! $multiline('kyuyo') !!}</td>
	</tr>

	<tr>
		<th>休日・休暇</th>
		<td>{!! $multiline('kyuka') !!}</td>
	</tr>

	<tr>
		<th>待遇・福利厚生</th>
		<td>{!! $multiline('taigu') !!}</td>
	</tr>

	<tr>
		<th>画像</th>
		<td class="textCenter" valign="top">
		@if ($isDetail)
		<div align="left">
			{!! $photo(1) !!}
			<p>画像１</p>
			<br /><br />
			{!! $photo(2) !!}
			<p>画像２</p>
		</div>
		@else
		<div align="center">
			<div id="gazou">{!! $photo(1) !!}</div>
			<p>画像１</p>
		</div>
		<div align="center">
			<div id="gazou2">{!! $photo(2) !!}</div>
			<p>画像２</p>
		</div>
		@endif
		</td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>会社概要</legend>

<table class="tableBox">

	<tr>
		<th>名称</th>
		<td>{{ $field('kaisha_name') }}</td>
	</tr>

	<tr>
		<th>所在地</th>
		<td>{!! $multiline('add') !!}</td>
	</tr>

	<tr>
		<th>電話番号</th>
		<td>{{ $field('tel') }}</td>
	</tr>

	<tr>
		<th>ファックス番号</th>
		<td>{{ $field('fax') }}</td>
	</tr>

	<tr>
		<th>事業内容</th>
		<td>{!! $multiline('jigyou_naiyou') !!}</td>
	</tr>

</table>
</fieldset>

<fieldset>
<legend>応募について</legend>

<table class="tableBox">

	<tr>
		<th>応募方法</th>
		<td>{!! $multiline('oubo_houhou') !!}</td>
	</tr>

	<tr>
		<th>面接地</th>
		<td>{!! $multiline('mensetsuchi') !!}</td>
	</tr>

	<tr>
		<th>担当</th>
		<td>{{ $field('tantou') }}</td>
	</tr>

</table>

</fieldset>

@if ($isDetail)
<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ route('admin.mypage.recruits.index') }}">
@csrf
<input type="image" name="reserve_list_btn" id="reserve_list_btn" src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" value="戻る" />
</form>


<div class="btnBox">

@if ($canEdit && $recruit !== null)
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.mypage.recruits.edit', ['cd' => $recruit->cd]) }}">
@csrf
<input type="hidden" name="cd" id="cd" value="{{ $recruit->cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="修正する" />
</form>
@endif

@if ($canDelete && $recruit !== null)
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.mypage.recruits.destroy') }}" onsubmit="return submitChk()">
@csrf
<input type="hidden" name="delete_flg" id="delete_flg" value="del">
<input type="hidden" name="cd" id="cd" value="{{ $recruit->cd }}" />
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>
@endif

</div>
@else
<div class="btnBox">

@if ($registerType === 'new')
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.mypage.recruits.create') }}">
@csrf
<input type="submit" name="enter" id="enter" class="btn" value="続けて新規登録する" />
</form>
@endif

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.mypage.recruits.index') }}">
@csrf
<input type="submit" name="enter" id="enter" class="btn" value="一覧へ" />
<input type="hidden" name="se_flg" id="se_flg" value="2" />
<input type="hidden" name="act" id="act" value="up" />
</form>
</div>
@endif


<!-- / .form --></div>

<!-- / .contents --></div>
@endsection

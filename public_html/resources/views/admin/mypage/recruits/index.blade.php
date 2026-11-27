{{--
    求人情報一覧 (system/category6/recruit/recruit_list.php).

    CONTRATO DE DATOS
        $pager     array   LegacyPageControl::paginate de tb_recruit (disp_flg = '1', tienda de la sesión si no es
                           master, ORDER BY update_date DESC; kensu 10 por defecto). items = Recruit
        $siteName  string  (MypageServiceProvider; el <title> del legacy era fijo 「-総合業務管理システム-」)

    Diferencia con el legacy: kensuSet() enviaba document.form_search, que está dentro de un comentario HTML
    (error de JS: el 表示件数 no hacía nada). Aquí recarga la lista con el kensu elegido.
--}}
@extends('layouts.legacy')

@section('title', '求人情報一覧 -総合業務管理システム-')

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

<script type="text/javascript">
//表示件数の制御

function kensuSet(parts){
	location.href = "{{ route('admin.mypage.recruits.index') }}?page=1&kensu=" + parts.value + "&se_flg=2";
}

</script>
@endsection

@section('content')
@php
    $listUrl = route('admin.mypage.recruits.index');
    $kensu = $pager['kensu'];
    $page = $pager['page'];
@endphp
<div class="contents">
<p class="h2Title">■求人情報一覧</p>
@if ($pager['items']->isNotEmpty())
<div id="form">
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)"><option value="" ></option>
@for ($i = 1; $i <= 6; $i++)
<option value="{{ $i * 5 }}" {{ $kensu == $i * 5 ? 'selected' : '' }}>{{ $i * 5 }}</option>
@endfor
</select></p>
</div>

<p><span class="attention">{{ $pager['total'] }}件</span>の該当結果がありました。</p>

<form method="post" name="form1" id="form1" class="form" action="">
@csrf

<table class="tableBox2">
  <tr>
    <th>タイトル</th>
    <th>担当者</th>
    <th class="btn3">&nbsp;</th>
  </tr>

@foreach ($pager['items'] as $recruit)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $recruit->boshu_headline }}</td>
    <td>{{ $recruit->tantou }}</td>
    <td align="center">
	<ul>
	<li><a href="{{ route('admin.mypage.recruits.show', ['cd' => $recruit->cd, 'page' => $page, 'kensu' => $kensu]) }}"><img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" width="40" height="40" alt="詳細" /></a></li>
	</ul>
    </td>
  </tr>
@endforeach
</table>

@else
求人データはありません。
@endif

</form>

<ul class="btnBox2">
@if ($page != 1 && $kensu < $pager['total'])
<li class="btn"><a href="{{ $listUrl }}?page={{ $page - 1 }}&kensu={{ $kensu }}&se_flg=2"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($pager['maxPage'] > 1)
@for ($l = 1; $l <= $pager['maxPage']; $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $listUrl }}?page={{ $l }}&kensu={{ $kensu }}&se_flg=2">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($pager['maxPage'] > 1 && $page < $pager['maxPage'])
<li class="btn"><a href="{{ $listUrl }}?page={{ $page + 1 }}&kensu={{ $kensu }}&se_flg=2"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>


<!-- / .contents --></div>
@endsection

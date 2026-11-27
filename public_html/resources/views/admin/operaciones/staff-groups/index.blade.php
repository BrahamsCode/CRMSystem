{{-- Legacy: system/category5/staff/staff_group.php + staff_group_frm.php (スタッフグループ設定).
     Una sola pantalla: formulario de alta/edición arriba y lista con 修正・削除 y ▲▼ abajo.
     Variables:
       $group    StaffGroup|null  el grupo en edición (?cd=), o null en alta
       $groups   colección de StaffGroup activos en orden (rank)
       $siteName nombre del sitio para el <title> --}}
@extends('layouts.legacy')

@php($pageTitle = 'スタッフグループ')

@section('menu', 'operaciones')

@section('title', $pageTitle.'管理 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

function submitChk () {

	if(document.form1.name.value == ""){
		alert("{{ $pageTitle }}名が空白です。\n\n{{ $pageTitle }}名をご記入下さい。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}

function submitChk2 (val) {
	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	if(flg){
		element.submit();
	}
}
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $pageTitle }} 登録</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $group ? route('admin.operaciones.staff-groups.update', $group->cd) : route('admin.operaciones.staff-groups.store') }}" onsubmit="return submitChk()">
@csrf
@if ($group)
@method('PUT')
@endif

<fieldset>
<legend>{{ $pageTitle }}情報</legend>

<table class="tableBox">

<tr>
<th>{{ $pageTitle }}名</th>
<td>
<input type="text" name="name" id="name" class="writeArea" value="{{ old('name', $group?->name) }}" />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($group)
<input type="submit" name="enter" id="revise" class="btnStyleM" value="この内容で修正する" />
<input type="hidden" name="cd" value="{{ $group->cd }}" />
@else
<input type="submit" name="enter" id="reivse" class="btnStyleM" value="この内容で登録する" />
@endif
</div>

</form>

<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>
<div class="form">
@if ($groups->isNotEmpty())

<p class="textCenter">{{ $groups->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
	<tr>
		<th>{{ $pageTitle }}名</th>
		<th>修正・削除</th>
		<th>並び</th>
	</tr>

@foreach ($groups as $row)
@php($memCnt = $loop->iteration)
	<tr @class(['tableBg' => $loop->index % 2 != 0])>
	<td>{{ $row->name }}</td>
	<td class="textCenter" width="100">
	<a href="{{ route('admin.operaciones.staff-groups.index', ['cd' => $row->cd]) }}" class="btnStyle">修正</a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;" class="btnStyle">削除</a>

	<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.operaciones.staff-groups.destroy', $row->cd) }}">
	@csrf
	@method('DELETE')
	<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
	<input type="submit" name="delete" class="btn" value="削除する">
	</form>
	</td>

	<td style="width: 3em;">{{ $loop->iteration }}
	<ul style="padding:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
@unless ($loop->first)
	<li><a href="{{ route('admin.operaciones.staff-groups.sort', ['thisCd' => $row->cd, 'kind' => 'up']) }}">▲</a></li>
@else
	&nbsp;
@endunless
@unless ($loop->last)
	<li><a href="{{ route('admin.operaciones.staff-groups.sort', ['thisCd' => $row->cd, 'kind' => 'down']) }}">▼</a></li>
@endunless
	</ul>
	</td>
  </tr>
@endforeach

</table>
@else
{{ $pageTitle }}は登録されていません。
@endif

<ul class="btnBox2">
</ul>

</div>

<!-- / .contents --></div>
@endsection

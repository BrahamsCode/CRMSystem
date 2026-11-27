{{-- Legacy: system/category5/staff/information.php (インフォメーション管理): alta/edición arriba y los 10
     últimos avisos abajo. El master elige tienda (vacío = 全店舗) y puede modificar los de todas las tiendas;
     el resto ve los de su tienda y los de 全店舗, y solo modifica los de su tienda.
     Variables:
       $information        Information|null  el aviso en edición (?cd=)
       $informations       colección de Information (los 10 últimos visibles, orden update DESC)
       $shopNames          [cd => kaishamei] de las tiendas no borradas (para el 配信条件)
       $shopOptions        [cd => kaishamei] para el selector (todas para master, la suya para el resto)
       $isMaster           bool  rank == 'master'
       $currentShop        Shop|null  tienda de la sesión (para el no master)
       $employmentSystems  lista de textos de tb_employmentsystem (orden cd)
       $belongPosts        lista de textos de tb_belongpost (orden cd)
       $officialPositions  lista de textos de tb_officialposition (orden cd)
       $siteName           nombre del sitio para el <title> --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'インフォメーション管理 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

function submitChk () {
	if(document.form1.comment.value == ""){
		alert("インフォメーション内容が空白です。\n\nインフォメーション内容をご記入下さい。");
		return false;
	}else{
		var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
		return flg;
	}
}

function submitChk2 (val) {
	element = eval("document.form_" + val);
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
	if(flg){
		element.submit();
	}
}

$(document).ready(function() {
	$("#all_checkbox").click(function(){
		$(".checkboxes").prop("checked", $("#all_checkbox").prop("checked"));
	});
});
</script>
@endsection

@section('content')
@php
    $selectedPositions = $information?->officialPositions() ?? [];
@endphp
<div class="contents">
<p class="h2Title">■インフォメーション登録</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $information ? route('admin.operaciones.information.update', $information->cd) : route('admin.operaciones.information.store') }}" onsubmit="return submitChk()">
@csrf
@if ($information)
@method('PUT')
@endif

<fieldset>
<legend>同報先検索条件</legend>
<table class="tableBox">
<tr>
<th>店舗</th>
	<td>
@if ($isMaster)
	<select name="shop_cd">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼店舗選択', 'options' => $shopOptions, 'selected' => old('shop_cd', $information?->shop_cd)])
	</select><br />
	※店舗を選択しない場合は、全店舗に配信されます。
@else
	{{ $currentShop?->kaishamei }}
	<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $currentShop?->cd }}">
@endif
</td>
</tr>
<tr>
<th>雇用形態</th>
<td>
		<select name="employmentsystem" id="employmentsystem" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼雇用形態選択', 'options' => $employmentSystems, 'selected' => old('employmentsystem', $information?->employmentsystem)])
		</select>
</td>
</tr>
<tr>
<th>役職</th>
<td>
<ul class="checkBtn">
@foreach ($officialPositions as $i => $position)
<li><input type="checkbox" name="officialposition[]" id="officialposition{{ $i }}" class="checkBtn checkboxes" value="{{ $position }}" @checked(in_array($position, old('officialposition', $selectedPositions), true)) /><label for="officialposition{{ $i }}">{{ $position }}</label></li>
@endforeach
</ul>
		<input type="checkbox" name="all_checkbox" id="all_checkbox" class="checkBtn" value="all"><label for="all_checkbox">全て選択</label>
</td>
</tr>
<tr>
<th>所属部署</th>
<td>
		<select name="belongpost" id="belongpost" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼所属部署選択', 'options' => $belongPosts, 'selected' => old('belongpost', $information?->belongpost)])
		</select>
</td>
</tr>
</table>
</fieldset>

<fieldset>
<legend>配信内容</legend>
<table class="tableBox">
<tr>
<th>作成者</th>
<td>
<input type="text" name="staff_name" id="inputM" class="writeArea" value="{{ old('staff_name', $information?->staff_name) }}" />
</td>
</tr>
<tr>
<th>インフォメーション内容</th>
<td>
<input type="text" name="comment" id="comment" class="writeArea" value="{{ old('comment', $information?->comment) }}" />
</td>
</tr>
</table>
</fieldset>

<div class="btnBox">
@if ($information)
<input type="submit" name="enter" id="revise" class="btnStyleM" value="この内容で修正する" />
<input type="hidden" name="cd" id="cd" value="{{ $information->cd }}" />
@else
<input type="submit" name="enter" id="reivse" class="btnStyleM" value="この内容で登録する" />
@endif
</div>
</form>

<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■インフォメーション一覧</p>
<div class="form">
@if ($informations->isNotEmpty())
<p class="textCenter">最新から{{ $informations->count() }}件</span>の該当結果を表示しています。</p>
<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>インフォメーション内容</th>
    <th>配信条件</th>
    <th>配信者</th>
    <th>修正・削除</th>
  </tr>
@foreach ($informations as $row)
@php
    $memCnt = $loop->iteration;
    $shopName = $shopNames[$row->shop_cd ?? ''] ?? '';
    // 全店舗の aviso solo lo modifica el master
    $canEdit = $shopName !== '' || $isMaster;
@endphp
  <tr @class(['tableBg' => $loop->index % 2 != 0])>
    <td>{{ $row->cd }}</td>
    <td>{{ $row->comment }}</td>
    <td>
{{ $shopName === '' ? '全店舗' : $shopName }}
	@if ($row->belongpost && $row->shop_cd)/ @endif{{ $row->belongpost }}
	@if ($row->employmentsystem && $row->belongpost)/ @endif{{ $row->employmentsystem }}
	@if ($row->officialposition && $row->employmentsystem)/ @endif{{ $row->officialposition }}
    </td>
    <td>{{ $row->staff_name }}</td>
    <td class="textCenter" width="100">
@if ($canEdit)
<a href="{{ route('admin.operaciones.information.index', ['cd' => $row->cd]) }}" class="btnStyle">修正</a>
<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;" class="btnStyle">削除</a>
@endif
<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.operaciones.information.destroy', $row->cd) }}">
@csrf
@method('DELETE')
<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
<input type="submit" name="delete" class="btn" value="削除する">
</form></td>
  </tr>
@endforeach
</table>
@unless ($isMaster)
※全店舗配信の同報は管理者から配信されたものなので、修正・削除は管理者のみが行えます。
@endunless
@else
インフォメーションは登録されていません。
@endif

<ul class="btnBox2">
</ul>
</div>

<!-- / .contents --></div>
@endsection

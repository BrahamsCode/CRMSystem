{{-- Legacy: system/category1/customer/customer_group_category_frm.php (顧客グループ), raiten_douki_frm.php (来店動機)
     y customer_level_category_frm.php (顧客レベル): la misma pantalla con alta/edición arriba y la lista con ▲ abajo.
     Diferencias del legacy por pantalla: 顧客グループ tiene 初期値 (default_flg); 来店動機 no tiene <fieldset>;
     顧客レベル usa botones de imagen y sin clase btnStyleM.
     Variables:
       $screen     'group' | 'motive' | 'level'
       $routeName  prefijo de rutas: admin.clientes.groups | admin.clientes.visit-motives | admin.clientes.levels
                   (+ .store, .update, .destroy, .sort)
       $siteName   nombre del sitio (<title>)
       $item       ?Model fila que se edita (?cd=) o null en el alta: cd, name, default_flg (solo grupo)
       $items      Collection de filas activas (disp_flg = '1') por rank: cd, name, rank, default_flg (solo grupo)
--}}
@php
    $pageTitle = ['group' => '顧客グループ', 'motive' => '来店動機', 'level' => '顧客レベル'][$screen];
    $isGroup = $screen === 'group';
    $isLevel = $screen === 'level';
@endphp
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', $pageTitle . '管理 - ' . $siteName)

@section('js')
<script type="text/javascript">

function submitChk () {

	if(document.form1.name.value == ""){
		alert("{{ $pageTitle }}名が空白です。\n\n{{ $pageTitle }}名をご記入下さい。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
	return flg;
}


function submitChk2 (val) {
	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

	if(flg){
		element.submit();
	}
}

// ▲▼: el legacy cambiaba el orden con un GET (?thisCd=&thisRow=&kind=); ahora POST a {{ $routeName }}.sort
function sortRow (url) {
	var f = document.getElementById("sort_form");
	f.action = url;
	f.submit();
}

</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $pageTitle }} 登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ $item ? route($routeName . '.update', $item->cd) : route($routeName . '.store') }}" onsubmit="return submitChk()">
@csrf
@if ($item)
@method('PUT')
@endif

@if (! ($screen === 'motive'))
<fieldset>
<legend>{{ $pageTitle }}情報</legend>
@endif

<table class="tableBox">

<tr>
@if ($isLevel)
<th>{{ $pageTitle }}名</th>
@else
<th><label for="name">{{ $pageTitle }}名</label></th>
@endif
<td>
<input type="text" name="name" id="name" class="writeArea" value="{{ old('name', $item?->name) }}" />
</td>
</tr>
@if ($isGroup)

<tr>
<th><label for="default_flg">{{ $pageTitle }}初期値</label></th>
<td>
<input type="checkbox" name="default_flg" id="default_flg" class="checkBtn" value="1" @checked(old('default_flg', $item?->default_flg) == '1') /><label for="default_flg">この値を新規顧客の初期値として使用する<span class="attention">(選択できるのは1項目のみで、以前の設定はクリアされます)</span></label>
</td>
</tr>
@endif

</table>

@if (! ($screen === 'motive'))
</fieldset>
@endif

<div class="btnBox">
@if ($item)
<input type="submit" name="enter" id="revise"@unless ($isLevel) class="btnStyleM"@endunless value="この内容で修正する" />
<input type="hidden" name="act"@unless ($isGroup) id="act"@endunless value="up" />
<input type="hidden" name="cd"@unless ($isGroup) id="cd"@endunless value="{{ $item->cd }}" />
@else
<input type="submit" name="enter" id="reivse"@unless ($isLevel) class="btnStyleM"@endunless value="この内容で登録する" />
<input type="hidden" name="act"@unless ($isGroup) id="act"@endunless value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>
<div class="form">
@if ($items->count() > 0)

@if ($isLevel)
<p class="textCenter">{{ $items->count() }}件の該当結果を表示しています。</p>
@else
<p>{{ $items->count() }}件の該当結果を表示しています。<br /></p>
@endif

<table class="tableBox2">
	<tr>
		<th>{{ $pageTitle }}名</th>
@if ($isGroup)
		<th>初期値</th>
@endif
		<th>修正・削除</th>
		<th>並び</th>
	</tr>

@foreach ($items as $row)
@if ($loop->index % 2 != 0)
	<tr class="tableBg">
@else
	<tr>
@endif
	<td>{{ $row->name }}</td>
@if ($isGroup)
	<td width="50" class="attention">@if ($row->default_flg == '1')選択中@else&nbsp;@endif</td>
@endif
	<td class="textCenter" width="{{ $isGroup ? 130 : 100 }}">
@if ($isLevel)
	<a href="{{ route($routeName, ['cd' => $row->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $loop->iteration }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>
@else
	<a href="{{ route($routeName, ['cd' => $row->cd]) }}" class="btnStyle">修正</a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $loop->iteration }}); return false;" class="btnStyle">削除</a>
@endif

	<form method="post" name="form_{{ $loop->iteration }}" id="form_{{ $loop->iteration }}" class="displayNone" action="{{ route($routeName . '.destroy', $row->cd) }}">
	@csrf
	@method('DELETE')
	<input type="hidden" name="act" class="btn" value="del">
	<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
	<input type="submit" name="delete" class="btn" value="削除する">
	</form>
	</td>

	<td style="width: 3em;">{{ $loop->iteration }}
	<ul style="padding:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
	<li><a href="#" onclick="sortRow('{{ route($routeName . '.sort', ['cd' => $row->cd, 'kind' => 'up']) }}'); return false;">▲</a></li>
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

<form method="post" id="sort_form" action="" style="display:none">
@csrf
</form>
@endsection

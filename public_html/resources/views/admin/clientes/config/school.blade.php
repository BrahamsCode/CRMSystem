{{-- Legacy: system/category1/config/schoolyear_config.php (学年) y schoolclass_config.php (クラス), iguales salvo
     el título y la tabla: formulario de alta (o edición con ?cd=) y lista. Sin enlace en el menú.
     Variables:
       $siteName     nombre del sitio
       $title        '学年' | 'クラス' ($title del legacy)
       $routePrefix  'admin.clientes.school-years' | 'admin.clientes.school-classes' (.index/.store/.update/.destroy)
       $item         SchoolYearConfig|SchoolClassConfig|null: el que se edita
       $items        Collection (disp_flg = '1', ORDER BY cate_order); cate_name guardado con get_form2() --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', $title . '管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac3").accordion('option', 'active', 0);

});

</script>

<script type="text/javascript">

function submitChk () {
	if(document.form1.cate_name.value == ""){
		alert("{{ $title }}名が空白です。\n\n{{ $title }}名をご記入下さい。");
		return false;
	}

	var flg = confirm ( "この{{ $title }}名で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
	return flg;

}


    function submitChk2 (val) {
			element = eval("document.form_" + val);

      var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

			if(flg){
				element.submit();
			}
    }



</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $title }}登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ $item ? route($routePrefix . '.update', $item->cd) : route($routePrefix . '.store') }}" onsubmit="return submitChk()">
@csrf
@if ($item)
@method('PUT')
@endif


<fieldset>
<legend>{{ $title }}情報</legend>

<table class="tableBox">

<tr>
<th>{{ $title }}名</th>
<td>
	<input type="text" name="cate_name" id="cate_name" class="writeArea" maxlength="200" value="{!! $item?->cate_name !!}" />
</td>
</tr>

</table>

</fieldset>



<div class="btnBox">
@if ($item)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $item->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■{{ $title }}一覧</p>
<div class="form">
@if ($items->count() > 0)




<p class="textCenter">最新から{{ $items->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>カテゴリー名</th>
    <th>修正・削除</th>
  </tr>


@foreach ($items as $data)
@php $memCnt = $loop->iteration; @endphp
@if ($loop->index % 2 <> 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $data->cd }}</td>
    <td>{!! $data->cate_name !!}</td>
    <td class="textCenter" width="100">
    	<a href="{{ route($routePrefix . '.index', ['cd' => $data->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
		<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>
		<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route($routePrefix . '.destroy', $data->cd) }}">
		@csrf
		@method('DELETE')
		<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
		<input type="hidden" name="cd" class="btn" value="{{ $data->cd }}">
		<input type="submit" name="delete" id="delete" class="btn" value="削除する">
		</form>
	</td>
  </tr>
@endforeach

</table>
@else
{{ $title }}は登録されていません。
@endif

<ul class="btnBox2">

</ul>

</div>

<!-- / .contents --></div>
@endsection

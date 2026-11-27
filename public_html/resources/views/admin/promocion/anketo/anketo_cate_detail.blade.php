{{--
	アンケート詳細 — legacy: category3/anketo(2)/anketo_cate_detail.php (sin enlaces en el legacy; se conserva)
	Variables: $family, $cate, $kensu, $page
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $family->title() . ' 修正 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $family->title() }} 詳細</p>

<div class="form">

<fieldset>
<legend>{{ $family->title() }}</legend>

<table class="tableBox">

	<tr>
		<th><label for="name">アンケート名</label></th>
		<td>{{ $cate->name }}</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td>{{ $cate->comment }}</td>
	</tr>

	<tr>
		<th><label for="start_date">開始年月日</label></th>
		<td>{{ \App\Services\Promotions\AnketoSurveyService::date($cate->start_date) ?: '指定なし' }}</td>
	</tr>

	<tr>
		<th><label for="end_date">終了年月日</label></th>
		<td>{{ \App\Services\Promotions\AnketoSurveyService::date($cate->end_date) ?: '指定なし' }}</td>
	</tr>

</table>
</fieldset>

<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ $family->route('index') }}">
<input type="image" name="reserve_list_btn" id="reserve_list_btn" src="/system/img/btn_back_list_off.gif" value="戻る" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
</form>


<div class="btnBox">

<form method="post" name="form1" id="form1" class="form" action="{{ $family->route('edit') }}">
<input type="hidden" name="cd" id="cd" value="{{ $cate->cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="修正する" />
</form>

</div>


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection

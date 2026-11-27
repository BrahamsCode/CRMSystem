{{--
	部品 / 項目 詳細 — legacy: category3/anketo(2)/anketo_form_detail.php (+ inc/anketo_function.php addStylePrint)
	Variables: $family, $title, $form, $data (formData: eachform<i>), $registerType, $kensu, $page
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title . ($registerType === 'new' ? ' 新規登録' : ' 修正') . ' - ' . $siteName)

@php
	$options = in_array($form->form_type, \App\Services\Promotions\AnketoItemService::OPTION_TYPES, true);
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $title }} 詳細</p>

<div class="form">

<fieldset>
<legend>{{ $title }}</legend>

<table class="tableBox">

	<tr>
		<th><label for="name">項目タイトル</label></th>
		<td>{{ $form->name }}</td>
	</tr>

	<tr>
		<th><label for="unit">単位</label></th>
		<td>{{ $form->unit }}</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td>{!! nl2br(e($form->comment)) !!}</td>
	</tr>

	<tr>
		<th><label for="form_type">項目タイプ</label></th>
		<td>{{ $form->form_type }}

<span id="optionDisp">@if ($options)
オプション数:{{ $form->flds_row }}
@endif</span>

	</td>
	</tr>

	<tr>
		<th>項目設定</th>
		<td>
<div id="eachformData">
@if ($options)
@for ($i = 0; $i < (int) $form->flds_row; $i++)
表示値：{{ $data['eachform'.$i] ?? '' }}&nbsp;&nbsp;
<br/>
@endfor
@endif
</div>

		</td>
	</tr>

	<tr>
		<th><label for="style_class">表示仕様</label></th>
		<td>{{ \App\Services\Customers\CustomFieldService::STYLE_CLASSES[(string) $form->style_class] ?? '' }}</td>
	</tr>

</table>
</fieldset>

<form method="get" name="reserve_list" id="reserve_list" class="form mT10" action="{{ $family->route('forms.index') }}">
<input type="image" name="reserve_list_btn" id="reserve_list_btn" src="/system/img/btn_back_list_off.gif" value="戻る" />
<input type="hidden" name="pcd" id="pcd" value="{{ $form->tb_c3_anketo_cate_cd }}" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
</form>


<div class="btnBox">

<form method="post" name="form1" id="form1" class="form" action="{{ $family->route('forms.edit') }}">
<input type="hidden" name="cd" id="cd" value="{{ $form->cd }}" />
<input type="hidden" name="pcd" id="pcd" value="{{ $form->tb_c3_anketo_cate_cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="修正する" />
</form>

</div>


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection

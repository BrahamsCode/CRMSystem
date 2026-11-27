{{--
	アンケートテスト — legacy: category3/anketo(2)/anketo_test.php (sin menú lateral: una columna)
	Variables: $family, $cate, $forms, $values (POST), $errors (cd => mensaje de createform_errorcheck), $customerCd, $ok
	Cada campo se pinta con el parcial de S2 (createform_request).
--}}
@extends('layouts.legacy')

@section('title', 'アンケートテスト')

@section('content')
<div class="contents">
<div style="text-align:center;"><font size="14px" color="peru">テスト入力</font></div>
<p class="h2Title">■アンケートテスト</p>






<div class="form">

<form name="form1" action="{{ $family->route('test.store') }}" method="post">
<table class="tableBox">
@foreach ($forms as $form)
	<tr>
	    <th>{{ $form->name }}@if ((string) $form->hissu === '1')<span class="attention">※</span>@endif</th>
	    <td @if (isset($errors[$form->cd])) class="Ng" @endif>@include('admin.clientes.partials.custom_field', ['field' => $form->getAttributes(), 'values' => $values, 'mode' => 'input'])
@if ((string) $form->unit !== '')
{{ $form->unit }}<br />
@endif
{!! nl2br(e($form->comment)) !!}

<br /><font color=red>@if (isset($errors[$form->cd])){{ $errors[$form->cd] }}<br>@endif</font>		</td>
	</tr>

@endforeach
</table>

<input type="hidden" name="add_record_verify" id="add_record_verify" value="{{ time() }}" />
<input type="hidden" name="act" value="up" />
<input type="hidden" name="anketo_cate_cd" value="{{ $cate->cd }}" />
<input type="hidden" name="kaiin_cd" value="{{ $customerCd }}" />
<input type="submit" name="submit" value="送信" />
</form>

</div>






<!-- / .contents --></div>
@if ($ok)
<script>alert("送信しました。");</script>
@endif
@endsection

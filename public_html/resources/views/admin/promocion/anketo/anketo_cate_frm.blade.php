{{--
	アンケート / お客様アンケート 新規登録・修正 — legacy: category3/anketo(2)/anketo_cate_frm.php
	Variables: $family, $mode, $action, $back, $data (get_form: texto plano, se imprime escapado), $errors (campo => HTML), $cd
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $family->title() . $mode . ' - ' . $siteName)

@php
	$v = fn (string $k) => $data[$k] ?? '';
	$bg = fn (string $k) => isset($errors[$k]) ? ' class="Ng"' : '';
	$err = fn (string $k) => $errors[$k] ?? '';
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $family->title() }}{{ $mode }}@unless ($family->isCustomer()) - {{ $siteName }}@endunless</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $action }}">
<fieldset>
<legend>{{ $family->title() }}</legend>
<table class="tableBox">
	<tr>
		<th><label for="name">アンケート名<span class="attention">※</span></label></th>
		<td{!! $bg('name') !!}><input type="text" name="name" id="name" class="writeArea" value="{{ $v('name') }}" /><br />
		※アンケート名は、お客様回答時にアンケートフォーム上部へ表示されます。
		{!! $err('name') !!}
		</td>
	</tr>

	<tr>
		<th><label for="comment">管理用コメント</label></th>
		<td{!! $bg('comment') !!}><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ $v('comment') }}</textarea><br />
		※管理用コメントは、お客様には送信されません。
		{!! $err('comment') !!}
		</td>
	</tr>
@unless ($family->isCustomer())
	<tr>
		<th><label for="start_date">開始年月日<span class="attention">※</span></label></th>
		<td{!! $bg('start_date') !!}>
			<input type="text" name="start_date" id="start_date" maxlength="10" size="12" value="{{ $v('start_date') }}" style="ime-mode:disabled;" />&nbsp; (例: 2009/12/01)
		{!! $err('start_date') !!}
		</td>
	</tr>
	<tr>
		<th><label for="end_date">終了年月日<span class="attention">※</span></label></th>
		<td{!! $bg('end_date') !!}>
			<input type="text" name="end_date" id="end_date" maxlength="10" size="12" value="{{ $v('end_date') }}" style="ime-mode:disabled;" />&nbsp; (例: 2009/12/31)
		{!! $err('end_date') !!}
		</td>
	</tr>
@endunless
</table>
</fieldset>


<!--基本情報table start-->



<a href="{{ $back }}" class="backBtnStyle">{{ $family->isCustomer() ? 'アンケート一覧へ' : '一覧へ戻る' }}</a>


<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $cd }}" />
<input type="hidden" name="act" value="up">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $family->isCustomer() ? '項目設定へ' : '登録する' }}">
</div>

</form>

<!-- / .contents --></div>
@endsection

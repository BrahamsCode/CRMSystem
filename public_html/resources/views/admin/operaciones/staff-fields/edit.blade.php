{{-- Legacy: system/category5/staff/form_config.php + system/inc/staff_check_select.php (スタッフ検索項目設定).
     Mismo HTML (5 campos por fila, ■ verde = 検索条件項目, ■ amarillo = CSV出力項目) y mismos nombres de
     campo que espera form_config_register.php: item_cd{n} (cd de tb_koumoku), item_cd3_{n} (mail_mag_select),
     item_cd4_{n} (koumoku_select), item_max_no.
     Corregido: la casilla de búsqueda de スタッフID se pintaba con koumoku_select (salía siempre marcada);
     aquí usa mail_mag_select como el resto.
     Variables:
       $fields   filas de tb_koumoku con tb_name = 'tb_staff', ordenadas por cd
       $siteName nombre del sitio para el <title> --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ検索項目設定 - '.($siteName ?? ''))

@section('content')
@php
    $checked = fn ($value) => (int) $value === 1 ? ' checked="checked"' : '';
    $max = $fields->count();
@endphp
<div class="contents">
<p class="h2Title">■スタッフ検索項目設定</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.operaciones.staff-fields.update') }}">
@csrf
@method('PUT')

<fieldset>
<legend>スタッフ情報</legend>
<font color="#B3EFB3">■</font>…検索条件項目　<font color="#FFFFCC">■</font>…CSV出力項目<br />
<table class="tableBox">
@foreach ($fields as $field)
@php($no = $loop->iteration)
@if ($no % 5 == 1)
	<tr>
	<th>
@else
	<th>
@endif
		<small>{{ $field->tb_koumoku_name }}</small></th>
@if ($field->tb_koumoku_name === 'メールアドレス1')
				<td bgcolor="#C2E4EF" class="textCenter">必須<input type="hidden" name="item_cd3_{{ $no }}" value="1" />
<input type="hidden" name="item_cd{{ $no }}" id="item_cd{{ $no }}" value="{{ $field->cd }}" /></td>

		<td bgcolor="#FFFFCC"><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"{!! $checked($field->koumoku_select) !!} /></td>
@elseif ($field->tb_koumoku_name === 'メルマガ区分')
				<td colspan="3" bgcolor="#C2E4EF" class="textCenter"><input type="hidden" name="item_cd3_{{ $no }}" id="item_cd3_{{ $no }}" value="1" />
<input type="hidden" name="item_cd{{ $no }}" id="item_cd{{ $no }}" value="{{ $field->cd }}" /></td>

		<td bgcolor="#FFFFCC"><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"{!! $checked($field->koumoku_select) !!} /></td>
@elseif ($field->tb_koumoku_name === 'スタッフID')
				<td colspan="1" bgcolor="#B3EFB3" class="textCenter"><input type="checkbox" name="item_cd3_{{ $no }}" id="item_cd3_{{ $no }}" value="1"{!! $checked($field->mail_mag_select) !!} />
<input type="hidden" name="item_cd{{ $no }}" id="item_cd{{ $no }}" value="{{ $field->cd }}" /></td>

		<td bgcolor="#FFFFCC">必須<input type="hidden" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1" /></td>
@else
		<td bgcolor="#B3EFB3" class="textCenter"><input type="checkbox" name="item_cd3_{{ $no }}" id="item_cd3_{{ $no }}" value="1"{!! $checked($field->mail_mag_select) !!} />
<input type="hidden" name="item_cd{{ $no }}" id="item_cd{{ $no }}" value="{{ $field->cd }}" /></td>

		<td bgcolor="#FFFFCC"><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"{!! $checked($field->koumoku_select) !!} /></td>
@endif
@if ($no % 5 == 0)
	</th>
	</tr>
@else
	</th>
	@if ($loop->last)
	<th colspan="{{ 5 * (5 - ($max % 5)) }}"></th>
	@endif
@endif
@endforeach
</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="item_max_no" value="{{ $max }}">
<input type="submit" name="enter" id="enter" class="btnStyleM" value="この内容で変更する">
</div>

</form>

<!-- / .contents -->
</div>
@endsection

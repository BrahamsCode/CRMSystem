{{-- Legacy: system/category1/inc/photoalbum_frm.php (desde photoalbum_new.php / photoalbum_update.php), マイアルバム.
     Variables:
       $customer     Customer (cd, id, name)
       $album        CustomerPhoto|null: fila de tb_photo_album en modificación (null = alta)
       $mode         '新規登録' | '更新'
       $values       array: hiduke ('Y/m/d'), comment
       $picNames     array n => '<temp>.jpg': fotos recién subidas (pic_name[]) al volver con errores
       $picFlags     array n => '1'|'': 画像フラグ de la fila (pic_flg)
       $picCount     int: 画像枚数 (1)
       $fieldErrors  array: 'pic_name.0', 'hiduke' => mensaje del legacy
       $hidden       array: kaiin_cd, tab, page (el $form_content del legacy)
       $verifyToken  string: add_record_verify (time(), evita el doble envío) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', 'フォトアルバム ' . $mode . ' - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@php
    $cacheBuster = time();
    $ngBg = fn (string $key) => isset($fieldErrors[$key]) ? ' class="Ng"' : '';
@endphp

@section('css')
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/YahhoCal.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/GCalHolidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/yuiloader-dom-event.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/cal/calendar-min.js') }}"></script>
<script type="text/javascript">
function format1(id) {
	YahhoCal.format.padZero = true;
	YahhoCal.render(id);
}
</script>

<link type="text/css" rel="stylesheet" href="{{ asset('legacy/system/css/calendar.css') }}" media="all">
@endsection

@section('js')
@include('admin.clientes.partials.ac')

<script type="text/javascript">
var max_pics = {{ $picCount }};
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/category1/inc/js/pic_upload.js') }}"></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■フォトアルバム {{ $mode }}</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $album ? route('admin.clientes.photos.update', [$customer->cd, $album->cd]) : route('admin.clientes.photos.store', $customer->cd) }}">
@csrf
@if ($album)
@method('PUT')
@endif

<fieldset>
<legend>フォトアルバム</legend>

<table class="tableBox">

	<tr>
		<th><label for="user_id">顧客ID</label></th>
		<td>{{ $customer->id }}</td>
	</tr>

	<tr>
		<th><label for="user_id">顧客名</label></th>
		<td>{{ $customer->name }}</td>
	</tr>

@for ($x = 0; $x < $picCount; $x++)
	<tr>
		<th>画像</th>
		<td{!! $ngBg('pic_name.' . $x) !!}>
		<div id="gazou{{ $x }}">@if (($picNames[$x] ?? '') !== '')<img src="/system/category1/customer/uploads/{{ substr($picNames[$x], 0, -4) }}th.jpg?t={{ $cacheBuster }}" alt="画像" class="mB10" />@elseif ($album && ($picFlags[$x] ?? '') == '1')<img src="{{ url('hp/uploads/c1/photoalbum/' . $album->cd . '/' . $x . '_m.jpg') }}?t={{ $cacheBuster }}" alt="画像" class="mB10" />@endif</div>
		<p id="photo{{ $x }}" class="up_photo">&nbsp;</p>
		<input type="hidden" name="pic_name[{{ $x }}]" id="pic_name{{ $x }}" value="{{ $picNames[$x] ?? '' }}" />
		@isset($fieldErrors['pic_name.' . $x])<br />{{ $fieldErrors['pic_name.' . $x] }}@endisset
		</td>
	</tr>
@endfor

	<tr>
		<th><label for="hiduke">年月日<em>※</em></label></th>
		<td{!! $ngBg('hiduke') !!}>
		<input type="text" name="hiduke" id="hiduke" class="inputM" maxlength="10" style="ime-mode:disabled;" onclick="format1(this.id);" value="{{ $values['hiduke'] ?? '' }}" />(例: 2009/12/01)
		@isset($fieldErrors['hiduke'])<br />{{ $fieldErrors['hiduke'] }}@endisset
		</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td>
		<textarea name="comment" id="comment" rows="5" class="writeArea mB5">{{ $values['comment'] ?? '' }}</textarea>
		</td>
	</tr>

</table>
</fieldset>

<input type="button" name="dummy_back_input" id="dummy_back_input" class="btn backBtnStyle" value="戻る" onclick="document.back_btn.submit()" />

<div class="btnBox">
@foreach ($hidden as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="add_record_verify" id="add_record_verify" value="{{ $verifyToken }}" />
<input type="hidden" name="photoalbum_cd" id="cd" value="{{ $album?->cd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn btnStyleS" value="この内容で登録する" />
</div>

</form>
{{-- 戻る: el legacy hacía POST a customer_detail.php; la ficha es GET --}}
<form name="back_btn" id="back_btn" action="{{ route('admin.clientes.show', $customer->cd) }}" method="get" style="display: none">
@foreach ($hidden as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="photoalbum_cd" id="cd" value="{{ $album?->cd }}" />
<input type="hidden" name="from" id="from" value="reg">
<input type="submit" name="enter" id="enter" class="btn" value="戻る">
</form>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection

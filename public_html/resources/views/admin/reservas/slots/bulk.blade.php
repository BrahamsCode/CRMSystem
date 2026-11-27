{{--
    Legacy: system/category9/yoyaku/yoyaku_multi_uketsuke_day.php (受付日設定 de varios días; el legacy pone
    inc/header.php sin menú lateral: aquí layouts.legacy-popup como el resto de pantallas de 予約管理).
    Variables:
      $days    Collection<array{value: string, weekday: ?string}>  días elegidos en スタッフ別予約表 ('Y/m/d')
      $shopCd  ?int                 tienda (?shop=; si no es master y no viene, la de la sesión)
      $config  ?ReservationConfig   tb_yoyaku_config de la tienda (horario 'HH:MM')
    Parámetros que se reenvían tal cual: staff, d, bd.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約項目管理')

@section('css')
<style type="text/css"><!--

#container {
	min-width: 500px;
}

--></style>
@endsection

@section('js')
@include('admin.reservas.slots.partials.form_js')
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■受付日設定</p>

@if (Route::has('admin.reservas.timetable.staff'))<a href="{{ route('admin.reservas.timetable.staff', ['shop' => request('shop'), 'staff' => request('staff'), 'd' => request('d')]) }}">&lt;&lt;戻る</a>@endif

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.slots.bulk.store') }}">
@csrf

<fieldset>
<legend>受付日登録情報</legend>

<table class="tableBox">

	<tr>
	<th>予約日</th>
	<td>
@foreach ($days as $day)
	{{ $day['value'] }}({{ $day['weekday'] }})<br/>
@endforeach
	</td>
	</tr>

@include('admin.reservas.slots.partials.time_rows', ['isNew' => true, 'start' => null, 'end' => null, 'people' => null])

</table>

</fieldset>



<div class="btnBox">
<input type="button" name="enter" id="revise" value="この内容で登録する" onclick="submitChk();" />
<input type="hidden" name="act" id="act" value="reg" />
@foreach ($days as $day)
<input type="hidden" name="days[]" value="{{ $day['value'] }}" />
@endforeach
<input type="hidden" name="shop" value="{{ $shopCd }}" />
<input type="hidden" name="staff" value="{{ request('staff') }}" />
<input type="hidden" name="d" value="{{ request('d') }}" />
<input type="hidden" name="bd" value="{{ request('bd') }}" />
</div>

</form>



<!-- / .contents --></div>
@endsection

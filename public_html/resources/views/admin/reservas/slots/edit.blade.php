{{--
    Legacy: system/category9/yoyaku/yoyaku_uketsuke_day.php (受付日設定, ventana emergente sin yoyaku_navi).
    Variables:
      $slot          ?ReservationSlot  franja que se edita (?cd=) o null en el alta (?staff=&yoyaku_date=)
      $staffName     string            tb_staff.name
      $staffCd       int               tb_staff.cd
      $shopCd        ?int              tb_staff.shop_cd
      $staffLabel    string            manageWord('スタッフ名', 'c9_yoyaku[スタッフ]')
      $displayDate   string            'YYYY年MM月DD日(曜) '
      $config        ?ReservationConfig  tb_yoyaku_config de la tienda (horario 'HH:MM')
      $start, $end   ?array            ['HH', 'MM'] de la franja (null en el alta)
      $getData       ?string           consulta para volver a 時間選択 (solo si llegan kijun_time y yoyaku_date)
      $isIpad        bool              User-Agent con iPad
      $reservations  ?LengthAwarePaginator<Reservation>  予約者一覧 (con customer_name); null en el alta
      $pageQuery     array             parámetros de la petición sin page/kensu (para la paginación)
--}}
@extends('layouts.legacy-popup')

@section('title', '予約項目管理')

@section('css')
<style type="text/css">

#container {
	min-width: 500px;
}

</style>
@endsection

@section('js')
@include('admin.reservas.slots.partials.form_js')

<script type="text/javascript">

function submitChk2 () {
	jConfirm ( "この日の予約受付を削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい", "予約受付削除", function(ret) {
		if (ret) document.form2.submit();
	});
}

function hideMess() {
		$("#mess").hide().html('');
}

$(document).ready(function() {
@if (request('lastact') == 'reg')
@if ($isIpad)
	window.opener.location.reload();
@endif
	$("#mess").html('設定更新しました。').show();
	setTimeout('hideMess()', 2000);
	if (typeof(window.parent.popUpWinReloadClose) != "undefined") window.parent.popUpWinReloadClose(''); // 存在する場合はページを切りかえる
@endif

@if (($slot?->start_break_time ?? '') != '')
	var startHourList = {{ \Illuminate\Support\Js::from((string) $slot->start_break_time) }}.split("<>");
	var endHourList = {{ \Illuminate\Support\Js::from((string) $slot->end_break_time) }}.split("<>");
	for (var i=0; i<startHourList.length; i++) {
		addBreakTime(startHourList[i], endHourList[i]);
	}
@endif
});

</script>
@endsection

@section('content')
<div class="contents">


<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>

@if ($isIpad)
<style>#ipad_window_close { cursor: pointer; }</style>
<div style="text-align:right;margin-bottom:15px">
	<button id="ipad_window_close" ontouchstart="window.opener.location.reload();window.close();">閉じる</button>
</div>
@endif

<p class="h2Title">■受付日設定</p>

@if ($getData !== null)
@if (Route::has('admin.reservas.calendar-select'))<a href="{{ route('admin.reservas.calendar-select') }}?{{ $getData }}">&lt;&lt;時間設定に戻る</a>@endif
@endif

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.slots.store') }}">
@csrf

<fieldset>
<legend>受付日登録情報</legend>

<table class="tableBox">

	<tr>
	<th>{{ $staffLabel }}</th>
	<td>
	{{ $staffName }}
	</td>
	<tr>
	<th>予約日</th>
	<td>
	{{ $displayDate }}
	</td>
	</tr>

@include('admin.reservas.slots.partials.time_rows', ['isNew' => ! filled(request('cd')), 'people' => $slot?->people])

</table>

</fieldset>

<div class="btnBox">
@if (filled(request('cd')))
<input type="button" name="enter" id="revise" value="この内容で修正する" onclick="submitChk();" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $slot?->cd }}" />
@else
<input type="button" name="enter" id="revise" value="この内容で登録する" onclick="submitChk();" />
<input type="hidden" name="act" id="act" value="new" />
@endif
<input type="hidden" name="shop" value="{{ $shopCd }}" />
<input type="hidden" name="staff" value="{{ $staffCd }}" />
<input type="hidden" name="menu" value="{{ is_scalar(request('menu')) ? request('menu') : '' }}" />

@if (request()->has('kijun_time'))
<input type="hidden" name="kijun_time" value="{{ request('kijun_time') }}" />
<input type="hidden" name="getData" value="{{ $getData }}" />
@endif

@if (request()->has('yoyaku_date'))
<input type="hidden" name="yoyaku_date" value="{{ request('yoyaku_date') }}" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
@if ($slot !== null)
@if ($reservations->total() > 0)
<p class="h2Title">■予約者一覧</p>

<div class="form">
	<table class="tableBox2">
		<tr>
			<th>名前</th>
			<th>電話番号</th>
			<th style="width: 5em">開始時間</th>
			<th style="width: 5em">終了時間</th>
			<th style="width: 5em">受付人数</th>
		</tr>
@foreach ($reservations as $row)
		<tr{!! $loop->index % 2 != 0 ? ' class="tableBg"' : '' !!}>
			<td>{{ $row->customer_name }}</td>
			<td>{{ $row->tel }}</td>
			<td>{{ \App\Services\Reservations\ReservationSlotService::clock($row->start_time) }}</td>
			<td>{{ \App\Services\Reservations\ReservationSlotService::clock($row->end_time) }}</td>
			<td>{{ $row->people }}人</td>
		</tr>
@endforeach
	</table>
<!--/.form--></div>

<ul class="btnBox2">
@include('admin.reservas.slots.partials.page_control', ['paginator' => $reservations, 'params' => $pageQuery])
</ul>
@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/>現在登録データがありません。<br/></p>
<p style="text-align:left;">
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.reservas.slots.destroy', $slot->cd) }}">
@csrf
@method('DELETE')
<input type="button" name="delbtn" value="予約受付を削除する" style="background:#EE0000;color:#FFFFFF;" onclick="return submitChk2();" />
<input type="hidden" name="act" value="delete" />
<input type="hidden" name="cd" value="{{ $slot->cd }}" />
</form>
</p>
@endif
@endif


<!-- / .contents --></div>
@endsection

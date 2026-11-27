{{--
    Legacy: system/category9/yoyaku/pop_calendar.php (年間カレンダー: ventana emergente que cambia el 予約表 de la ventana madre).
    Variables:
      $year      int     año mostrado (`y` o el actual).
      $shop      ?string `shop` de la petición (se arrastra en los enlaces).
      $ctiParams array   [] o ['isCti' => '1', 'tel' => …, 'customerCd' => …] (CTI 電話予約モード).
      $months    array   12 hojas: ['title' => 'Y年n月', 'weeks' => filas de 7 celdas (null o ['day', 'class', 'ymd'])].
      $siteName  string  nombre del sitio (<title>).
--}}
@extends('layouts.legacy-popup')

@section('title', '年間カレンダー - ' . $siteName)

@section('css')
<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}


.smallCal {
	display: inline-block;
	margin-bottom: 10px;
	vertical-align: top;
	background: #FFFFFF;
}

.smallCal:nth-child(odd) {
	margin-right: 5px;
}


.smallCal th {
	padding: 0 5px;
	border: 1px solid #000;
	text-align: center;
	font-size: 20px;
	color: #FFFFFF;
	background: #333333;
}

.smallCal td {
	width: 30px;
	border: 1px solid #000;
	text-align: center;
	font-size: 20px;
}

.smallCal td span {
	display: block;
	padding: 0 4px;
}


.saturday a:link,
.saturday a:visited {
	color: #336699;
	text-decoration: none;
}

.sunday a:link,
.sunday a:visited {
	color: #FF0000;
	text-decoration: none;
}

.weekday a:link,
.weekday a:visited {
	color: #333333;
	text-decoration: none;
}

.today {
	background: #FFFF99;
}

.selectday {
	background: #555555;
}

.selectday a:link,
.selectday a:visited {
	color: #FFFFFF;
}


.yearSenni {
	position: relative;
	width: 45px;
	background: #999999;
	text-align: center;
	cursor:pointer;

}

.seniLeft {
	/*gradient*/
	background: #EEE; /* old browsers */
	background: -moz-linear-gradient(left, #EEE 0%, #FFF 10%, #EEE 100%); /* firefox */
	background: -webkit-gradient(linear, left top, right top, color-stop(0%,#EEE), color-stop(10%,#FFF), color-stop(100%,#EEE)); /* webkit */
}

.seniRight {
	/*gradient*/
	background: #EEE; /* old browsers */
	background: -moz-linear-gradient(left, #EEE 0%, #FFF 90%, #EEE 100%); /* firefox */
	background: -webkit-gradient(linear, left top, right top, color-stop(0%,#EEE), color-stop(90%,#FFF),  color-stop(100%,#EEE)); /* webkit */
}

.yearSenni:hover {
	background: #FFC;
}

.calendarNextBtn {
	position:fixed;
	top: 120px;
	width: 45px;
	text-align: center;
	font-size: 25px;
	color: #333;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">

function disp(url){

	if(!window.opener || window.opener.closed){ // メインウィンドウの存在をチェック

		window.alert('メインウィンドウがありません'); // 存在しない場合は警告ダイアログを表示

	}
	else{

		window.opener.location.href = url; // 存在する場合はページを切りかえる

	}

}
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■年間カレンダー</p>
<table>
<tr>
<td valign="top" class="yearSenni seniLeft" onclick="javascript:location.href={{ \Illuminate\Support\Js::from(route('admin.reservas.pop-calendar', array_merge(['shop' => $shop, 'y' => $year - 1], $ctiParams))) }};">
<div class="calendarNextBtn">前</div>
</td>
<td style="width: 460px; text-align: center;">
@foreach ($months as $sheet)
<table class="smallCal"><tr><th colspan="7">{{ $sheet['title'] }}</th></tr>
@foreach ($sheet['weeks'] as $week)
<tr>
@foreach ($week as $cell)
@if ($cell === null)
<td></td>
@else
<td><span class="{{ $cell['class'] }}"><a href="javascript:void" onclick="disp({{ \Illuminate\Support\Js::from(route('admin.reservas.timetable', array_merge(['shop' => $shop, 'd' => $cell['ymd']], $ctiParams))) }})">{{ $cell['day'] }}</a></span></td>
@endif
@endforeach
</tr>
@endforeach
</table>
@endforeach
</td>
<td class="yearSenni seniRight" onclick="javascript:location.href={{ \Illuminate\Support\Js::from(route('admin.reservas.pop-calendar', array_merge(['shop' => $shop, 'y' => $year + 1], $ctiParams))) }};">
<div class="calendarNextBtn">次</div>
</td>
</tr>
</table>

<div class="btnBox">
<input type="button" class="btnStyleM" value="閉じる" onclick="window.close()" />
</div>

<!-- / .contents --></div>
@endsection

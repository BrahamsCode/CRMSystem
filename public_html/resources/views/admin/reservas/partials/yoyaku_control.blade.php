{{--
    Legacy: system/category9/yoyaku/inc/yoyaku_control.php (fecha mostrada, calendarios, pestañas y botones de día/semana).
    Variables (las mismas de timetable.blade.php):
      $shopCd int; $d ?string (Ymd de la petición); $baseDate Carbon (día mostrado);
      $ichiran int (0 día | 1 月間 | 2 週間); $ichiranDate ?string ('Y-m' | 'Y-Www'); $ichiranStart, $ichiranEnd ?Carbon;
      $ctiParams array (parámetros CTI que se arrastran en los enlaces); $versionFlg '1' | '2';
      $raitenListEnabled bool (pestaña 来店リスト); $listPrintEnabled bool (当日予約一覧出力);
      $calendarMonths Carbon[] (día 1 de los 3 meses de los calendarios pequeños).
--}}
@php
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
    $dateDispStyle = null;
    if ($ichiran > 0) {
        $dispAreaYear = substr((string) $ichiranDate, 0, 4);
        if ($ichiran == 1) {
            $dispAreaDateLines = [substr((string) $ichiranDate, 5, 2).'月'];
        } else {
            $dispAreaDateLines = [$ichiranStart->format('m月d日').'～', $ichiranEnd->format('m月d日')];
            $dateDispStyle = 'font-size: 25px;';
        }
        $dispAreaDay = null;
        $reloadParams = ['shop' => $shopCd, 'ichiran' => $ichiran, 'date' => $ichiranDate];
    } else {
        $dispAreaYear = $baseDate->format('Y');
        $dispAreaDateLines = [$baseDate->format('m/d')];
        $dispAreaDay = '('.$youbi[$baseDate->dayOfWeek].')';
        $reloadParams = ['shop' => $shopCd, 'd' => $d];
    }

    // Botones de Base::createHtmlPart(): versión 2 = texto, versión 1 = imagen
    $btnImg = fn (string $file) => asset('legacy/system/category9/yoyaku/img/'.$file);

    $navParams = fn (?string $ymd) => array_merge(['shop' => $shopCd], $ymd ? ['d' => $ymd] : [], $ctiParams);
    $yesterday = $baseDate->copy()->subDay()->format('Ymd');
    $tomorrow = $baseDate->copy()->addDay()->format('Ymd');
    $senshu = $baseDate->copy()->subDays(7)->format('Ymd');
    $raishu = $baseDate->copy()->addDays(7)->format('Ymd');

    $ichiranMonthDate = $ichiran == 1 ? $ichiranDate : '';
    $ichiranWeekDate = $ichiran == 2 ? $ichiranDate : '';
@endphp
<div class="yoyakuControlArea">

<div id="dateDispArea">
<div id="nenDisp"><span>{{ $dispAreaYear }}</span>年</div>
<div id="dateDisp"><a href="{{ route('admin.reservas.timetable', $reloadParams) }}"@if ($dateDispStyle) style="{{ $dateDispStyle }}"@endif>@foreach ($dispAreaDateLines as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach<span>{{ $dispAreaDay }}</span></a></div>
<div id="kenmeiDisp"><span id="total_yoyaku_cnt"></span>件<span id="total_people_cnt"></span>名</div>
<div id="yoyakuBtnChart" class="koushinBtn"><a href="{{ route('admin.reservas.timetable', $reloadParams) }}">@if ($versionFlg == '2')チャート更新@else<img src="{{ $btnImg('button_chart.jpg') }}" alt="チャート更新" />@endif</a></div>
</div>



<div id="calArea">

<ul id="calAreaControl">
<li><a id="calAreaControl_calendar">　カレンダー　</a></li>
<li><a id="calAreaControl_yoyakuList">　新規予約リスト　</a></li>
@if ($raitenListEnabled)
<li><a id="calAreaControl_raitenList">　来店リスト　</a></li>
@endif
<li><a id="calAreaControl_ichiran">　一覧表示　</a></li>
@if ($listPrintEnabled)
<li><a href="{{ route('admin.reservas.print', ['shop' => $shopCd, 'd' => $d]) }}" target="_blank">当日予約一覧出力</a></li>
@endif
</ul>
<div id="calendarControl">
@foreach ($calendarMonths as $calendarMonth)
@include('admin.reservas.partials.small_calendar', ['month' => $calendarMonth])
@endforeach

<!-- /#calendarControl --></div>

<div id="newYoyakuList"></div>
<div id="newRaitenList"></div>

<div id="ichiranControl">
	<div id="ichiranButtons">
		<button type="button" class="btn" style="margin: 20px; width:100px; height:50px;" id="ichiranMonthButton">月間</button>
		<button type="button" class="btn" style="margin: 20px; width:100px; height:50px;" id="ichiranWeekButton">週間</button>
	</div>
	<div id="ichiranCalendar" hidden>
		<button type="button" class="btn" style="margin-top:10px; margin-bottom:10px; font-size:0.8rem;" id="">月間・週間切り替え</button><br/>
		<div id="ichiranMonthCalendar" hidden>
			<label for="ichiranMonth" style="font-size:1.5rem;">月間　</label>
			<input type="month" id="ichiranMonth" name="ichiranMonth" ichiran="1" style="font-size:1.2rem;" value="{{ $ichiranMonthDate }}"/>
		</div>
		<div id="ichiranWeekCalendar" hidden>
			<label for="ichiranWeek" style="font-size:1.5rem;">週間　</label>
			<input type="week" id="ichiranWeek" name="ichiranWeek" ichiran="2" style="font-size:1.2rem;" value="{{ $ichiranWeekDate }}"/>
		</div>
	</div>
</div>

<!-- /#calArea --></div>

<div id="dateControlArea">
<span id="yoyakuBtnZenjitsu"><a href="{{ route('admin.reservas.timetable', $navParams($yesterday)) }}">@if ($versionFlg == '2')前日@else<img src="{{ $btnImg('button_zenjitsu.jpg') }}" width="70" height="31" alt="前日" />@endif</a></span><span id="yoyakuBtnYokujitsu"><a href="{{ route('admin.reservas.timetable', $navParams($tomorrow)) }}">@if ($versionFlg == '2')翌日@else<img src="{{ $btnImg('button_yokujitsu.jpg') }}" width="70" height="31" alt="翌日" />@endif</a></span>
<span id="yoyakuBtnKyo"><a href="{{ route('admin.reservas.timetable', $navParams(null)) }}">@if ($versionFlg == '2')今日@else<img src="{{ $btnImg('button_kyo.jpg') }}" width="145" height="31" alt="今日" />@endif</a></span>
<span id="yoyakuBtnSensyu"><a href="{{ route('admin.reservas.timetable', $navParams($senshu)) }}">@if ($versionFlg == '2')先週@else<img src="{{ $btnImg('button_sensyu.jpg') }}" width="70" height="31" alt="先週" />@endif</a></span><span id="yoyakuBtnYokusyu"><a href="{{ route('admin.reservas.timetable', $navParams($raishu)) }}">@if ($versionFlg == '2')翌週@else<img src="{{ $btnImg('button_yokusyu.jpg') }}" width="70" height="31" alt="翌週" />@endif</a></span>
<span id="yoyakuBtnCal"><a href="javascript:void(0);" onclick="var w=screen.width-650;var h=screen.height-130;window.open({{ \Illuminate\Support\Js::from(route('admin.reservas.pop-calendar', $navParams(null))) }}, 'popCalendar', 'width=630, height='+h+', left='+w+', top=10, menubar=no, toolbar=no, scrollbars=yes');">@if ($versionFlg == '2')年間カレンダー@else<img class="propagateThis" src="{{ $btnImg('button_cale.jpg') }}" alt="年間カレンダー" />@endif</a></span>
</div>





<!-- /.yoyakuControlArea--></div>

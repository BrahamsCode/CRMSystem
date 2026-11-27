{{--
	クーポン 詳細 + クーポン解析 — legacy: system/category3/mycoupon/mycoupon_detail.php
	Variables: $data (fila; time_limit ya como Y/m/d si limit_flg = 5), $shopName, $kubunLabel, $canEdit, $canSend, $expired,
	  $usedCount, $usedByMonth, $mailIssues, $followIssues, $back (filtros de la lista), $fromStats
	Las gráficas usan /system/inc/graph_pie.php y graph_bar.php del núcleo (pedido a S1), como el legacy.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
	$kubun = $d('coupon_kubun');
	$limitLabel = ['', '発行から1週間', '発行から1ヶ月', '発行から3ヶ月', '発行から' . $d('time_limit') . '日間', '指定日付：' . $d('time_limit') . 'まで'][(int) $d('limit_flg')] ?? '';
	$attention = $d('attention') !== '' ? explode('<>', $d('attention')) : [];
	$points = $d('shouhi_point') !== '' && in_array($kubun, ['2', '10'], true);
	$rowspan = ($points || $kubun === '4') ? 7 : 6;
	$pie = fn (string $v) => '/system/inc/graph_pie.php?w=300&h=180&v=' . $v;
@endphp

@section('title', 'クーポン' . (request('register_type') === 'new' ? ' 新規登録' : ' 修正') . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
<script type="text/javascript"><!--
function submitChk () {
	var flg = confirm ( "この内容で発行してもよろしいですか？\n\n発行しない場合は[キャンセル]ボタンを押して下さい");
	return flg;
}
function delChk () {
    var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
    return flg;
}
--></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■クーポン 詳細</p>
<div class="form">
<fieldset>
<legend>クーポン</legend>
<table class="tableBox">
	<tr>
		<th valign="top">使用区分</th>
		<td valign="top">{{ $kubunLabel }}</td>
		<td rowspan="{{ $rowspan }}" style="width: 300px; background: #FAFAF6;">
		<div class="merumagaKakuninField" style="margin-left: 30px;">
		<div class="mailKakuninNaiyou">
		<div style="font-size: 16px;">クーポン詳細</div>
		<div style="text-align: center; font-size: 16px;">{{ $d('title') }}</div>
		<div style="text-align: center; font-size: 12px;">{!! nl2br(e($d('comment'))) !!}</div>
		<div style="font-size: 12px;">発行店舗：{{ $shopName }}</div>
		<div style="font-size: 12px;">有効期限：{{ $limitLabel }}@if ($d('shouhi_point') !== '')<span class="attention mL5">※ユーザーがスタンプと交換してからの期限になります。</span>@endif</div>
		<div style="font-size: 12px;">
		@foreach ($attention as $line)
		{{ $line }}<br/>
		@endforeach
			</div>
		</div></div>
		<small>※携帯電話の画面サイズ・文字の大きさはそれぞれ違います。<br />
		ここに表示してあるものはあくまで一例です。</small>
		</td>
	</tr>
	<tr>
		<th valign="top">店舗</th>
		<td valign="top">{{ $shopName }}</td>
	</tr>
	<tr>
		<th valign="top">タイトル</th>
		<td valign="top">{{ $d('title') }}</td>
	</tr>
	<tr>
		<th valign="top">内容</th>
		<td valign="top">{!! nl2br(e($d('comment'))) !!}</td>
	</tr>
@if ($points)
	<tr>
		<th valign="top">{{ $kubun === '2' ? '消費スタンプ数' : '消費ポイント数' }}</th>
		<td valign="top">{{ $d('shouhi_point') }}</td>
	</tr>
@endif
	<tr>
		<th valign="top">有効期限</th>
		<td valign="top">
			{{ $limitLabel }}
			@if ($d('shouhi_point') !== '')
				<span class="attention mL5">※ユーザーが{{ $kubun === '2' ? 'スタンプ' : 'ポイント' }}と交換してからの期限になります。</span>
			@endif
			</td>
	</tr>
@if ($kubun === '4')
	<tr>
		<th valign="top">クーポン発行条件</th>
		<td valign="top">{{ $d('saitouroku_flg') !== '1' ? '一度退会して再登録した場合も発行する' : '一度退会して再登録した場合は発行しない' }}</td>
	</tr>
@endif
	<tr>
		<th valign="top">注意事項</th>
		<td valign="top">
		@foreach ($attention as $line)
		{{ $line }}<br/>
		@endforeach
		</td>
	</tr>
@if ($kubun === '1')
	<tr>
		<th>他店舗使用</th>
		<td>{{ $d('other_use_flg') === '' ? '使用不可' : '使用可' }}</td>
	</tr>
@endif
</table>
</fieldset>

@if (($back['func'] ?? '') === 'ranking')
<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ Route::has('admin.promocion.coupons.ranking') ? route('admin.promocion.coupons.ranking') : '' }}">
@csrf
<input type="submit" name="reserve_list_btn" id="reserve_list_btn" class="backBtnStyle" value="戻る" />
<input type="hidden" name="shop" id="shop" value="{{ $back['shop'] ?? '' }}" />
<input type="hidden" name="coupon_kubun" id="coupon_kubun" value="{{ $back['coupon_kubun'] ?? '' }}" />
</form>
@elseif ($fromStats)
<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ Route::has('admin.promocion.coupons.stats') ? route('admin.promocion.coupons.stats') : '' }}">
@csrf
<input type="submit" class="backBtnStyle" value="戻る" />
@foreach (['kensu', 'page', 'shop_cd', 'kikan_option', 'search_start', 'search_end'] as $key)
<input type="hidden" name="{{ $key }}" id="{{ $key }}" value="{{ $back[$key] ?? '' }}" />
@endforeach
</form>
@else
<form method="post" name="reserve_list" id="reserve_list" class="form mT10" action="{{ route('admin.promocion.coupons.index') }}">
@csrf
<input type="submit" name="reserve_list_btn" id="reserve_list_btn" class="backBtnStyle" value="戻る" />
@foreach (['kensu', 'page', 'coupon_kubun', 'shop_cd'] as $key)
<input type="hidden" name="{{ $key }}" id="{{ $key }}" value="{{ $back[$key] ?? '' }}" />
@endforeach
</form>
@endif

@if (! $fromStats)
<div class="btnBox">
@if ($canEdit)
<form method="get" name="form1" id="form1" class="form" action="{{ route('admin.promocion.coupons.edit') }}">
<input type="hidden" name="cd" id="cd" value="{{ $d('cd') }}" />
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="修正する" />
</form>
@endif
@if ($canSend)
@if ((string) $d('limit_flg') === '5' && $expired && $kubun === '')
<span style="color:red; margin:0 10px;">有効期限が過ぎてるため、発行できません。</span>
@elseif ($kubun === '' && Route::has('admin.promocion.coupons.mail.select'))
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.promocion.coupons.mail.select') }}">
@csrf
<input type="hidden" name="cd" id="cd" value="{{ $d('cd') }}" />
<input type="hidden" name="hakkou_cd" id="hakkou_cd" value="{{ $d('cd') }}" />
<input type="hidden" name="get_coupon_cd" id="get_coupon_cd" value="{{ $d('cd') }}" />
<input type="hidden" name="get_coupon_text" id="get_coupon_text" value="【{{ $d('title') }}】({{ $d('cd') }})" />
<input type="hidden" name="coupon_kubun" id="coupon_kubun" value="{{ $kubun }}" />
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="このクーポンを送る" />
</form>
@endif
@endif
@if ($canEdit)
<form method="post" name="form3" id="form2" class="form" action="{{ route('admin.promocion.coupons.destroy') }}" onsubmit="return delChk()">
@csrf
<input type="hidden" name="delete_flg" id="delete_flg" value="del">
<input type="hidden" name="cd" id="cd" value="{{ $d('cd') }}" />
@foreach (['kensu', 'page', 'coupon_kubun', 'shop_cd'] as $key)
<input type="hidden" name="{{ $key }}" id="{{ $key }}" value="{{ $back[$key] ?? '' }}" />
@endforeach
<input type="submit" name="delete" id="delete" class="btn btnStyleM" value="削除する">
</form>
@endif
</div>
@endif

<div class="contents">
@if ($usedCount > 0)
<p class="h2Title">■クーポン解析</p>
<p class="h3Title">基本情報</p>
過去クーポン使用総数：
<span style="font-size: 45px; font-weight: bold; color: #EE0000">{{ $usedCount }}</span>
<span style="font-size: 20px; font-weight: bold;">回</span>

@if ($usedByMonth->isNotEmpty())
@php
	$recent = $usedByMonth->take(15);
	$x = $recent->map(fn ($r) => "'" . substr((string) (int) $r->year, 2, 3) . '.' . sprintf('%02d', (int) $r->month))->map(fn ($v) => ',' . $v)->implode('');
	$v = $recent->map(fn ($r) => ',' . $r->count)->implode('');
@endphp
<p class="h3Title">クーポンを使用した時期</p>
<small>※グラフは1回でも使用があった月を最新から15ヶ月分まで表示します。</small>
<iframe src="/system/inc/graph_bar.php?b=FFFFFF&gb=CCCCCC&lc=000000&gtype=flat&l=false& w=550%&h=150&x={{ $x }}&y=&bc=005DFF&v={{ $v }}" frameborder="0" width="600" height="170" scrolling="no" style="display: block; *display: inline; *zoom: 1; vertical-align: middle; overflow:hidden"></iframe>
<small>※リストは1回でも使用があった月を最新から30ヶ月分表示します。</small>
<table class="tableBox">
@foreach ($usedByMonth as $m)
@php
	$sex = '男性：' . $m->men . '人,' . $m->men . '|女性：' . $m->women . '人,' . $m->women . '|不明：' . $m->sex_none . '人,' . $m->sex_none;
	$ages = collect(['age0' => '0～19歳', 'age20' => '20～29歳', 'age30' => '30～39歳', 'age40' => '40～49歳', 'age50' => '50～59歳', 'age60' => '60～69歳', 'age70' => '70～79歳', 'age80' => '80歳以上', 'age_none' => '不明'])
		->map(fn ($label, $k) => $label . '：' . $m->{$k} . '人,' . $m->{$k})->implode('|');
	$jobs = collect(['job1' => '会社員', 'job2' => '自営・自由業', 'job3' => '公務員', 'job6' => '専業主婦', 'job7' => 'フリーター', 'job8' => '無職', 'job9' => '大学生', 'job10' => '高校生', 'job11' => '中学生以下', 'job4' => 'その他', 'job5' => 'その他学生', 'job_none' => '不明'])
		->map(fn ($label, $k) => $label . '：' . $m->{$k} . '人,' . $m->{$k})->implode('|');
@endphp
<tr>
<th>{{ (int) $m->year }}年{{ (int) $m->month }}月</th>
<td>
クーポン使用回数：<span style="font-size: 25px; font-weight: bold; color: #336699">{{ $m->count }}</span>回<br />
<div style="display: inline-block;">
<div>【性別】</div>
<iframe src="{{ $pie($sex) }}" frameborder="0" width="310" height="190" scrolling="no" style="vertical-align: middle; overflow:hidden"></iframe>
</div>
<div style="display: inline-block;">
<div>【年代】</div>
<iframe src="{{ $pie($ages) }}" frameborder="0" width="310" height="190" scrolling="no" style="vertical-align: middle; overflow:hidden"></iframe>
</div>
<div style="display: inline-block;">
<div>【職業】</div>
<iframe src="{{ $pie($jobs) }}" frameborder="0" width="310" height="190" scrolling="no" style="vertical-align: middle; overflow:hidden"></iframe>
</div>
</td>
</tr>
@endforeach
</table>
@endif

@if ($mailIssues->isNotEmpty())
<p class="h3Title">クーポン管理からメルマガ発行したものの内訳</p>
<small>※最新のものから50件分まで表示します。</small>
<table class="tableBox">
<tr>
<th style="width: auto;">ID</th>
<th style="width: auto;">配信総数</th>
<th style="width: auto;">配信日時</th>
<th style="width: auto;">詳細</th>
</tr>
@foreach ($mailIssues as $mail)
<tr>
<td>{{ $mail->cd }}</td>
<td><span style="font-size: 20px;">{{ $mail->getAttribute('配信総数') }}</span>人</td>
<td>{{ $mail->getAttribute('配信日時') }}</td>
<td>
<div style="margin: 2px; padding: 4px; background: #FFEEEE;">【タイトル】<br />{{ $mail->getAttribute('タイトル') }}</div>
<div style="margin: 2px; padding: 4px; background: #EEFFEE;">【振分条件】<br />{!! str_replace(['&lt;&gt;', '＜＞'], '<br />', e((string) $mail->search_display)) !!}</div>
</td>
</tr>
@endforeach
</table>
@endif

@if ($followIssues->isNotEmpty())
<p class="h3Title">フォロー・自動メールに現状設定されている数： <span style="font-size: 25px; font-weight: bold; color: #EE0000;">{{ $followIssues->count() }}</span> 設定</p>
<small>※過去に設定して、設定解除したフォロー・自動メールに関しては解析不可能です。</small>
<table class="tableBox">
<tr>
<th style="width: auto;">ID</th>
<th style="width: auto;">配信時間</th>
<th style="width: auto;">条件</th>
</tr>
@foreach ($followIssues as $mail)
<tr>
<td>{{ $mail->cd }}</td>
<td>{{ $mail->getAttribute('配信日時') }}</td>
<td>{{ $mail->search_display }}</td>
</tr>
@endforeach
</table>
@endif

<input type="image" id="reserve_list_btn_dummy" src="{{ asset('legacy/system/img/btn_back_list_off.gif') }}" value="戻る" onclick="document.reserve_list.submit()" />
@endif
</div>
</div>
</div>
@endsection

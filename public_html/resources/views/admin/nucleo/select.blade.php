{{-- システム選択: system/select.php del legacy (sin sus efectos secundarios: sync con POS, normalizaciones) --}}
@extends('layouts.legacy')

@section('title', 'システム選択 - ' . $legacyUi->siteName())

@section('top_page', '1')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
@if ($legacyUi->uiVersion() === '2')
	$('.functionSelect li a, .functionSelect2 li a').vRippleEffect({ color: '#FFFFFF' });
@endif
});

</script>
@endsection

@section('content')
<div id="systemSelectContents" class="contents">

<p id="systemSelectTitle" class="h2Title">■機能選択</p>

<p id="systemSelectExplain">ご利用になりたい機能を選択してください。</p>

<ul class="functionSelect{{ $modules->count() > 5 ? '2' : '' }}">@foreach ($modules as $module)<li id="functionSelect_{{ $module->cd }}"><a onclick="javascript:location.href='category{{ $module->cd }}/';">{!! $legacyUi->part('functionSelect', ['cd' => $module->cd, 'system_name' => $module->system_name]) !!}</a></li>@endforeach @if ($legacyUi->isCrmsDomain())<li id="functionSelect_support"><a href="http://crm-s.jp/user_support/" target="_blank">{!! $legacyUi->part('functionSelect', ['cd' => '_support', 'system_name' => 'サポート']) !!}</a></li>@endif</ul>


<!-- / .contents --></div>
@endsection

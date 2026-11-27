{{-- Cabecera del legacy (inc/header.php): logo, subnavegación, pestañas de módulos y accesos directos.
     Variables: $legacyUi, $staff, $category, $isTopPage (システム選択: sin pestañas ni 総合トップ) --}}
<div id="header">
<div id="logo"><a href="/system/select.php">{!! $legacyUi->part('headerLogo') !!}</a></div>

<ul class="subNavi">
@if ($shiireUrl = $legacyUi->shiireUrl($staff))
<li><a href="{{ $shiireUrl }}" target="_blank">{!! $legacyUi->part('headerShiire') !!}</a></li>
@endif
<li><a href="javascript:void(0)" onclick="javascript:window.open('/system/contact/?wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');return false">{!! $legacyUi->part('headerOtoiawase') !!}</a></li>
@if ($legacyUi->isCrmsDomain())
<li><a href="http://crm-s.jp/user_support/" target="_blank">{!! $legacyUi->part('headerSupport') !!}</a></li>
@endif
@unless ($isTopPage)
<li><a href="/system/select.php">{!! $legacyUi->part('headerBackTop') !!}</a></li>
@endunless
<li><a href="/system/logout.php">{!! $legacyUi->part('headerLogout') !!}</a></li>
</ul>

@unless ($isTopPage)
<div class="functionSelect">
	<ul>@foreach ($legacyUi->visibleModules($staff) as $module)<li class="{{ $module->cd === $category ? ' selected ' : '' }}"><a href="/system/category{{ $module->cd }}/" id="selectCategory{{ $module->cd }}"><span>{{ $module->system_name }}</span></a></li>@endforeach</ul>
</div>
@endunless

@php
    $bookmarks = $legacyUi->bookmarks($staff);
    $bookmarkAreaFlg = request()->cookie('bookmarkArea') === 'block' ? 1 : 2;
@endphp
@if ($bookmarks->isNotEmpty())
<div id="bookmarkArea"{!! $isTopPage ? $legacyUi->part('bookmarkAreaProperty') : '' !!}>
<ul id="bookmarkAreaList"{!! $bookmarkAreaFlg === 1 ? $legacyUi->part('bookmarkAreaProperty2') : '' !!}>
@foreach ($bookmarks as $bookmark)
<li><a href="{{ $bookmark->url }}">{{ $bookmark->bookmark_name }}</a></li>
@endforeach
</ul>
<div id="openBookmarkArea">{!! $legacyUi->part('bookmarkArea', ['bookmarkArea_flg' => $bookmarkAreaFlg]) !!}</div>
<!-- / div#bookmarkArea END -->
</div>
@endif

<!-- / #header --></div>

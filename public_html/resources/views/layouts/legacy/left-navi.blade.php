{{-- Menú lateral del legacy (inc/left_navi<N>.php) armado desde config/menu/<clave>.php. Variables: $legacyUi, $staff, $menu --}}
<div class="leftNavi">
<div class="leftNavi1">

<p class="{{ $menu['login_class'] }}">
<b>LOGIN NAME</b>
<span>{{ $staff?->loginUserName() }}</span>
</p>

@foreach ($menu['blocks'] as $block)
<dl class="{{ $block['ac'] }} accordionBlock">
<dt>@if (! empty($block['image_only']))<img src="/system/img/{{ $block['image'] }}" width="195" height="61" alt="{{ $block['title'] }}" />@else{!! $legacyUi->part('leftNavi', ['systemName' => $block['title'], 'imageName' => $block['image'] ?? '']) !!}@endif</dt>
<dd>
<ul>
@foreach ($block['links'] as $link)
<li><a href="{{ $link['url'] }}"@if (! empty($link['target'])) target="{{ $link['target'] }}"@endif>{{ $link['label'] }}</a></li>
@endforeach
</ul>
</dd>
</dl>
@endforeach

<!-- / .leftNavi1 --></div>
<!-- / .leftNavi --></div>

@props([
    'variant' => 'default',
    'href' => null,
    'icon' => null,
    'type' => 'button',
])

@php
    $clases = [
        'inline-flex h-10.5 items-center justify-center gap-2 rounded-ctl px-4 text-sm font-bold whitespace-nowrap transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/40',
        'border border-accent bg-accent text-on-accent hover:bg-accent/90' => $variant === 'primary',
        'border border-line bg-surface text-ink hover:bg-surface2' => $variant === 'default',
        'border border-transparent text-danger hover:bg-danger/10' => $variant === 'danger',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>
        @if ($icon) <x-icon :name="$icon" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($clases) }}>
        @if ($icon) <x-icon :name="$icon" /> @endif
        {{ $slot }}
    </button>
@endif

@props(['label', 'value', 'hint' => null, 'icon' => null])

<div {{ $attributes->class('flex flex-col gap-1 rounded-card border border-line bg-surface p-5') }}>
    <span class="flex items-center gap-2 text-xs font-bold text-muted">
        @if ($icon) <x-icon :name="$icon" :size="16" /> @endif
        {{ $label }}
    </span>
    <span class="tnum text-3xl leading-tight font-extrabold">{{ $value }}</span>
    @if ($hint)
        <span class="text-xs text-faint">{{ $hint }}</span>
    @endif
</div>

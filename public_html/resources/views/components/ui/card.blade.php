@props(['pad' => true])

<div {{ $attributes->class(['rounded-card border border-line bg-surface', 'p-5' => $pad]) }}>
    {{ $slot }}
</div>

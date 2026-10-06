@props(['tone' => 'neutral'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold whitespace-nowrap',
    'bg-surface2 text-muted' => $tone === 'neutral',
    'bg-accent-soft text-accent-fg' => $tone === 'accent',
    'border border-line bg-surface text-muted' => $tone === 'outline',
]) }}>
    {{ $slot }}
</span>

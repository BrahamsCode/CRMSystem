@props([
    // [clave => etiqueta]
    'items',
    'current',
    // Clave → URL
    'href',
    'counts' => [],
])

<nav {{ $attributes->class('flex flex-wrap gap-1 border-b border-line') }} aria-label="Secciones">
    @foreach ($items as $key => $label)
        <a href="{{ $href($key) }}" @if ($key === $current) aria-current="page" @endif
           @class([
               '-mb-px inline-flex items-center gap-2 border-b-2 px-3.5 py-2.5 text-sm font-bold transition-colors',
               'border-accent text-ink' => $key === $current,
               'border-transparent text-muted hover:text-ink' => $key !== $current,
           ])>
            {{ $label }}
            @isset($counts[$key])
                <span class="tnum rounded-full bg-surface2 px-2 py-0.5 text-xs">{{ $counts[$key] }}</span>
            @endisset
        </a>
    @endforeach
</nav>

@props(['title' => null, 'description' => null])

<section {{ $attributes->class('overflow-hidden rounded-card border border-line bg-surface') }}>
    @if ($title)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h2 class="text-base font-bold">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-0.5 text-xs text-muted">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>

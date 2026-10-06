@props(['title', 'description' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div>
        <h1 class="text-[26px] leading-tight font-extrabold">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>

@props([
    'id',
    'number',
    'title',
    'description' => null,
    'label' => null,
    'columns' => 2,
])

<section id="{{ $id }}" aria-labelledby="{{ $id }}-h" class="scroll-mt-20 rounded-card border border-line bg-surface">
    <div class="flex flex-wrap items-center gap-2.5 border-b border-line px-5 py-4">
        <h2 id="{{ $id }}-h" class="text-[17px] font-bold">{{ $number }}. {{ $title }}</h2>

        @if ($label)
            <x-ui.badge tone="accent">{{ $label }}</x-ui.badge>
        @endif

        @if ($description)
            <p class="w-full text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    <div @class([
        'grid gap-x-5 gap-y-4 px-5 py-5',
        'sm:grid-cols-2' => $columns === 2,
        'sm:grid-cols-2 lg:grid-cols-3' => $columns === 3,
    ])>
        {{ $slot }}
    </div>
</section>

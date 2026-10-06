@props(['icon' => 'info', 'title'])

<div {{ $attributes->class('flex flex-col items-center gap-2 px-4 py-10 text-center text-sm text-muted') }}>
    <span class="flex h-13 w-13 items-center justify-center rounded-full bg-surface2 text-faint">
        <x-icon :name="$icon" :size="24" />
    </span>
    <strong class="text-ink">{{ $title }}</strong>
    <span class="max-w-80 leading-relaxed">{{ $slot }}</span>

    @isset($action)
        <div class="mt-1.5">{{ $action }}</div>
    @endisset
</div>

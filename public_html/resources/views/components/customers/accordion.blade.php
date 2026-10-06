@props(['key', 'title', 'sub' => null, 'count' => null])

{{-- Depende del objeto Alpine «abierto» declarado por la pantalla que lo usa --}}
<button type="button" @click="abierto.{{ $key }} = ! abierto.{{ $key }}" :aria-expanded="abierto.{{ $key }}"
        class="flex w-full items-center gap-3 border-t border-line px-5 py-3.5 text-left transition-colors hover:bg-surface2/60">
    <span class="flex flex-1 flex-col">
        <span class="font-bold">{{ $title }}</span>
        @if ($sub)
            <span class="text-xs text-faint">{{ $sub }}</span>
        @endif
    </span>

    @if ($count)
        <x-ui.badge tone="accent">{{ $count }}</x-ui.badge>
    @endif

    <x-icon name="chevron-down" class="text-muted transition-transform" ::class="abierto.{{ $key }} && 'rotate-180'" />
</button>

<div x-show="abierto.{{ $key }}" x-cloak x-collapse class="flex flex-col gap-3.5 px-5 pt-1 pb-5">
    {{ $slot }}
</div>

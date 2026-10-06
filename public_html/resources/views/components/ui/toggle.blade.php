@props(['name', 'checked' => false, 'label'])

{{-- Interruptor 0/1: el hidden manda 0 cuando la casilla está desmarcada --}}
<label {{ $attributes->class('inline-flex cursor-pointer items-center gap-3 text-sm font-semibold') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="peer sr-only">
    <span class="relative h-6 w-11 shrink-0 rounded-full bg-line transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40
                 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
    <span>{{ $label }}</span>
</label>

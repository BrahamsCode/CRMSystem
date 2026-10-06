@props([
    'label' => null,
    'hint' => null,
    'required' => false,
    'group' => false,
])

@php
    $etiqueta = $label
        ? '<span class="mb-1.5 block text-xs font-bold text-muted">' . e($label)
            . ($required ? '<span class="text-danger"> *</span>' : '') . '</span>'
        : '';
    $ayuda = $hint ? '<span class="mt-1.5 block text-xs leading-relaxed text-faint">' . e($hint) . '</span>' : '';
@endphp

@if ($group)
    {{-- Agrupa varios controles (rangos, botones de opción): legend en vez de label --}}
    <fieldset {{ $attributes->class('m-0 min-w-0 border-0 p-0') }}>
        @if ($label)
            <legend class="mb-1.5 block text-xs font-bold text-muted">
                {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
            </legend>
        @endif
        {{ $slot }}
        {!! $ayuda !!}
    </fieldset>
@else
    <label {{ $attributes->class('block min-w-0') }}>
        {!! $etiqueta !!}
        {{ $slot }}
        {!! $ayuda !!}
    </label>
@endif

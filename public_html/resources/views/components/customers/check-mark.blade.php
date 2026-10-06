@props(['on' => false])

{{-- Indicador de sí/no en tablas de solo lectura. No es una casilla porque el
     valor se cambia desde el formulario del campo, no desde el listado. --}}
@if ($on)
    <span class="flex h-5 w-5 items-center justify-center rounded bg-accent text-on-accent" title="Sí">
        <x-icon name="check" :size="14" />
    </span>
    <span class="sr-only">Sí</span>
@else
    <span class="flex h-5 w-5 items-center justify-center rounded border border-line" title="No" aria-hidden="true"></span>
    <span class="sr-only">No</span>
@endif

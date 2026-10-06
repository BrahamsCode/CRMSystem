@props(['field', 'value'])

{{-- Botón de selección múltiple. Depende del estado Alpine «actual» y del método «alternar».

     La comparación va siempre en texto: los valores vuelven de la base dentro de
     un json (["3"]) mientras que en la plantilla son números, y comparar 3 con "3"
     dejaba los chips sin marcar al recargar. --}}
<button type="button"
        @click="alternar('{{ $field }}', '{{ $value }}')"
        :aria-pressed="actual.{{ $field }}.map(String).includes('{{ $value }}')"
        {{ $attributes->class('min-h-9.5 min-w-11 rounded-ctl border px-2.5 text-sm transition-colors') }}
        :class="actual.{{ $field }}.map(String).includes('{{ $value }}')
            ? 'border-accent bg-accent font-bold text-on-accent'
            : 'border-line bg-surface font-semibold text-ink hover:bg-surface2'">
    {{ $slot }}
</button>

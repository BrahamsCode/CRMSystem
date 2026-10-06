@props(['rows', 'labels'])

{{-- Tabla de tres interruptores por campo, para las secciones que no son la
     matriz principal: información familiar y categorías personalizadas.
     Depende del objeto Alpine «valores» de la pantalla de Campos y CSV. --}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[880px] text-sm">
        <thead>
            <tr class="border-b border-line bg-surface2">
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-muted">Campo</th>
                <th scope="col" class="w-55 px-2 py-3.5 text-center text-[13px] font-extrabold">Mostrar en</th>
                @foreach ($labels as $label)
                    <th scope="col" class="w-35 px-2 py-3.5 text-center text-[13px] font-extrabold">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th scope="row" class="border-t border-line px-5 py-3 text-left font-semibold">
                        {{ $row['label'] }}
                    </th>
                    <td class="border-t border-line px-2.5 py-3">
                        <x-ui.select class="h-8.5! text-sm" aria-label="Mostrar {{ $row['label'] }} en">
                            <option @selected($row['solo_admin'] ?? false)>Solo administración</option>
                            <option @selected(! ($row['solo_admin'] ?? false))>Administración y móvil</option>
                        </x-ui.select>
                    </td>
                    @foreach ($row['cells'] as $i => $cell)
                        <td class="border-t border-line px-2 py-3 text-center">
                            <input type="checkbox" x-model="valores['{{ $cell['key'] }}']"
                                   aria-label="{{ $row['label'] }}: {{ $labels[$i] }}"
                                   class="h-5 w-5 cursor-pointer accent-[var(--crm-accent)]">
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

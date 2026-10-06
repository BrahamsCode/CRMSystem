<x-layouts.modulo module="promo" :title="$survey->name"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Encuestas', 'route' => 'admin.promotions.surveys.index'], ['label' => $survey->name]]">

    <x-ui.page-header :title="$survey->name" :description="$total . ' respuestas · ' . ($survey->isOpen() ? 'abierta' : 'cerrada')">
        <x-slot:actions>
            <x-ui.btn icon="send" :href="route('admin.promotions.messages.create')">Enviar en un mensaje</x-ui.btn>
            <x-ui.btn icon="pencil" :href="route('admin.promotions.surveys.edit', $survey)">Editar</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <div class="flex flex-wrap items-center gap-2 rounded-card border border-line bg-surface px-5 py-3.5 text-sm">
        <span class="font-bold">Enlace público:</span>
        <a href="{{ $link }}" target="_blank" rel="noopener" class="min-w-0 truncate text-accent-fg hover:underline">{{ $link }}</a>
        <span class="text-faint">· Desde un envío, {encuesta} añade el cliente al enlace para darle el premio.</span>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
        @foreach ($results as $i => $r)
            <x-ui.section :title="($i + 1) . '. ' . $r['question']->name" :description="$r['question']->type->label() . ' · ' . $r['answered'] . ' respuestas'">
                <div class="p-5">
                    @if (isset($r['counts']))
                        @php $max = max(1, max($r['counts'] ?: [0])); @endphp
                        <ul class="flex flex-col gap-2.5">
                            @foreach ($r['counts'] as $opcion => $n)
                                <li class="text-sm">
                                    <div class="flex justify-between gap-2"><span>{{ $opcion }}</span><span class="tnum text-muted">{{ $n }} · {{ $r['answered'] ? round($n / $r['answered'] * 100) : 0 }} %</span></div>
                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface2"><div class="h-full rounded-full bg-accent" style="width: {{ round($n / $max * 100) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    @elseif (array_key_exists('stats', $r))
                        @if ($r['stats'])
                            <dl class="grid grid-cols-3 gap-3 text-center">
                                @foreach (['Mínimo' => 'min', 'Media' => 'avg', 'Máximo' => 'max'] as $label => $k)
                                    <div class="rounded-ctl bg-surface2 p-3"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="tnum text-xl font-extrabold">{{ $r['stats'][$k] }}</dd></div>
                                @endforeach
                            </dl>
                        @else <p class="text-sm text-faint">Sin respuestas.</p> @endif
                    @else
                        <ul class="flex flex-col gap-2 text-sm">
                            @forelse ($r['samples'] as $texto)
                                <li class="rounded-ctl bg-surface2 px-3 py-2">«{{ is_array($texto) ? implode(', ', $texto) : $texto }}»</li>
                            @empty
                                <li class="text-faint">Sin respuestas.</li>
                            @endforelse
                        </ul>
                    @endif
                </div>
            </x-ui.section>
        @endforeach
    </div>

    <x-ui.section title="Últimas respuestas">
        <ul class="divide-y divide-line text-sm">
            @forelse ($responses as $resp)
                <li class="flex items-center gap-3 px-5 py-2.5">
                    <span class="min-w-0 flex-1 truncate">
                        @if ($resp->customer)
                            <a href="{{ route('admin.customers.show', $resp->customer) }}" class="font-bold hover:text-accent-fg">{{ $resp->customer->greetingName() }}</a>
                        @else
                            <span class="text-faint">Anónimo</span>
                        @endif
                    </span>
                    <span class="tnum text-xs text-faint">{{ $resp->answered_at->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li class="px-5 py-4 text-faint">Todavía no hay respuestas.</li>
            @endforelse
        </ul>
        @if ($responses->hasPages())<div class="border-t border-line px-4 py-3">{{ $responses->links() }}</div>@endif
    </x-ui.section>

</x-layouts.modulo>

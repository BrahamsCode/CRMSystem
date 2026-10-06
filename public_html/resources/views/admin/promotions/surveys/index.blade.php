<x-layouts.modulo module="promo" title="Encuestas"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Encuestas']]">

    <x-ui.page-header title="Encuestas" description="Pregunta a tus clientes y regálales un cupón por responder. Se envían enlazadas desde cualquier envío con {encuesta}.">
        <x-slot:actions>
            <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.surveys.create')">Nueva encuesta</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <x-ui.card :pad="false">
        <div class="relative overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                        <th scope="col" class="px-5 py-2.5">Encuesta</th>
                        <th scope="col" class="px-3 py-2.5">Periodo</th>
                        <th scope="col" class="px-3 py-2.5 text-right">Preguntas</th>
                        <th scope="col" class="px-3 py-2.5 text-right">Respuestas</th>
                        <th scope="col" class="px-3 py-2.5">Premio</th>
                        <th scope="col" class="px-5 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($surveys as $s)
                        <tr class="border-t border-line hover:bg-surface2/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.promotions.surveys.show', $s) }}" class="font-bold hover:text-accent-fg">{{ $s->name }}</a>
                                <div class="mt-0.5"><x-ui.badge :tone="$s->isOpen() ? 'accent' : 'outline'">{{ $s->isOpen() ? 'Abierta' : ($s->status === 1 ? 'Fuera de fechas' : 'Cerrada') }}</x-ui.badge></div>
                            </td>
                            <td class="tnum px-3 py-3 text-muted">{{ $s->starts_on?->format('d/m/Y') ?? 'Sin inicio' }} – {{ $s->ends_on?->format('d/m/Y') ?? 'sin fin' }}</td>
                            <td class="tnum px-3 py-3 text-right">{{ $s->questions_count }}</td>
                            <td class="tnum px-3 py-3 text-right font-bold">{{ $s->responses_count }}</td>
                            <td class="px-3 py-3 text-muted">{{ $s->coupon?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right"><x-ui.btn :href="route('admin.promotions.surveys.show', $s)" class="h-8.5 px-3">Resultados</x-ui.btn></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="clipboard" title="No hay encuestas">Crea una y enlázala en un envío.</x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($surveys->hasPages())<div class="border-t border-line px-4 py-3">{{ $surveys->links() }}</div>@endif
    </x-ui.card>

</x-layouts.modulo>

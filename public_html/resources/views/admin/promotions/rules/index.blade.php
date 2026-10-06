<x-layouts.modulo module="promo" title="Automatizaciones"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Automatizaciones']]">

    <x-ui.page-header title="Automatizaciones"
                      description="Mensajes que salen solos: seguimiento tras la visita o el alta, cumpleaños, aniversarios, fechas fijas y recordatorios de reserva.">
        <x-slot:actions>
            <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.rules.create')">Nueva automatización</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    @foreach (['Seguimiento', 'Programados', 'Recordatorios'] as $grupo)
        @continue(! $groups->has($grupo))
        <x-ui.section :title="$grupo">
            <ul class="divide-y divide-line">
                @foreach ($groups[$grupo] as $rule)
                    @php $proximo = $rule->nextRunAt(); @endphp
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-ctl',
                            'bg-accent-soft text-accent-fg' => $rule->status === 1, 'bg-surface2 text-faint' => $rule->status !== 1])>
                            <x-icon :name="$rule->trigger->icon()" :size="20" />
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a href="{{ route('admin.promotions.rules.edit', $rule) }}" class="font-bold hover:text-accent-fg">{{ $rule->name }}</a>
                            <span class="text-sm text-muted">{{ $rule->scheduleLabel() }}</span>
                            <span class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-faint">
                                <span class="inline-flex items-center gap-1"><x-icon :name="$rule->channel->icon()" :size="12" /> {{ $rule->channel->label() }}</span>
                                <span>{{ $rule->shop?->name ?? 'Todas las tiendas' }}</span>
                                @if ($rule->coupon) <span class="inline-flex items-center gap-1"><x-icon name="ticket" :size="12" /> {{ $rule->coupon->name }}</span> @endif
                                <span>{{ $rule->messages_count }} envíos hechos</span>
                            </span>
                        </div>
                        <div class="flex flex-col items-end text-sm">
                            @if ($rule->status === 1)
                                <span class="text-faint">Próximo envío</span>
                                <span class="tnum font-bold">{{ $proximo?->format('d/m/Y H:i') ?? '—' }}</span>
                            @else
                                <x-ui.badge tone="outline">En pausa</x-ui.badge>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.promotions.rules.toggle', $rule) }}">
                                @csrf
                                <x-ui.btn type="submit" class="h-9">{{ $rule->status === 1 ? 'Pausar' : 'Activar' }}</x-ui.btn>
                            </form>
                            <x-ui.btn :href="route('admin.promotions.rules.edit', $rule)" class="h-9" icon="pencil">Editar</x-ui.btn>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endforeach

    @if ($groups->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="zap" title="Todavía no hay automatizaciones">
                Empieza por una de estas, las más usadas:
            </x-ui.empty>
        </x-ui.card>
    @endif

    <section aria-labelledby="ideas">
        <h2 id="ideas" class="mb-3 text-xs font-bold tracking-wider text-faint uppercase">Crear desde un disparador</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($triggers as $t)
                <a href="{{ route('admin.promotions.rules.create', ['trigger' => $t->value]) }}"
                   class="flex flex-col gap-2 rounded-card border border-line bg-surface p-4 transition-colors hover:border-accent/40 hover:bg-surface2">
                    <span class="flex items-center gap-2 font-bold"><x-icon :name="$t->icon()" class="text-accent-fg" /> {{ $t->label() }}</span>
                    <span class="text-xs leading-relaxed text-muted">{{ $t->description() }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <p class="text-xs text-faint">
        Las automatizaciones se revisan cada minuto con el programador de Laravel (<code>php artisan schedule:work</code> en desarrollo, o el cron de <code>schedule:run</code> en el servidor).
    </p>

</x-layouts.modulo>

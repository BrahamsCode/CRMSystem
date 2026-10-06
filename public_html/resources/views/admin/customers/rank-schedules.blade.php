@php
    $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $semana = ['l' => 'Lunes', 'm' => 'Martes', 'x' => 'Miércoles', 'j' => 'Jueves', 'v' => 'Viernes', 's' => 'Sábado', 'd' => 'Domingo'];
@endphp

<x-layouts.modulo title="Asignación de rangos"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Asignación de rangos']]">

    <x-ui.page-header title="Asignación automática de rangos">
        <x-slot:description>
            Programa cuándo el sistema recalcula el rango de cada cliente. Los rangos se definen en
            <a href="{{ route('admin.customers.ranks') }}" class="font-bold text-accent-fg hover:underline">Rangos de clientes</a>.
        </x-slot:description>
    </x-ui.page-header>

    <div x-data="{
             tab: 'importe',
             cfg: @js($config),
             get actual() { return this.cfg[this.tab] },
             alternar(campo, valor) {
                 // Todo se guarda como texto para que coincida con lo que devuelve
                 // el json de la base al recargar la pantalla
                 const lista = this.actual[campo];
                 const i = lista.map(String).indexOf(String(valor));
                 i === -1 ? lista.push(String(valor)) : lista.splice(i, 1);
             },
         }"
         class="flex max-w-[1100px] flex-col gap-5">

        {{-- Pestañas por tipo de rango --}}
        <div role="tablist" aria-label="Tipo de rango" class="flex gap-1 border-b border-line">
            @foreach (['importe' => 'Por importe', 'visits' => 'Por visitas'] as $clave => $texto)
                <button type="button" role="tab" @click="tab = '{{ $clave }}'" :aria-selected="tab === '{{ $clave }}'"
                        class="flex min-h-11.5 items-center gap-2 px-4 text-sm transition-colors"
                        :class="tab === '{{ $clave }}'
                            ? 'font-bold text-ink shadow-[inset_0_-2px_0_var(--crm-accent)]'
                            : 'font-medium text-muted hover:text-ink'">
                    {{ $texto }}
                    <span class="rounded-full px-2 py-px text-[11px] font-extrabold"
                          :class="cfg.{{ $clave }}.activa ? 'bg-green-100 text-green-800' : 'bg-danger/10 text-danger'"
                          x-text="cfg.{{ $clave }}.activa ? 'ON' : 'OFF'"></span>
                </button>
            @endforeach
        </div>

        <x-ui.card :pad="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
                <h2 class="flex-1 text-[17px] font-bold"
                    x-text="tab === 'importe' ? 'Rango por importe de compra' : 'Rango por número de visitas'"></h2>

                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-extrabold"
                      :class="actual.activa ? 'bg-green-100 text-green-800' : 'bg-danger/10 text-danger'">
                    <span class="h-2 w-2 rounded-full" :class="actual.activa ? 'bg-green-600' : 'bg-danger'"></span>
                    Asignación <span x-text="actual.activa ? 'activada' : 'desactivada'"></span>
                </span>
            </div>

            <div class="flex flex-col gap-5.5 p-5.5">

                <label class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-bold text-muted">Periodo a evaluar</span>
                    <span class="text-sm">Últimos</span>
                    <x-ui.input type="number" min="1" placeholder="90" x-model="actual.periodo" class="w-25!" />
                    <span class="text-sm">días</span>
                    <span class="w-full text-xs text-faint">
                        Días hacia atrás, contados desde el momento en que se ejecuta la asignación.
                    </span>
                </label>

                <fieldset class="m-0 min-w-0 border-0 p-0">
                    <legend class="mb-1.5 text-xs font-bold text-muted">Cuándo se ejecuta</legend>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach (\App\Enums\RankScheduleMode::cases() as $modo)
                            @php [$value, $title, $desc] = [$modo->value, $modo->label(), $modo->description()]; @endphp
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-ctl border-2 p-3.5 transition-colors"
                                   :class="Number(actual.modo) === {{ $value }} ? 'border-accent' : 'border-line'">
                                <input type="radio" :name="tab + '-modo'" value="{{ $value }}" x-model="actual.modo"
                                       class="mt-1 accent-[var(--crm-accent)]">
                                <span>
                                    <span class="block font-bold">{{ $title }}</span>
                                    <span class="mt-1 block text-sm text-muted">{{ $desc }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- Meses y días --}}
                <template x-if="Number(actual.modo) === {{ \App\Enums\RankScheduleMode::ByDate->value }}">
                    <div class="flex flex-col gap-5.5">
                        <fieldset class="m-0 min-w-0 border-0 p-0">
                            <legend class="mb-1.5 text-xs font-bold text-muted">Meses</legend>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($months as $i => $mes)
                                    <x-customers.chip field="meses" :value="$i + 1">{{ $mes }}</x-customers.chip>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="m-0 min-w-0 border-0 p-0">
                            <legend class="mb-1.5 text-xs font-bold text-muted">Días</legend>
                            <div class="grid max-w-140 grid-cols-6 gap-1.5 sm:grid-cols-8">
                                @for ($d = 1; $d <= 31; $d++)
                                    <x-customers.chip field="dias" :value="$d">{{ $d }}</x-customers.chip>
                                @endfor
                                <x-customers.chip field="dias" value="fin" class="col-span-2">Fin de mes</x-customers.chip>
                            </div>
                        </fieldset>
                    </div>
                </template>

                {{-- Días de la semana --}}
                <template x-if="Number(actual.modo) === {{ \App\Enums\RankScheduleMode::ByWeekday->value }}">
                    <fieldset class="m-0 min-w-0 border-0 p-0">
                        <legend class="mb-1.5 text-xs font-bold text-muted">Días de la semana</legend>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($semana as $clave => $dia)
                                <x-customers.chip field="semana" :value="$clave">{{ $dia }}</x-customers.chip>
                            @endforeach
                        </div>
                    </fieldset>
                </template>

                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-bold text-muted">Hora de ejecución</span>
                    <x-ui.select aria-label="Hora" class="w-27!" x-model.number="actual.hora">
                        @foreach ([0, 3, 6, 12, 18, 23] as $h)
                            <option value="{{ $h }}">{{ str_pad((string) $h, 2, '0', STR_PAD_LEFT) }} h</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.select aria-label="Minutos" class="w-30!" x-model.number="actual.minuto">
                        @foreach ([0, 15, 30, 45] as $m)
                            <option value="{{ $m }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }} min</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.customers.rank-schedules.store') }}"
                  class="flex flex-wrap justify-end gap-2 border-t border-line px-5.5 py-3.5">
                @csrf
                {{-- El estado vive en Alpine; se vuelca a campos ocultos al enviar --}}
                <input type="hidden" name="type" :value="tab === 'importe' ? {{ \App\Enums\RankType::Amount->value }} : {{ \App\Enums\RankType::Visits->value }}">
                <input type="hidden" name="enabled_flg" x-ref="activa" value="1">
                <input type="hidden" name="mode" :value="actual.modo">
                <input type="hidden" name="period_days" :value="actual.periodo">
                <input type="hidden" name="run_hour" :value="actual.hora">
                <input type="hidden" name="run_minute" :value="actual.minuto">
                <template x-for="m in actual.meses" :key="'m' + m">
                    <input type="hidden" name="months[]" :value="m">
                </template>
                <template x-for="d in actual.dias" :key="'d' + d">
                    <input type="hidden" name="days[]" :value="d">
                </template>
                <template x-for="w in actual.semana" :key="'w' + w">
                    <input type="hidden" name="weekdays[]" :value="w">
                </template>

                <x-ui.btn type="submit" @click="$refs.activa.value = 0">Desactivar asignación</x-ui.btn>
                <x-ui.btn type="submit" variant="primary" @click="$refs.activa.value = 1">Guardar y activar</x-ui.btn>
            </form>
        </x-ui.card>
    </div>

</x-layouts.modulo>

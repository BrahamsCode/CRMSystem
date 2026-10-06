<x-layouts.modulo title="Motivos de primera visita"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Motivos de 1ª visita']]">

    <x-ui.page-header title="Motivos de primera visita"
                      description="Opciones que se eligen en «Motivo de primera visita» al registrar un cliente." />

    <div x-data="{
             nombre: '',
             motivos: @js($motives->pluck('name')->values()),
             agregar() {
                 const n = this.nombre.trim();
                 if (! n) return;
                 this.motivos.push(n);
                 this.nombre = '';
             },
             mover(i, d) {
                 const j = i + d;
                 if (j < 0 || j >= this.motivos.length) return;
                 [this.motivos[i], this.motivos[j]] = [this.motivos[j], this.motivos[i]];
             },
         }"
         class="grid items-start gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">

        <x-ui.section title="Nuevo motivo">
            <form @submit.prevent="agregar()" class="flex flex-col gap-4 px-5 py-5">
                <x-ui.field label="Nombre del motivo">
                    <x-ui.input placeholder="Ej. Recomendación de un amigo" x-model="nombre" />
                </x-ui.field>
                <x-ui.btn type="submit" variant="primary">Registrar motivo</x-ui.btn>
            </form>
        </x-ui.section>

        <x-ui.section title="Motivos registrados">
            <x-slot:actions>
                <span class="text-sm text-muted">
                    <span x-text="motivos.length"></span>
                    <span x-text="motivos.length === 1 ? 'resultado' : 'resultados'"></span>
                </span>
            </x-slot:actions>

            <div x-show="! motivos.length" x-cloak>
                <x-ui.empty icon="target" title="No hay motivos registrados">
                    Crea el primero con el formulario.
                </x-ui.empty>
            </div>

            <div x-show="motivos.length" class="overflow-x-auto">
                <table class="w-full min-w-120 text-sm">
                    <thead>
                        <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                            <th scope="col" class="px-4 py-2.5">Motivo</th>
                            <th scope="col" class="w-48 px-4 py-2.5">Acciones</th>
                            <th scope="col" class="w-33 px-4 py-2.5">Orden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(m, i) in motivos" :key="m">
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-bold" x-text="m"></td>
                                <td class="px-4 py-3">
                                    <span class="flex gap-1.5">
                                        <x-ui.btn class="h-8.5 px-3">Editar</x-ui.btn>
                                        <x-ui.btn variant="danger" class="h-8.5 px-3"
                                                  @click="motivos.splice(i, 1)">Eliminar</x-ui.btn>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="flex items-center gap-1">
                                        <span class="min-w-5.5 text-muted" x-text="i + 1"></span>
                                        <button type="button" @click="mover(i, -1)" :aria-label="'Subir ' + m"
                                                class="flex h-9 w-9 items-center justify-center rounded-ctl text-muted hover:bg-surface2">&uarr;</button>
                                        <button type="button" @click="mover(i, 1)" :aria-label="'Bajar ' + m"
                                                class="flex h-9 w-9 items-center justify-center rounded-ctl text-muted hover:bg-surface2">&darr;</button>
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-ui.section>
    </div>

</x-layouts.modulo>

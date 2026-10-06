<x-layouts.modulo title="Grupos de clientes"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Grupos de clientes']]">

    <x-ui.page-header title="Grupos de clientes"
                      description="Crea grupos para clasificar a tus clientes y filtrarlos en las búsquedas." />

    <div x-data="{
             nombre: '',
             inicial: false,
             grupos: @js($groups->map(fn ($g) => [
                 'name' => $g->name,
                 'inicial' => $g->default_flg === 1,
                 'clientes' => $g->customers_count,
             ])->values()),
             agregar() {
                 const n = this.nombre.trim();
                 if (! n) return;
                 // Solo un grupo puede ser el valor inicial
                 if (this.inicial) this.grupos.forEach(g => g.inicial = false);
                 this.grupos.push({ nombre: n, inicial: this.inicial });
                 this.nombre = '';
                 this.inicial = false;
             },
             mover(i, d) {
                 const j = i + d;
                 if (j < 0 || j >= this.grupos.length) return;
                 [this.grupos[i], this.grupos[j]] = [this.grupos[j], this.grupos[i]];
             },
         }"
         class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">

        <x-ui.section title="Nuevo grupo">
            <form @submit.prevent="agregar()" class="flex flex-col gap-4 px-5 py-5">
                <x-ui.field label="Nombre del grupo">
                    <x-ui.input placeholder="Ej. Clientes VIP" x-model="nombre" />
                </x-ui.field>

                <label class="flex cursor-pointer items-start gap-2.5">
                    <input type="checkbox" x-model="inicial" class="mt-0.5 h-4.5 w-4.5 shrink-0 accent-[var(--crm-accent)]">
                    <span>
                        <span class="block text-sm font-bold">Valor inicial para clientes nuevos</span>
                        <span class="mt-1 block text-xs leading-relaxed text-faint">
                            Solo un grupo puede ser el valor inicial: al marcarlo se quita del grupo anterior.
                        </span>
                    </span>
                </label>

                <x-ui.btn type="submit" variant="primary">Registrar grupo</x-ui.btn>
            </form>
        </x-ui.section>

        <x-ui.section title="Grupos registrados">
            <x-slot:actions>
                <span class="text-sm text-muted">
                    <span x-text="grupos.length"></span> <span x-text="grupos.length === 1 ? 'grupo' : 'grupos'"></span>
                </span>
            </x-slot:actions>

            <div x-show="! grupos.length">
                <x-ui.empty icon="layers" title="Todavía no hay grupos de clientes">
                    Crea el primero con el formulario.
                </x-ui.empty>
            </div>

            <div x-show="grupos.length" x-cloak class="relative overflow-x-auto">
                <table class="w-full min-w-120 text-sm">
                    <thead>
                        <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                            <th scope="col" class="px-4 py-2.5">Nombre</th>
                            <th scope="col" class="w-28 px-4 py-2.5">Clientes</th>
                            <th scope="col" class="w-48 px-4 py-2.5">Acciones</th>
                            <th scope="col" class="w-28 px-4 py-2.5">Orden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(g, i) in grupos" :key="g.nombre">
                            <tr class="border-t border-line">
                                <td class="px-4 py-3">
                                    <span class="flex items-center gap-2.5 font-bold">
                                        <span x-text="g.nombre"></span>
                                        <template x-if="g.inicial">
                                            <span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs font-bold text-accent-fg">
                                                Valor inicial
                                            </span>
                                        </template>
                                    </span>
                                </td>
                                <td class="tnum px-4 py-3 text-muted" x-text="g.clientes ?? 0"></td>
                                <td class="px-4 py-3">
                                    <span class="flex gap-1.5">
                                        <x-ui.btn class="h-8.5 px-3">Editar</x-ui.btn>
                                        <x-ui.btn variant="danger" class="h-8.5 px-3"
                                                  @click="grupos.splice(i, 1)">Eliminar</x-ui.btn>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="flex gap-1">
                                        <button type="button" @click="mover(i, -1)" :aria-label="'Subir ' + g.nombre"
                                                class="flex h-9 w-9 items-center justify-center rounded-ctl text-muted hover:bg-surface2">&uarr;</button>
                                        <button type="button" @click="mover(i, 1)" :aria-label="'Bajar ' + g.nombre"
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

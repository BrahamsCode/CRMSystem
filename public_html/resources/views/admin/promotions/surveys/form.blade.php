@php
    $editing = $survey->exists;
    $titulo = $editing ? 'Editar encuesta' : 'Nueva encuesta';
    $tipos = collect($types)->mapWithKeys(fn ($t) => [$t->value => ['label' => $t->label(), 'options' => $t->hasOptions()]]);
@endphp

<x-layouts.modulo module="promo" :title="$titulo"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Encuestas', 'route' => 'admin.promotions.surveys.index'], ['label' => $titulo]]">

    <div class="flex max-w-[1000px] flex-col gap-5">
        <x-ui.page-header :title="$titulo" />
        <x-ui.flash />

        @if ($hasResponses)
            <p role="note" class="rounded-card bg-accent-soft px-5 py-3.5 text-sm">
                Esta encuesta ya tiene respuestas. Cambiar el texto de una pregunta conserva sus respuestas; quitar una pregunta oculta sus respuestas de los resultados.
            </p>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.promotions.surveys.update', $survey) : route('admin.promotions.surveys.store') }}"
              class="flex flex-col gap-5"
              x-data="{
                  preguntas: @js(array_values($questions)),
                  tipos: @js($tipos),
                  agregar() { this.preguntas.push({ id: null, name: '', type: 8, required_flg: 0, options: '' }) },
                  quitar(i) { this.preguntas.splice(i, 1) },
                  mover(i, d) { const j = i + d; if (j < 0 || j >= this.preguntas.length) return; [this.preguntas[i], this.preguntas[j]] = [this.preguntas[j], this.preguntas[i]] },
              }">
            @csrf
            @if ($editing) @method('PUT') @endif

            <x-ui.section title="Encuesta">
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <x-ui.field label="Título" required class="md:col-span-2"><x-ui.input name="name" :value="old('name', $survey->name)" required /></x-ui.field>
                    <x-ui.field label="Introducción" class="md:col-span-2"><x-ui.textarea name="description" :rows="2">{{ old('description', $survey->description) }}</x-ui.textarea></x-ui.field>
                    <x-ui.field label="Desde"><x-ui.input type="date" name="starts_on" :value="old('starts_on', $survey->starts_on?->toDateString())" /></x-ui.field>
                    <x-ui.field label="Hasta"><x-ui.input type="date" name="ends_on" :value="old('ends_on', $survey->ends_on?->toDateString())" /></x-ui.field>
                    <x-ui.field label="Premio por responder" hint="Cupones de tipo «Premio de encuesta».">
                        <x-ui.select name="coupon_id">
                            <option value="">Sin premio</option>
                            @foreach ($rewards as $c) <option value="{{ $c->id }}" @selected((int) old('coupon_id', $survey->coupon_id) === $c->id)>{{ $c->name }}</option> @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Tienda">
                        <x-ui.select name="shop_id">
                            <option value="">Todas</option>
                            @foreach ($shops as $shop) <option value="{{ $shop->id }}" @selected((int) old('shop_id', $survey->shop_id) === $shop->id)>{{ $shop->name }}</option> @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Mensaje de agradecimiento" class="md:col-span-2"><x-ui.input name="thanks_message" :value="old('thanks_message', $survey->thanks_message)" placeholder="¡Gracias por tu opinión!" /></x-ui.field>
                    <x-ui.toggle name="status" :checked="(int) old('status', $survey->status) === 1" label="Abierta" />
                </div>
            </x-ui.section>

            <x-ui.section title="Preguntas">
                <ol class="flex flex-col divide-y divide-line">
                    <template x-for="(p, i) in preguntas" :key="i">
                        <li class="grid gap-3 p-5 md:grid-cols-[32px_minmax(0,1fr)_220px_auto]">
                            <span class="tnum flex h-8 w-8 items-center justify-center rounded-full bg-surface2 text-sm font-extrabold" x-text="i + 1"></span>
                            <div class="flex flex-col gap-2">
                                <input type="hidden" :name="'questions[' + i + '][id]'" :value="p.id">
                                <input type="text" :name="'questions[' + i + '][name]'" x-model="p.name" required placeholder="Texto de la pregunta" aria-label="Pregunta"
                                       class="h-10 w-full rounded-ctl border border-line bg-surface px-3 text-sm focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none">
                                <textarea x-show="tipos[p.type]?.options" :name="'questions[' + i + '][options]'" x-model="p.options" rows="3"
                                          placeholder="Una opción por línea" aria-label="Opciones"
                                          class="w-full rounded-ctl border border-line bg-surface px-3 py-2 text-sm focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none"></textarea>
                            </div>
                            <div class="flex flex-col gap-2">
                                <select :name="'questions[' + i + '][type]'" x-model.number="p.type" aria-label="Tipo"
                                        class="h-10 w-full rounded-ctl border border-line bg-surface px-3 text-sm">
                                    <template x-for="(t, v) in tipos" :key="v"><option :value="v" x-text="t.label" :selected="Number(v) === p.type"></option></template>
                                </select>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="hidden" :name="'questions[' + i + '][required_flg]'" :value="p.required_flg ? 1 : 0">
                                    <input type="checkbox" x-model="p.required_flg" class="accent-[var(--crm-accent)]"> Obligatoria
                                </label>
                            </div>
                            <div class="flex items-start gap-1">
                                <button type="button" @click="mover(i, -1)" class="h-9 w-9 rounded-ctl hover:bg-surface2" aria-label="Subir">↑</button>
                                <button type="button" @click="mover(i, 1)" class="h-9 w-9 rounded-ctl hover:bg-surface2" aria-label="Bajar">↓</button>
                                <button type="button" @click="quitar(i)" x-show="preguntas.length > 1" class="flex h-9 w-9 items-center justify-center rounded-ctl text-danger hover:bg-danger/10" aria-label="Quitar"><x-icon name="trash" :size="16" /></button>
                            </div>
                        </li>
                    </template>
                </ol>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
                    <x-ui.btn icon="plus" x-on:click="agregar()">Añadir pregunta</x-ui.btn>
                    <div class="flex gap-2">
                        @if ($editing)
                            <x-ui.btn type="submit" variant="danger" icon="trash" form="borrar" onclick="return confirm('¿Eliminar la encuesta y sus respuestas?')">Eliminar</x-ui.btn>
                        @endif
                        <x-ui.btn type="submit" variant="primary">{{ $editing ? 'Guardar cambios' : 'Crear encuesta' }}</x-ui.btn>
                    </div>
                </div>
            </x-ui.section>
        </form>

        @if ($editing)
            <form id="borrar" method="POST" action="{{ route('admin.promotions.surveys.destroy', $survey) }}" class="hidden">@csrf @method('DELETE')</form>
        @endif
    </div>

</x-layouts.modulo>

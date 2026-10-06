@php
    use App\Enums\CustomFieldType;
    use App\Enums\DisplayScope;

    $editing = (bool) $field;
    $title = $editing ? "Editar campo «{$field->name}»" : "Nuevo campo en «{$category->name}»";
    $action = $editing
        ? route('admin.customers.custom-fields.update', $field)
        : route('admin.customers.custom-fields.store');

    $typesWithOptions = collect(CustomFieldType::cases())
        ->filter(fn (CustomFieldType $t) => $t->hasOptions())
        ->map(fn (CustomFieldType $t) => $t->value)
        ->values();

    // Opciones a pintar: las guardadas, las reenviadas tras un error, o dos vacías
    $options = old('opciones', $field?->options
        ->map(fn ($o) => ['label' => $o->label, 'value' => $o->value])
        ->all() ?: [['label' => '', 'value' => ''], ['label' => '', 'value' => '']]);
@endphp

<x-layouts.modulo :title="$title"
                  :crumbs="[
                      ['label' => 'Información adicional', 'route' => 'admin.customers.custom-categories'],
                      ['label' => 'Campos', 'route' => 'admin.customers.custom-fields'],
                      ['label' => $editing ? $field->name : 'Nuevo campo'],
                  ]">

    <div x-data="{
             tipo: {{ old('type', $field?->type?->value ?? CustomFieldType::Text->value) }},
             tiposConOpciones: @js($typesWithOptions),
             filas: {{ max(count($options), 1) }},
             get conOpciones() { return this.tiposConOpciones.includes(Number(this.tipo)) },
         }"
         class="flex max-w-[760px] flex-col gap-5">

        <div>
            <a href="{{ route('admin.customers.custom-fields', ['category' => $category->id]) }}"
               class="text-sm font-bold text-accent-fg hover:underline">
                &larr; Volver a los campos de «{{ $category->name }}»
            </a>
            <h1 class="mt-1.5 text-[26px] leading-tight font-extrabold">{{ $title }}</h1>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-card border border-danger/30 bg-danger/10 px-5 py-4 text-sm">
                <strong class="block text-danger">Revisa los datos:</strong>
                <ul class="mt-2 list-inside list-disc text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="flex flex-col gap-5">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="custom_category_id" value="{{ $category->id }}">

            <x-ui.card class="flex flex-col gap-4 p-5.5">
                <x-ui.field label="Título del campo" required>
                    <x-ui.input name="name" required autofocus :value="old('name', $field?->name)" />
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Tipo de campo">
                        <select name="type" x-model.number="tipo"
                                class="h-10 w-full rounded-ctl border border-line bg-surface px-3 text-sm">
                            @foreach (CustomFieldType::cases() as $type)
                                <option value="{{ $type->value }}"
                                        @selected(old('type', $field?->type?->value) == $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Unidad" hint="Se muestra junto al valor. Ej. Kg.">
                        <x-ui.input name="unit" placeholder="Ej. Kg" :value="old('unit', $field?->unit)" />
                    </x-ui.field>
                </div>

                <x-ui.field label="Comentario">
                    <x-ui.textarea name="note" :rows="2"
                                   placeholder="Texto de ayuda para quien rellena el campo">{{ old('note', $field?->note) }}</x-ui.textarea>
                </x-ui.field>

                {{-- Opciones: solo para selección, radio y casillas --}}
                <template x-if="conOpciones">
                    <fieldset class="m-0 flex min-w-0 flex-col gap-2.5 rounded-card border border-line p-3.5">
                        <legend class="px-1.5 text-xs font-bold text-muted">Opciones</legend>
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold text-muted">
                            <span>Texto que se muestra</span>
                            <span>Valor que se guarda</span>
                        </div>

                        @foreach ($options as $i => $option)
                            <div class="grid grid-cols-2 gap-2">
                                <x-ui.input name="opciones[{{ $i }}][label]"
                                            :value="$option['label'] ?? ''"
                                            :aria-label="'Texto de la opción ' . ($i + 1)"
                                            :placeholder="'Opción ' . ($i + 1)" />
                                <x-ui.input name="opciones[{{ $i }}][value]"
                                            :value="$option['value'] ?? ''"
                                            :aria-label="'Valor de la opción ' . ($i + 1)"
                                            placeholder="Se deriva del texto si lo dejas vacío" />
                            </div>
                        @endforeach

                        {{-- Las filas añadidas en caliente se envían igual que las de arriba --}}
                        <template x-for="n in Math.max(filas - {{ count($options) }}, 0)" :key="n">
                            <div class="grid grid-cols-2 gap-2">
                                <x-ui.input ::name="'opciones[' + ({{ count($options) }} + n - 1) + '][label]'"
                                            ::aria-label="'Texto de la opción ' + ({{ count($options) }} + n)"
                                            ::placeholder="'Opción ' + ({{ count($options) }} + n)" />
                                <x-ui.input ::name="'opciones[' + ({{ count($options) }} + n - 1) + '][value]'"
                                            ::aria-label="'Valor de la opción ' + ({{ count($options) }} + n)"
                                            placeholder="Se deriva del texto si lo dejas vacío" />
                            </div>
                        </template>

                        <x-ui.btn class="self-start" @click="filas++">+ Añadir opción</x-ui.btn>
                        <p class="text-xs text-faint">Las opciones vacías se descartan al guardar.</p>
                    </fieldset>
                </template>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Dónde se muestra">
                        <x-ui.select name="display_scope">
                            @foreach (DisplayScope::cases() as $scope)
                                <option value="{{ $scope->value }}"
                                        @selected(old('display_scope', $field?->display_scope?->value ?? DisplayScope::Both->value) == $scope->value)>
                                    {{ $scope->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Tamaño del campo">
                        <x-ui.select name="size">
                            <option value="">Por defecto</option>
                            @foreach (\App\Enums\FieldSize::cases() as $tamano)
                                <option value="{{ $tamano->value }}"
                                        @selected(old('size', $field?->size?->value) == $tamano->value)>
                                    {{ $tamano->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>

                <fieldset class="m-0 flex flex-col gap-2.5 border-0 p-0">
                    <legend class="mb-1.5 text-xs font-bold text-muted">Comportamiento</legend>

                    @foreach ([
                        'required_flg' => ['Obligatorio', 'No se puede guardar el cliente sin rellenarlo.'],
                        'search_flg' => ['Filtro de búsqueda', 'Aparece como criterio en «Buscar clientes».'],
                        'mail_magazine_flg' => ['Variable de newsletter', 'Su valor se puede insertar en los correos.'],
                    ] as $name => [$label, $help])
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1"
                                   @checked(old($name, $field?->{$name} ?? 0))
                                   class="mt-0.5 h-4.5 w-4.5 shrink-0 accent-[var(--crm-accent)]">
                            <span>
                                <span class="block text-sm font-bold">{{ $label }}</span>
                                <span class="mt-1 block text-xs text-faint">{{ $help }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            </x-ui.card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-ui.btn :href="route('admin.customers.custom-fields', ['category' => $category->id])">Cancelar</x-ui.btn>
                <x-ui.btn type="submit" variant="primary">
                    {{ $editing ? 'Guardar campo' : 'Crear campo' }}
                </x-ui.btn>
            </div>
        </form>

        @if ($editing)
            <x-ui.card class="flex flex-wrap items-center gap-3 border-danger/30 p-4.5">
                <span class="flex-1 text-sm">
                    <strong class="block">Eliminar este campo</strong>
                    <span class="text-muted">Los valores guardados en las fichas dejan de mostrarse.</span>
                </span>
                <form method="POST" action="{{ route('admin.customers.custom-fields.destroy', $field) }}"
                      onsubmit="return confirm('¿Eliminar el campo «{{ $field->name }}»?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.btn type="submit" variant="danger">Eliminar campo</x-ui.btn>
                </form>
            </x-ui.card>
        @endif
    </div>

</x-layouts.modulo>

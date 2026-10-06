@props([
    'channel',
    'subject' => '',
    'body' => '',
    'channels',
    'templates',
    'variables',
    // Nombre del cliente de muestra para la vista previa
    'sample' => 'Tanaka Yui',
])

@php
    $channelValue = $channel instanceof \App\Enums\MessageChannel ? $channel->value : (int) $channel;
@endphp

{{-- Canal, plantilla, título y contenido con vista previa. Lo usan envíos y automatizaciones. --}}
<div class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_340px]"
     x-data="{
         canal: {{ old('channel', $channelValue) }},
         titulo: @js(old('subject', $subject)),
         cuerpo: @js(old('body', $body)),
         plantillas: @js($templates->keyBy('id')->map->only(['subject', 'body', 'html_flg'])),
         muestra: @js($sample),
         usar(id) {
             const t = this.plantillas[id];
             if (! t) return;
             if ((this.titulo || this.cuerpo) && ! confirm('¿Reemplazar el título y el contenido por la plantilla?')) return;
             this.titulo = t.subject ?? '';
             this.cuerpo = t.body ?? '';
             if (this.canal !== 3) this.canal = t.html_flg ? 2 : 1;
         },
         insertar(v) {
             const el = this.$refs.cuerpo;
             const [a, b] = [el.selectionStart ?? this.cuerpo.length, el.selectionEnd ?? this.cuerpo.length];
             this.cuerpo = this.cuerpo.slice(0, a) + v + this.cuerpo.slice(b);
             this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = a + v.length; });
         },
         vista(texto) {
             return (texto || '')
                 .replaceAll('{nombre_completo}', this.muestra).replaceAll('{名前}', this.muestra)
                 .replaceAll('{apellido}', this.muestra.split(' ')[0]).replaceAll('{nombre}', this.muestra.split(' ')[1] ?? '')
                 .replaceAll('{num_socio}', '1100028').replaceAll('{tienda}', 'shop')
                 .replaceAll('{puntos}', '1,250').replaceAll('{sellos}', '7')
                 .replaceAll('{cupon}', 'Cupón de ejemplo').replaceAll('{enlace_cupon}', 'https://…/p/cupon/…')
                 .replaceAll('{encuesta}', 'https://…/p/encuesta/…');
         },
     }">

    <div class="flex min-w-0 flex-col gap-4">
        <x-ui.field label="Canal" group>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($channels as $c)
                    <label class="flex cursor-pointer items-start gap-3 rounded-ctl border p-3 transition-colors"
                           :class="canal === {{ $c->value }} ? 'border-accent bg-accent-soft' : 'border-line hover:bg-surface2'">
                        <input type="radio" name="channel" value="{{ $c->value }}" x-model.number="canal" class="sr-only">
                        <x-icon :name="$c->icon()" :size="20" class="mt-0.5" />
                        <span class="flex flex-col">
                            <span class="text-sm font-bold">{{ $c->label() }}</span>
                            <span class="text-xs text-faint">
                                @switch($c)
                                    @case(\App\Enums\MessageChannel::TextEmail) Solo texto, llega a cualquier móvil @break
                                    @case(\App\Enums\MessageChannel::HtmlEmail) Con imágenes y formato (デコメール) @break
                                    @default A la app de Mi página
                                @endswitch
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </x-ui.field>

        @if ($templates->isNotEmpty())
            <x-ui.field label="Empezar desde una plantilla">
                <x-ui.select @change="usar($event.target.value); $event.target.value = ''">
                    <option value="">Elige una plantilla…</option>
                    @foreach ($templates->groupBy(fn ($t) => $t->category->label()) as $categoria => $grupo)
                        <optgroup label="{{ $categoria }}">
                            @foreach ($grupo as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        @endif

        <x-ui.field label="Título" required>
            <x-ui.input name="subject" x-model="titulo" maxlength="255" required
                        x-bind:placeholder="canal === 3 ? 'Título de la notificación' : 'Asunto del email'" />
        </x-ui.field>

        <x-ui.field label="Contenido" required>
            <textarea name="body" x-ref="cuerpo" x-model="cuerpo" rows="12" required
                      class="w-full rounded-ctl border border-line bg-surface px-3 py-2.5 text-sm leading-relaxed text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none"
                      :class="canal === 2 && 'font-mono text-[13px]'"></textarea>
            <span class="mt-1.5 flex flex-wrap justify-between gap-2 text-xs text-faint">
                <span x-show="canal === 2">Escribe HTML. Los enlaces se cuentan solos: no hace falta acortarlos.</span>
                <span x-show="canal !== 2">Los enlaces http(s) se convierten en enlaces con seguimiento de clics.</span>
                <span><span class="tnum" x-text="cuerpo.length"></span> caracteres<span x-show="canal === 3 && cuerpo.length > 180" class="font-bold text-danger"> · la notificación se cortará en el móvil</span></span>
            </span>
        </x-ui.field>

        <div>
            <span class="mb-1.5 block text-xs font-bold text-muted">Insertar variable</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($variables as $token => $label)
                    <button type="button" @click="insertar(@js($token))" title="{{ $label }}"
                            class="rounded-full border border-line bg-surface px-2.5 py-1 font-mono text-xs hover:border-accent hover:bg-accent-soft">
                        {{ $token }}
                    </button>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-faint">
                Los campos de información adicional marcados «Usar en newsletter» se escriben como <code>{info:categoria.campo}</code>.
            </p>
        </div>
    </div>

    {{-- Vista previa en un «móvil» --}}
    <aside aria-label="Vista previa" class="2xl:sticky 2xl:top-20 2xl:self-start">
        <span class="mb-1.5 block text-xs font-bold text-muted">Vista previa con {{ $sample }}</span>
        <div class="mx-auto w-full max-w-[340px] rounded-[28px] border-8 border-ink bg-paper p-3 shadow-sm">
            <template x-if="canal === 3">
                <div class="mt-6 rounded-2xl bg-surface p-3 shadow">
                    <div class="flex items-center gap-2 text-xs text-faint"><x-icon name="bell" :size="14" /> {{ config('app.name') }} · ahora</div>
                    <div class="mt-1 text-sm font-bold" x-text="vista(titulo) || 'Título de la notificación'"></div>
                    <div class="mt-0.5 line-clamp-4 text-sm text-muted" x-text="vista(cuerpo)"></div>
                </div>
            </template>
            <template x-if="canal !== 3">
                <div class="min-h-[420px] rounded-2xl bg-surface p-4">
                    <div class="border-b border-line pb-2 text-sm font-bold" x-text="vista(titulo) || 'Asunto del email'"></div>
                    <div x-show="canal === 1" class="pt-3 text-sm leading-relaxed break-words whitespace-pre-line" x-text="vista(cuerpo)"></div>
                    {{-- HTML escrito por el administrador; solo se pinta en su propio navegador --}}
                    <div x-show="canal === 2" class="prose-sm pt-3 text-sm leading-relaxed break-words" x-html="vista(cuerpo)"></div>
                </div>
            </template>
        </div>
    </aside>
</div>

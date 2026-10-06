@php
    use App\Enums\AddressType;
    use App\Enums\CustomerType;
    use App\Enums\Industry;
    use App\Enums\MailMagazine;
    use App\Enums\Occupation;
    use App\Enums\Sex;

    $t = fn (string $k) => __("customers.nuevo.{$k}");

    $sections = [
        ['id' => 'cliente', 'label' => $t('seccion_cliente')],
        ['id' => 'personal', 'label' => $t('seccion_personal')],
        ['id' => 'promo', 'label' => $t('seccion_promo')],
        ['id' => 'familia', 'label' => $t('seccion_familia')],
        ['id' => 'mascota', 'label' => $t('seccion_mascota')],
        ['id' => 'acceso', 'label' => $t('seccion_acceso')],
    ];
@endphp

<x-layouts.modulo :title="$t('titulo')"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => $t('titulo')]]">

    <div x-data="{
             paso: 'form',
             get bloqueado() { return this.paso !== 'form' },
             ir(p) { this.paso = p; window.scrollTo({ top: 0, behavior: 'smooth' }) },

             /* Autocompletado de dirección a partir del código postal japonés */
             zip: @js(old('zip', '')),
             buscandoZip: false,
             errorZip: '',
             async buscarZip() {
                 const limpio = (this.zip || '').replace(/[^0-9]/g, '');
                 this.errorZip = '';
                 if (limpio.length !== 7) { this.errorZip = @js(__('customers.nuevo.zip_error')); return; }
                 this.buscandoZip = true;
                 try {
                     const r = await fetch(`/admin/customers/postal-code/${limpio}`);
                     if (! r.ok) throw new Error();
                     const d = await r.json();
                     this.$refs.pref.value = d.pref;
                     this.$refs.city.value = d.city;
                     this.$refs.cityKana.value = d.city_kana;
                     this.$refs.calle.focus();
                 } catch (e) {
                     this.errorZip = @js(__('customers.nuevo.zip_error'));
                 } finally {
                     this.buscandoZip = false;
                 }
             },
         }"
         class="flex flex-col gap-5">

        <x-ui.page-header :title="$t('titulo')">
            <x-slot:description>{{ $t('obligatorios') }}</x-slot:description>

        </x-ui.page-header>

        @if ($errors->any())
            <div role="alert" class="rounded-card border border-danger/30 bg-danger/10 px-5 py-4 text-sm">
                <strong class="block text-danger">{{ $t('errores') }}</strong>
                <ul class="mt-2 list-inside list-disc text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[210px_minmax(0,1fr)]">

            <nav aria-label="{{ $t('titulo') }}" class="sticky top-22 hidden flex-col gap-0.5 lg:flex">
                @foreach ($sections as $x)
                    <a href="#{{ $x['id'] }}"
                       class="flex min-h-10 items-center gap-2.5 rounded-ctl px-2.5 text-sm font-semibold text-muted transition-colors hover:bg-surface2 hover:text-ink">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface2 text-xs font-extrabold text-ink">
                            {{ $loop->iteration }}
                        </span>
                        {{ $x['label'] }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('admin.customers.store') }}" class="flex min-w-0 flex-col gap-5">
                @csrf

                {{-- Se bloquea con pointer-events y no con `disabled`: un fieldset
                     deshabilitado no envía sus campos. --}}
                <fieldset class="m-0 flex min-w-0 flex-col gap-5 border-0 p-0 transition-opacity"
                          :class="bloqueado && 'pointer-events-none opacity-60'">

                    {{-- 1 --}}
                    <x-customers.form-section id="cliente" number="1" :title="$t('seccion_cliente')">
                        <div>
                            <span class="mb-1.5 block text-xs font-bold text-muted">{{ $t('num_socio') }}</span>
                            <div class="flex h-10 items-center rounded-ctl border border-line bg-surface2 px-3 text-sm font-bold">
                                {{ $nextCode }}
                            </div>
                            <span class="mt-1.5 block text-xs text-faint">{{ $t('num_socio_hint') }}</span>
                        </div>

                        <x-ui.field :label="$t('num_gestion')">
                            <x-ui.input name="management_no" :value="old('management_no')" />
                        </x-ui.field>

                        <x-ui.field :label="$t('tienda')" required>
                            <x-ui.select name="shop_id" required>
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field :label="$t('tipo')" required group>
                            <div class="flex flex-wrap gap-2">
                                @foreach (CustomerType::cases() as $type)
                                    <label class="inline-flex h-10 items-center gap-2 rounded-ctl border border-line bg-surface px-3.5 text-sm font-semibold">
                                        <input type="radio" name="type" value="{{ $type->value }}"
                                               @checked(old('type', 1) == $type->value)
                                               class="accent-[var(--crm-accent)]">
                                        {{ $type === CustomerType::Person ? $t('tipo_persona') : $t('tipo_empresa') }}
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>

                        <x-ui.field :label="$t('apellido_kana')">
                            <x-ui.input name="last_name_kana" :value="old('last_name_kana')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('nombre_kana')">
                            <x-ui.input name="first_name_kana" :value="old('first_name_kana')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('apellido')" required>
                            <x-ui.input name="last_name" :value="old('last_name')" autocomplete="family-name" required />
                        </x-ui.field>
                        <x-ui.field :label="$t('nombre')">
                            <x-ui.input name="first_name" :value="old('first_name')" autocomplete="given-name" />
                        </x-ui.field>

                        <x-customers.subheading>{{ $t('sub_direccion') }}</x-customers.subheading>

                        <x-ui.field :label="$t('zip')" class="sm:col-span-full">
                            <span class="flex flex-wrap items-center gap-2">
                                <x-ui.input name="zip" x-model="zip" inputmode="numeric" autocomplete="postal-code"
                                            placeholder="5300001" class="w-44!"
                                            @keydown.enter.prevent="buscarZip()" />
                                <x-ui.btn class="h-10 shrink-0" @click="buscarZip()" ::disabled="buscandoZip">
                                    <span x-show="! buscandoZip">{{ $t('zip_buscar') }}</span>
                                    <span x-show="buscandoZip" x-cloak>{{ $t('zip_buscando') }}</span>
                                </x-ui.btn>
                                <span x-show="errorZip" x-cloak x-text="errorZip" class="text-xs font-bold text-danger"></span>
                            </span>
                        </x-ui.field>

                        <x-ui.field :label="$t('pref')">
                            <x-ui.input name="pref" x-ref="pref" :value="old('pref')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('ciudad_kana')">
                            <x-ui.input name="city_kana" x-ref="cityKana" :value="old('city_kana')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('ciudad')">
                            <x-ui.input name="city" x-ref="city" :value="old('city')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('calle')">
                            <x-ui.input name="street_address" x-ref="calle" :value="old('street_address')"
                                        autocomplete="street-address" />
                        </x-ui.field>
                        <x-ui.field :label="$t('edificio_kana')">
                            <x-ui.input name="building_kana" :value="old('building_kana')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('edificio')">
                            <x-ui.input name="building" :value="old('building')" />
                        </x-ui.field>

                        <x-customers.subheading>{{ $t('sub_contacto') }}</x-customers.subheading>

                        <x-ui.field :label="$t('tel')">
                            <x-ui.input type="tel" name="tel1" :value="old('tel1')" autocomplete="tel" />
                        </x-ui.field>
                        <x-ui.field :label="$t('fax')">
                            <x-ui.input type="tel" name="fax" :value="old('fax')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('mail1')">
                            <x-ui.input type="email" name="mail1" :value="old('mail1')" autocomplete="email" />
                        </x-ui.field>
                        <x-ui.field :label="$t('mail2')">
                            <x-ui.input type="email" name="mail2" :value="old('mail2')" />
                        </x-ui.field>
                        <div>
                            <span class="mb-1.5 block text-xs font-bold text-muted">{{ $t('referido') }}</span>
                            <x-ui.btn icon="search">{{ $t('referido_buscar') }}</x-ui.btn>
                        </div>
                    </x-customers.form-section>

                    {{-- 2 --}}
                    <x-customers.form-section id="personal" number="2" :title="$t('seccion_personal')">
                        <x-ui.field :label="$t('nacimiento')">
                            <x-ui.input type="date" name="birth_date" :value="old('birth_date')" />
                        </x-ui.field>

                        <x-ui.field :label="$t('sexo')" group>
                            <div class="flex flex-wrap gap-2">
                                @foreach (Sex::cases() as $sexo)
                                    <label class="inline-flex h-10 items-center gap-2 rounded-ctl border border-line bg-surface px-3.5 text-sm font-semibold">
                                        <input type="radio" name="sex" value="{{ $sexo->value }}"
                                               @checked(old('sex') == $sexo->value)
                                               class="accent-[var(--crm-accent)]">
                                        {{ app()->getLocale() === 'ja'
                                            ? ($sexo === Sex::Male ? '男性' : '女性')
                                            : $sexo->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>

                        <x-ui.field :label="$t('sangre')">
                            <x-ui.select name="blood_type">
                                <option value="">—</option>
                                @foreach (['A', 'B', 'O', 'AB'] as $type)
                                    <option @selected(old('blood_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field :label="$t('movil')">
                            <x-ui.input type="tel" name="tel2" :value="old('tel2')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('mail_personal')">
                            <x-ui.input type="email" name="mail3" :value="old('mail3')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('ocupacion')">
                            <x-ui.select name="occupation">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach (Occupation::cases() as $ocupacion)
                                    <option value="{{ $ocupacion->value }}" @selected(old('occupation') == $ocupacion->value)>
                                        {{ $ocupacion->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-customers.subheading>{{ $t('sub_trabajo') }}</x-customers.subheading>

                        <x-ui.field :label="$t('trabajo_nombre_kana')">
                            <x-ui.input name="company_name_kana" :value="old('company_name_kana')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('trabajo_nombre')">
                            <x-ui.input name="company_name" :value="old('company_name')" autocomplete="organization" />
                        </x-ui.field>
                        <x-ui.field :label="$t('rubro')">
                            <x-ui.select name="company_industry">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach (Industry::cases() as $rubro)
                                    <option value="{{ $rubro->value }}" @selected(old('company_industry') == $rubro->value)>
                                        {{ $rubro->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <div class="hidden sm:block"></div>
                        <x-ui.field :label="$t('trabajo_tel')">
                            <x-ui.input type="tel" name="company_tel" :value="old('company_tel')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('trabajo_fax')">
                            <x-ui.input type="tel" name="company_fax" :value="old('company_fax')" />
                        </x-ui.field>
                    </x-customers.form-section>

                    {{-- 3 --}}
                    <x-customers.form-section id="promo" number="3" :title="$t('seccion_promo')">
                        <x-ui.field :label="$t('newsletter')" group>
                            <div class="flex flex-wrap gap-2">
                                @foreach (MailMagazine::cases() as $option)
                                    <label class="inline-flex h-10 items-center gap-2 rounded-ctl border border-line bg-surface px-3.5 text-sm font-semibold">
                                        <input type="radio" name="mail_magazine_flg" value="{{ $option->value }}"
                                               @checked(old('mail_magazine_flg', 1) == $option->value)
                                               class="accent-[var(--crm-accent)]">
                                        {{ __('customers.comun.' . match ($option) {
                                            MailMagazine::Send => 'enviar',
                                            MailMagazine::DoNotSend => 'no_enviar',
                                            MailMagazine::Undeliverable => 'no_entregable',
                                        }) }}
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>

                        <x-ui.field :label="$t('tipo_direccion')" :hint="$t('tipo_direccion_hint')">
                            <x-ui.select name="address_type">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach (AddressType::cases() as $tipoDir)
                                    <option value="{{ $tipoDir->value }}" @selected(old('address_type') == $tipoDir->value)>
                                        {{ $tipoDir->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field :label="$t('recordatorio')" group>
                            <div class="flex flex-wrap gap-2">
                                @foreach ([1 => 'enviar', 0 => 'no_enviar'] as $value => $clave)
                                    <label class="inline-flex h-10 items-center gap-2 rounded-ctl border border-line bg-surface px-3.5 text-sm font-semibold">
                                        <input type="radio" name="reservation_reminder_flg" value="{{ $value }}"
                                               @checked(old('reservation_reminder_flg', 1) == $value)
                                               class="accent-[var(--crm-accent)]">
                                        {{ __('customers.comun.' . $clave) }}
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>

                        <x-ui.field :label="$t('grupo')">
                            <x-ui.select name="customer_group_id">
                                <option value="">{{ __('customers.comun.sin_grupo') }}</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected(old('customer_group_id') == $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field :label="$t('motivo')">
                            <x-ui.select name="visit_motive_id">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach ($motives as $motive)
                                    <option value="{{ $motive->id }}" @selected(old('visit_motive_id') == $motive->id)>
                                        {{ $motive->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <div class="hidden sm:block"></div>

                        <x-ui.field :label="$t('notas')" class="sm:col-span-full">
                            <x-ui.textarea name="note" :rows="3">{{ old('note') }}</x-ui.textarea>
                        </x-ui.field>
                    </x-customers.form-section>

                    {{-- 4 --}}
                    <x-customers.form-section id="familia" number="4" :title="$t('seccion_familia')">
                        <x-ui.field :label="$t('conyuge')">
                            <x-ui.select name="spouse_flg">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                <option value="1" @selected(old('spouse_flg') === '1')>{{ __('customers.comun.si') }}</option>
                                <option value="0" @selected(old('spouse_flg') === '0')>{{ __('customers.comun.no') }}</option>
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field :label="$t('aniversario')">
                            <x-ui.input type="date" name="wedding_date" :value="old('wedding_date')" />
                        </x-ui.field>
                    </x-customers.form-section>

                    {{-- 5 --}}
                    <x-customers.form-section id="mascota" number="5" :title="$t('seccion_mascota')"
                                             :label="$t('mascota_etiqueta')" :columns="3">
                        <x-ui.field :label="$t('mascota_nombre')">
                            <x-ui.input name="custom[mascota][nombre]" :value="old('custom.mascota.nombre')" />
                        </x-ui.field>
                        <x-ui.field :label="$t('mascota_tipo')">
                            <x-ui.select name="custom[mascota][tipo]">
                                <option value="">{{ __('customers.comun.seleccionar') }}</option>
                                @foreach (['Perro', 'Gato', 'Conejo', 'Hámster', 'Otros'] as $type)
                                    <option @selected(old('custom.mascota.tipo') === $type)>{{ $type }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field :label="$t('mascota_peso')">
                            <span class="flex items-center gap-2">
                                <x-ui.input type="number" name="custom[mascota][peso]" :value="old('custom.mascota.peso')" />
                                <span class="text-sm text-muted">Kg</span>
                            </span>
                        </x-ui.field>
                    </x-customers.form-section>

                    {{-- 6 --}}
                    <x-customers.form-section id="acceso" number="6" :title="$t('seccion_acceso')"
                                             :description="$t('acceso_desc')">
                        <div>
                            <span class="mb-1.5 block text-xs font-bold text-muted">{{ $t('login_id') }}</span>
                            <div class="flex h-10 items-center rounded-ctl border border-line bg-surface2 px-3 text-sm font-bold">
                                {{ $nextCode }}
                            </div>
                            <span class="mt-1.5 block text-xs text-faint">{{ $t('login_id_hint') }}</span>
                        </div>
                        <x-ui.field :label="$t('password')">
                            <x-ui.input name="password" :value="old('password', (string) random_int(1000, 9999))" />
                        </x-ui.field>
                    </x-customers.form-section>
                </fieldset>

                <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-end gap-2 rounded-card border border-line bg-surface p-3 shadow-lg">
                    <template x-if="paso === 'form'">
                        <div class="flex gap-2">
                            <x-ui.btn :href="route('admin.customers.index')">{{ $t('cancelar') }}</x-ui.btn>
                            <x-ui.btn variant="primary" @click="ir('confirm')">{{ $t('revisar_btn') }}</x-ui.btn>
                        </div>
                    </template>

                    <template x-if="paso === 'confirm'">
                        <div class="flex gap-2">
                            <x-ui.btn @click="paso = 'form'">{{ $t('volver_editar') }}</x-ui.btn>
                            <x-ui.btn type="submit" variant="primary">{{ $t('confirmar') }}</x-ui.btn>
                        </div>
                    </template>
                </div>
            </form>
        </div>
    </div>

</x-layouts.modulo>

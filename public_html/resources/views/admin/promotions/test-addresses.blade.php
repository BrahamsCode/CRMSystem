<x-layouts.modulo module="promo" title="Emails de prueba"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Emails de prueba']]">

    <div class="flex max-w-[900px] flex-col gap-5">
        <x-ui.page-header title="Emails de prueba"
                          description="Reciben el botón «Enviar prueba» de cada envío, con los datos de un cliente de muestra." />
        <x-ui.flash />

        @foreach ($shops as $shop)
            <x-ui.section :title="$shop->name" :description="$shop->testMailAddresses->count() . ' direcciones'">
                <ul class="divide-y divide-line">
                    @forelse ($shop->testMailAddresses as $address)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-icon name="mail" class="text-muted" />
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="font-bold">{{ $address->mail }}</span>
                                @if ($address->name) <span class="text-xs text-faint">{{ $address->name }}</span> @endif
                            </span>
                            <form method="POST" action="{{ route('admin.promotions.test-addresses.destroy', $address) }}">
                                @csrf @method('DELETE')
                                <x-ui.btn type="submit" variant="danger" class="h-9" icon="trash">Quitar</x-ui.btn>
                            </form>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-faint">Sin direcciones todavía.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.promotions.test-addresses.store') }}" class="flex flex-wrap items-end gap-3 border-t border-line px-5 py-4">
                    @csrf
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    <x-ui.field label="Email" class="min-w-64 flex-1"><x-ui.input type="email" name="mail" required /></x-ui.field>
                    <x-ui.field label="Nombre (opcional)" class="w-56"><x-ui.input name="name" /></x-ui.field>
                    <x-ui.btn type="submit" icon="plus">Añadir</x-ui.btn>
                </form>
            </x-ui.section>
        @endforeach
    </div>

</x-layouts.modulo>

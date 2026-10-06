<x-layouts.modulo module="promo" title="QR de registro"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'QR de registro']]">

    <x-ui.page-header title="QR de registro" description="Imprime el código de cada tienda para que los clientes se den de alta desde el móvil y empiecen a recibir tus envíos." />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($qrs as $qr)
                <x-ui.section :title="$qr['shop']->name">
                    <div class="flex flex-col items-center gap-3 p-5">
                        <div class="rounded-ctl bg-white p-3">{!! $qr['svg'] !!}</div>
                        <a href="{{ $qr['url'] }}" target="_blank" rel="noopener" class="max-w-full truncate text-xs text-accent-fg hover:underline">{{ $qr['url'] }}</a>
                        <x-ui.btn icon="download" onclick="window.print()">Imprimir</x-ui.btn>
                    </div>
                </x-ui.section>
            @endforeach
        </div>

        <x-ui.section title="Lista de correo" :description="number_format($total) . ' clientes reciben la newsletter'">
            <ul class="divide-y divide-line text-sm">
                @foreach ($types as [$label, $n])
                    <li class="flex justify-between px-5 py-2.5"><span>{{ $label }}</span><span class="tnum font-bold">{{ number_format($n) }}</span></li>
                @endforeach
            </ul>
            <p class="border-t border-line px-5 py-3 text-xs text-faint">Por tipo de dirección del cliente. El alta desde el QR lleva a Mi página (módulo 3).</p>
        </x-ui.section>
    </div>

</x-layouts.modulo>

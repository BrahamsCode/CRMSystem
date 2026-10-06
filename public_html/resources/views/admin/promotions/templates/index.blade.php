<x-layouts.modulo module="promo" title="Plantillas"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Plantillas']]">

    <x-ui.page-header title="Plantillas" description="Textos guardados para empezar un envío o una automatización sin escribir desde cero.">
        <x-slot:actions>
            <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.templates.create')">Nueva plantilla</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.promotions.templates.index') }}"
           @class(['rounded-ctl border px-3 py-2 text-sm font-bold', 'border-accent bg-accent text-on-accent' => ! $category, 'border-line bg-surface hover:bg-surface2' => $category])>Todas</a>
        @foreach (\App\Enums\TemplateCategory::cases() as $c)
            <a href="{{ route('admin.promotions.templates.index', ['category' => $c->value]) }}"
               @class(['rounded-ctl border px-3 py-2 text-sm font-bold', 'border-accent bg-accent text-on-accent' => $category === $c, 'border-line bg-surface hover:bg-surface2' => $category !== $c])>{{ $c->label() }}</a>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($templates as $t)
            <a href="{{ route('admin.promotions.templates.edit', $t) }}"
               class="flex flex-col gap-2 rounded-card border border-line bg-surface p-5 transition-colors hover:border-accent/40">
                <span class="flex items-center justify-between gap-2">
                    <x-ui.badge>{{ $t->category->label() }}</x-ui.badge>
                    @if ($t->html_flg) <x-ui.badge tone="outline">HTML</x-ui.badge> @endif
                </span>
                <span class="font-bold">{{ $t->name }}</span>
                @if ($t->subject) <span class="text-sm text-muted">{{ $t->subject }}</span> @endif
                <span class="line-clamp-3 text-xs leading-relaxed text-faint">{{ strip_tags($t->body) }}</span>
            </a>
        @empty
            <x-ui.card class="md:col-span-2 xl:col-span-3">
                <x-ui.empty icon="template" title="No hay plantillas">Crea la primera para reutilizar textos.</x-ui.empty>
            </x-ui.card>
        @endforelse
    </div>

</x-layouts.modulo>

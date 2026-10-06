@extends('layouts.public')

@section('title', $issued->coupon->name)

@section('content')
    @php $coupon = $issued->coupon; @endphp
    <div class="overflow-hidden rounded-card border border-line bg-surface shadow-sm">
        <div class="bg-accent px-6 py-8 text-center text-on-accent">
            <div class="text-sm font-bold opacity-90">{{ $coupon->shop?->name ?? config('app.name') }}</div>
            <div class="tnum mt-2 text-5xl font-extrabold">{{ $coupon->type->format($coupon->value) }}</div>
            <div class="mt-1 text-sm">de descuento</div>
        </div>
        <div class="flex flex-col gap-3 border-t-2 border-dashed border-line p-6">
            <h1 class="text-xl font-extrabold">{{ $coupon->name }}</h1>
            @if ($coupon->description) <p class="text-sm leading-relaxed whitespace-pre-line text-muted">{{ $coupon->description }}</p> @endif

            <div @class(['rounded-ctl px-4 py-3 text-center text-sm font-bold', 'bg-accent-soft text-accent-fg' => $issued->isUsable(), 'bg-surface2 text-muted' => ! $issued->isUsable()])>
                {{ $issued->stateLabel() }}
                @if ($issued->isUsable() && $issued->expires_at) · válido hasta el {{ $issued->expires_at->format('d/m/Y') }} @endif
            </div>

            <p class="text-center text-xs text-faint">Muestra esta pantalla en caja · Código <span class="tnum font-bold text-ink">{{ strtoupper($issued->uid) }}</span></p>

            @if ($coupon->notes)
                <ul class="list-disc pl-5 text-xs text-muted">
                    @foreach ($coupon->notes as $note) <li>{{ $note }}</li> @endforeach
                </ul>
            @endif
        </div>
    </div>
    @if ($issued->customer)
        <p class="text-center text-sm text-muted">Cupón de {{ $issued->customer->greetingName() }}</p>
    @endif
@endsection

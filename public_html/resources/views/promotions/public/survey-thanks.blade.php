@extends('layouts.public')

@section('title', $survey->name)

@section('content')
    <div class="flex flex-col items-center gap-3 rounded-card border border-line bg-surface p-8 text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-accent text-on-accent"><x-icon name="check" :size="28" /></span>
        <h1 class="text-xl font-extrabold">{{ $survey->thanks_message ?: '¡Gracias por tu opinión!' }}</h1>
        @if ($issued)
            <p class="text-sm text-muted">Te regalamos un cupón por responder.</p>
            <x-ui.btn variant="primary" icon="ticket" :href="route('promotions.public.coupon', $issued)">Ver mi cupón</x-ui.btn>
        @endif
    </div>
@endsection

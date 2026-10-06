{{-- Mensaje de resultado tras guardar (session('status')) --}}
@if (session('status'))
    <div role="status" {{ $attributes->class('flex items-center gap-3 rounded-card bg-accent-soft px-5 py-3.5') }}>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
            <x-icon name="check" :size="18" />
        </span>
        <strong>{{ session('status') }}</strong>
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="rounded-card border border-danger/30 bg-danger/5 px-5 py-3.5 text-sm">
        <strong class="text-danger">Revisa los datos:</strong>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

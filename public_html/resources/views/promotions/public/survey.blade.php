@extends('layouts.public')

@section('title', $survey->name)

@section('content')
    <header>
        <h1 class="text-2xl font-extrabold">{{ $survey->name }}</h1>
        @if ($survey->description) <p class="mt-2 text-sm leading-relaxed text-muted">{{ $survey->description }}</p> @endif
        @if ($survey->coupon) <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-accent-soft px-3 py-1 text-sm font-bold text-accent-fg"><x-icon name="gift" :size="16" /> Al responder te regalamos: {{ $survey->coupon->name }}</p> @endif
    </header>

    @if (session('status') || $answered)
        <div role="status" class="rounded-card bg-accent-soft p-5 text-center font-bold">{{ session('status') ?? 'Ya respondiste esta encuesta. ¡Gracias!' }}</div>
    @elseif (! $survey->isOpen())
        <div class="rounded-card border border-line bg-surface p-5 text-center text-muted">Esta encuesta está cerrada.</div>
    @else
        <form method="POST" action="{{ route('promotions.public.survey.answer', $survey) }}" class="flex flex-col gap-4">
            @csrf
            @if ($recipient) <input type="hidden" name="r" value="{{ $recipient->uid }}"> @endif

            @foreach ($survey->questions as $i => $q)
                @php $name = 'q[' . $q->id . ']'; $old = old('q.' . $q->id); @endphp
                <fieldset class="rounded-card border border-line bg-surface p-5">
                    <legend class="sr-only">Pregunta {{ $i + 1 }}</legend>
                    <p class="mb-3 font-bold">{{ $i + 1 }}. {{ $q->name }} @if ($q->required_flg)<span class="text-danger">*</span>@endif</p>

                    @switch($q->type)
                        @case(\App\Enums\CustomFieldType::Radio)
                            <div class="flex flex-col gap-2">
                                @foreach ($q->options ?? [] as $opt)
                                    <label class="flex items-center gap-2.5 rounded-ctl border border-line px-3 py-2.5 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                                        <input type="radio" name="{{ $name }}" value="{{ $opt }}" @checked($old === $opt) class="accent-[var(--crm-accent)]"> {{ $opt }}
                                    </label>
                                @endforeach
                            </div>
                            @break
                        @case(\App\Enums\CustomFieldType::Checkbox)
                            <div class="flex flex-col gap-2">
                                @foreach ($q->options ?? [] as $opt)
                                    <label class="flex items-center gap-2.5 rounded-ctl border border-line px-3 py-2.5 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                                        <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}" @checked(in_array($opt, (array) $old, true)) class="accent-[var(--crm-accent)]"> {{ $opt }}
                                    </label>
                                @endforeach
                            </div>
                            @break
                        @case(\App\Enums\CustomFieldType::Select)
                            <x-ui.select :name="$name" :aria-label="$q->name">
                                <option value="">Elige…</option>
                                @foreach ($q->options ?? [] as $opt) <option value="{{ $opt }}" @selected($old === $opt)>{{ $opt }}</option> @endforeach
                            </x-ui.select>
                            @break
                        @case(\App\Enums\CustomFieldType::Textarea)
                            <x-ui.textarea :name="$name" :rows="4" :aria-label="$q->name">{{ $old }}</x-ui.textarea>
                            @break
                        @case(\App\Enums\CustomFieldType::Numeric)
                            <x-ui.input type="number" :name="$name" :value="$old" :aria-label="$q->name" />
                            @break
                        @case(\App\Enums\CustomFieldType::YearMonthDay)
                            <x-ui.input type="date" :name="$name" :value="$old" :aria-label="$q->name" />
                            @break
                        @default
                            <x-ui.input :name="$name" :value="$old" :aria-label="$q->name" />
                    @endswitch

                    @error('q.' . $q->id) <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                </fieldset>
            @endforeach

            <x-ui.btn type="submit" variant="primary" class="h-12 text-base">Enviar respuestas</x-ui.btn>
        </form>
    @endif
@endsection

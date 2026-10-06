@props(['rows' => 3])

<textarea rows="{{ $rows }}"
          {{ $attributes->class('w-full min-w-0 rounded-ctl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-faint focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none') }}>{{ $slot }}</textarea>

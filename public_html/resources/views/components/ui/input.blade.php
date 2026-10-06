@props(['type' => 'text'])

<input type="{{ $type }}"
       {{ $attributes->class('h-10 w-full min-w-0 rounded-ctl border border-line bg-surface px-3 text-sm text-ink placeholder:text-faint focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none') }}>

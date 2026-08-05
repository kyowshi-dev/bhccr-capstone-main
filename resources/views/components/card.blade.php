@props(['padding' => 'p-4 lg:p-5', 'class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-surface shadow-sm ' . $padding . ' ' . $class]) }}>
    {{ $slot }}
</div>

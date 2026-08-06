@props(['padding' => 'p-4 lg:p-5', 'class' => '', 'hover' => false])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-surface shadow-sm ' . ($hover ? 'transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 ' : '') . $padding . ' ' . $class]) }}>
    {{ $slot }}
</div>

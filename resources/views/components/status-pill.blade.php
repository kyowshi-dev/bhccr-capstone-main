@props(['state' => 'neutral'])

@php
    $states = [
        'neutral' => 'bg-teal-soft text-ink-muted',
        'up-to-date' => 'bg-teal-soft text-primary',
        'completed' => 'bg-teal-soft text-primary',
        'active' => 'bg-accent-blue-soft text-accent-blue',
        'danger' => 'bg-danger-soft text-danger',
        'warning' => 'bg-amber-soft text-amber',
    ];
    $classes = $states[$state] ?? $states['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $classes]) }}>
    @if ($icon ?? null)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
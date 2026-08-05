@props(['state' => 'due'])

@php
    $states = [
        'due' => ['icon' => 'fa-regular fa-calendar', 'label' => 'Due', 'classes' => 'bg-accent-blue-soft text-accent-blue border-accent-blue/30'],
        'referred' => ['icon' => 'fa-solid fa-arrow-up-right-from-square', 'label' => 'Referred', 'classes' => 'bg-amber-soft text-amber border-amber/30'],
        'overdue' => ['icon' => 'fa-solid fa-circle-exclamation', 'label' => 'Overdue', 'classes' => 'bg-danger-soft text-danger border-danger/30'],
    ];
    $s = $states[$state] ?? $states['due'];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $s['classes'] }}">
    <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
    <span>{{ $slot ?: $s['label'] }}</span>
</span>

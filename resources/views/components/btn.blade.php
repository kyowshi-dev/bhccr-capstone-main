@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'disabled' => false,
    'icon' => null,
    'message' => 'Unauthorized',
])

@php
    $variants = [
        'primary' => 'bg-primary text-white shadow-[0_2px_8px_-1px_rgba(13,74,60,0.4)] hover:bg-primary-hover hover:shadow-[0_4px_12px_-2px_rgba(13,74,60,0.45)] focus-visible:outline-primary',
        'outlined' => 'border border-border bg-surface text-primary hover:bg-teal-soft hover:border-primary/30 focus-visible:outline-primary',
        'destructive' => 'bg-danger text-white shadow-[0_2px_8px_-1px_rgba(220,38,38,0.35)] hover:bg-red-700 focus-visible:outline-danger',
        'ghost' => 'text-ink-muted hover:bg-black/5',
    ];
    $classes = $variants[$variant] ?? $variants['primary'];
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-200 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2';
    $onclick = $disabled ? "Swal.fire({title: 'Unauthorized', text: '" . addslashes($message) . "', icon: 'error'}); return false;" : null;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => trim($base . ' ' . $classes . ($disabled ? ' disabled' : ''))]) }}
       @if ($onclick) onclick="{{ $onclick }}" @endif>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => trim($base . ' ' . $classes . ($disabled ? ' disabled' : ''))]) }}
            @if ($onclick) onclick="{{ $onclick }}" @endif @if ($disabled) aria-disabled="true" @endif>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </button>
@endif

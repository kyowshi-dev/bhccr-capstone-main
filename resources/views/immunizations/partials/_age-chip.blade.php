@props(['patient'])

@php
    $parts = $patient->ageDetail ?? null;
    [$y, $m, $d] = $parts !== null ? array_pad($parts, 3, 0) : [null, null, null];
    $label = $y !== null
        ? ($y > 0 ? "{$y}y {$m}m" : ($m > 0 ? "{$m}m {$d}d" : "{$d}d"))
        : null;
@endphp

@if ($label)
    <span class="inline-flex h-7 min-w-[2.5rem] shrink-0 items-center justify-center rounded-full px-2 text-[11px] font-semibold leading-none whitespace-nowrap"
          style="background: var(--teal-soft); color: var(--primary);"
          title="{{ $y }}y {{ $m }}m {{ $d }}d">{{ $label }}</span>
@endif
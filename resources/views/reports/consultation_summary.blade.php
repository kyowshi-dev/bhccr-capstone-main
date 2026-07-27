@extends('layouts.app')

@section('content')
@php
    $maxCount = $programs->max('count') ?: 0;

    $displayedGrowth = null;
    if (! is_null($growthPercent)) {
        $rounded = (int) round($growthPercent);
        $prefix = $rounded > 0 ? '+' : ($rounded < 0 ? '' : '');
        $displayedGrowth = $prefix.$rounded.'%';
    }
@endphp

<div class="space-y-6 lg:space-y-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('reports.index') }}"
               class="text-xs lg:text-sm font-medium hover:underline"
               style="color: var(--primary);">
                All reports
            </a>
            <h1 class="mt-1 lg:mt-2 font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">
                Program Summary Report
            </h1>
            <p class="text-xs lg:text-sm mt-1" style="color: var(--ink-muted);">
                Monthly consolidation of services by health program — {{ $reportDate }}
            </p>
        </div>

        <form method="GET" action="{{ route('reports.consultation-summary') }}" class="flex flex-wrap items-end gap-2">
            <div class="flex flex-col text-[11px]">
                <label for="month" class="mb-1 font-medium" style="color: var(--ink-muted);">Month</label>
                <select id="month" name="month"
                        class="rounded-lg border px-2 py-1.5 text-xs lg:text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('M') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col text-[11px]">
                <label for="year" class="mb-1 font-medium" style="color: var(--ink-muted);">Year</label>
                <input id="year" type="number" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}"
                       class="w-16 lg:w-20 rounded-lg border px-2 py-1.5 text-xs lg:text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
            </div>
            <button type="submit"
                    class="mt-3 sm:mt-5 px-3 lg:px-4 py-1.5 lg:py-2 rounded-xl text-xs lg:text-sm font-semibold text-white transition"
                    style="background: var(--primary); box-shadow: var(--shadow-sm);">
                Apply
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6">
        <div class="lg:col-span-4">
            <div class="h-full rounded-2xl p-5 lg:p-6 flex flex-col justify-between"
                 style="background: var(--primary); box-shadow: var(--shadow-md);">
                <div>
                    <p class="text-xs font-semibold tracking-wider uppercase" style="color: rgba(255,255,255,0.72);">
                        Total monthly consultations
                    </p>
                    <p class="mt-3 font-display font-semibold text-3xl lg:text-4xl" style="color: #ffffff;">
                        {{ number_format($total) }}
                    </p>
                </div>
                <div class="mt-6 rounded-xl px-4 py-3 text-xs"
                     style="background: rgba(0,0,0,0.25); color: rgba(255,255,255,0.9);">
                    <p class="font-semibold tracking-wide uppercase text-[10px] mb-1">
                        Growth vs last month
                    </p>
                    @if (! is_null($displayedGrowth))
                        <p class="text-sm font-semibold">
                            {{ $displayedGrowth }}
                        </p>
                    @else
                        <p class="text-sm">
                            Not available (no data for previous month)
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="rounded-2xl border p-5 lg:p-6"
                 style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">
                            Program breakdown
                        </h2>
                            <p class="text-xs mt-1" style="color: var(--ink-muted);">
                                Distribution of consultations by program for this period.
                            </p>
                    </div>
                </div>

                @if ($programs->isEmpty())
                    <p class="text-sm" style="color: var(--ink-muted);">
                        No consultations for this period.
                    </p>
                @else
                    @php
                        $graphColors = [
                            // Use more opaque fills so the per-program differences are visible.
                            'rgba(13, 74, 60, 0.82)',
                            'rgba(31, 181, 146, 0.82)',
                            'rgba(26, 31, 28, 0.55)',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach ($programs as $program)
                            @php
                                $label = $program['label'];
                                $width = $maxCount > 0 ? max(6, (int) floor(($program['count'] / $maxCount) * 100)) : 0;
                                $color = $graphColors[$loop->index % count($graphColors)];
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-xs">
                                    <span style="color: var(--ink);">{{ $label }}</span>
                                    <span class="font-semibold" style="color: var(--ink-muted);">
                                        {{ number_format($program['count']) }}
                                    </span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full" style="background: rgba(0,0,0,0.05);">
                                    <div class="h-full rounded-full"
                                         style="width: {{ $width }}%; background: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border overflow-hidden"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="p-4 lg:p-5 border-b" style="border-color: var(--border); background: var(--bgw-surface);">
            <p class="font-display font-semibold text-sm lg:text-base" style="color: var(--ink);">
                Barangay Health Center Information System — Sta. Ana
            </p>
            <p class="text-xs lg:text-sm" style="color: var(--ink-muted);">
                Health Service Program Consolidation Table
            </p>
            <p class="text-xs mt-1" style="color: var(--ink-subtle);">
                Report period: {{ $reportDate }}
            </p>
        </div>

        <div class="p-4 lg:p-6 overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead>
                    <tr style="background: var(--teal-soft);">
                        <th class="px-3 lg:px-4 py-2 lg:py-3 font-semibold text-xs uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Program category
                        </th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 font-semibold text-xs uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Description
                        </th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 font-semibold text-xs uppercase tracking-wide text-right"
                            style="color: var(--ink-muted);">
                            Consultations
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programs as $program)
                        <tr class="border-b last:border-b-0" style="border-color: var(--border);">
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 font-medium" style="color: var(--ink);">
                                {{ $program['label'] }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-xs" style="color: var(--ink-muted);">
                                {{ $program['description'] }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-right font-semibold" style="color: var(--ink);">
                                {{ number_format($program['count']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-sm" style="color: var(--ink-muted);">
                                No consultations for this period.
                            </td>
                        </tr>
                    @endforelse

                    @if ($programs->isNotEmpty())
                        <tr class="border-t" style="border-color: var(--border);">
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold" style="color: var(--ink);">
                                Total consultations consolidated
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-xs" style="color: var(--ink-muted);">
                                Sum of all recorded consultation statuses for the selected month.
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-right font-semibold" style="color: var(--ink);">
                                {{ number_format($total) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@php
    $showFilters = $showFilters ?? true;
    $filterAction = $filterAction ?? route('dashboard');
    $panelTitle = $panelTitle ?? 'Results ready';
    $panelSubtitle = $panelSubtitle ?? 'Completed consultations — print handouts for patients picking up Rx or diagnosis summaries.';
@endphp

<div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">{{ $panelTitle }}</h2>
            <p class="text-sm mt-0.5" style="color: var(--ink-muted);">{{ $panelSubtitle }}</p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shrink-0" style="background: var(--teal-soft); color: var(--primary);">
            {{ $resultsReadyCount ?? 0 }} total completed
        </span>
    </div>

    @if ($showFilters)
        <form method="GET" action="{{ $filterAction }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="sm:col-span-2">
                <label for="results_query" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Search patient</label>
                <input type="text" id="results_query" name="results_query" value="{{ $resultsFilters['query'] ?? '' }}"
                       placeholder="Name or patient ID"
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
            </div>
            <div>
                <label for="results_from" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">From</label>
                <input type="date" id="results_from" name="results_from" value="{{ $resultsFilters['from'] ?? '' }}"
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
            </div>
            <div>
                <label for="results_to" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">To</label>
                <input type="date" id="results_to" name="results_to" value="{{ $resultsFilters['to'] ?? '' }}"
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">Apply filters</button>
                <a href="{{ $filterAction }}" class="px-4 py-2 rounded-xl text-sm font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--ink-muted);">Clear</a>
            </div>
        </form>
    @endif

    <ul class="space-y-2">
        @forelse ($resultsReady ?? [] as $result)
            <li class="rounded-xl border px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                style="border-color: var(--border); background: var(--bg-surface-elevated);">
                <div class="min-w-0">
                    <p class="text-sm font-semibold" style="color: var(--ink);">
                        {{ $result->last_name }}, {{ ucwords($result->first_name) }}
                        <span class="font-medium" style="color: var(--primary);">PT{{ str_pad($result->patient_id, 3, '0', STR_PAD_LEFT) }}</span>
                    </p>
                    <p class="text-xs mt-0.5" style="color: var(--ink-muted);">
                        Completed {{ \Carbon\Carbon::parse($result->updated_at)->format('M j, Y g:i A') }}
                    </p>
                    @if ($result->diagnosis_summary ?? null)
                        <p class="text-xs mt-1 line-clamp-2" style="color: var(--ink-subtle);">{{ $result->diagnosis_summary }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 gap-2">
                    @if (auth()->user()->canPrintHandout())
                        <a href="{{ route('consultations.handout', $result->id) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                           style="background: var(--primary);">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> Print Handout
                        </a>
                    @endif
                    @if (auth()->user()->hasPermission('consultations'))
                        <a href="{{ route('consultations.show', $result->id) }}"
                           class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold transition hover:bg-black/[0.03]"
                           style="border: 1px solid var(--border); color: var(--primary);">View</a>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-xl border px-4 py-8 text-center text-sm" style="border-color: var(--border); color: var(--ink-muted);">
                No completed consultations match your filters.
            </li>
        @endforelse
    </ul>
</div>

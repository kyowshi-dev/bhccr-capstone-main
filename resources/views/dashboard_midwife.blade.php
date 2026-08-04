@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div>
        <h1 class="font-display font-semibold text-3xl lg:text-4xl" style="color: var(--ink);">Maternal Dashboard</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Pre-natal &amp; post-natal maternal tracking</p>
    </div>

    <div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl border" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Pre-natal registrants</p>
                    <i class="fa-solid fa-person-pregnant text-lg" style="color: var(--primary);"></i>
                </div>
                <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $prenatalRegistrants ?? 0 }}</p>
                <p class="text-xs mt-2" style="color: var(--ink-muted);">Active pregnancies being tracked</p>
            </div>

            <div class="p-5 rounded-xl border" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Due this month</p>
                    <i class="fa-solid fa-calendar-check text-lg" style="color: var(--accent-blue);"></i>
                </div>
                <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $dueThisMonth ?? 0 }}</p>
                <p class="text-xs mt-2" style="color: var(--ink-muted);">Estimated dates of delivery this month</p>
            </div>

            <div class="p-5 rounded-xl border" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Post-natal due</p>
                    <i class="fa-solid fa-baby text-lg" style="color: var(--accent);"></i>
                </div>
                <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $postnatalDue ?? 0 }}</p>
                <p class="text-xs mt-2" style="color: var(--ink-muted);">24h / 7d / 14d / 28d visits pending</p>
            </div>

            <div class="p-5 rounded-xl border" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-soft);">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">High-risk referrals</p>
                    <i class="fa-solid fa-triangle-exclamation text-lg" style="color: var(--accent-soft);"></i>
                </div>
                <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $highRiskReferrals ?? 0 }}</p>
                <p class="text-xs mt-2" style="color: var(--ink-muted);">Pregnancies referred for higher care</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border p-6 text-center" style="border-color: var(--border); background: var(--bg-surface); box-shadow: var(--shadow-sm);">
        <div class="flex justify-center mb-2"><i class="fa-solid fa-person-pregnant text-3xl" style="color: var(--ink-subtle);"></i></div>
        <p class="text-sm font-medium" style="color: var(--ink);">Maternal tracking is coming soon</p>
        <p class="text-xs mt-1" style="color: var(--ink-muted);">Pre-natal and post-natal records will appear here once the maternal module is released.</p>
    </div>

    @if ($showResultsReady ?? false)
        @include('dashboard.partials.results-ready', [
            'panelTitle' => 'Results Ready',
            'panelSubtitle' => 'Completed & Finalized Consultations Ready for Print',
            'showFilters' => true,
            'filterAction' => route('dashboard'),
        ])
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Midwife Dashboard')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Maternal Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Pre-natal &amp; post-natal maternal tracking</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm"
             style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                  style="background: var(--teal-soft); color: var(--primary);">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M16 2v4"></path>
                    <path d="M8 2v4"></path>
                    <path d="M3 10h18"></path>
                </svg>
            </span>
            <div class="leading-tight">
                <div class="font-semibold" style="color: var(--ink);">{{ $todayLabel }}</div>
                <div class="text-xs" style="color: var(--ink-muted);">{{ $weekdayLabel }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <div class="kpi-card animate-in opacity-0 delay-2 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-person-pregnant" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Pre-natal registrants</p>
                <p class="kpi-card__value">{{ $prenatalRegistrants ?? 0 }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Active pregnancies tracked</p>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-3 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Due this month</p>
                <p class="kpi-card__value">{{ $dueThisMonth ?? 0 }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">EDDs this month</p>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-4 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <span class="kpi-card__icon" style="background: var(--accent-soft); color: var(--accent);">
                <i class="fa-solid fa-baby" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Post-natal due</p>
                <p class="kpi-card__value">{{ $postnatalDue ?? 0 }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">24h / 7d / 14d / 28d visits</p>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-soft);">
            <span class="kpi-card__icon" style="background: var(--accent-soft); color: var(--amber);">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">High-risk referrals</p>
                <p class="kpi-card__value">{{ $highRiskReferrals ?? 0 }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Referred for higher care</p>
            </div>
        </div>
    </div>

    <div class="animate-in opacity-0 delay-6 rounded-xl border p-6 text-center"
         style="border-color: var(--border); background: var(--bg-surface); box-shadow: var(--shadow-sm);">
        <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center"
             style="background: var(--teal-soft); color: var(--primary);">
            <i class="fa-solid fa-person-pregnant text-xl" aria-hidden="true"></i>
        </div>
        <p class="mt-3 text-sm font-medium" style="color: var(--ink);">Maternal tracking is coming soon</p>
        <p class="text-xs mt-1 mx-auto max-w-md" style="color: var(--ink-muted);">
            Pre-natal and post-natal records will appear here once the maternal module is released. You can still update patient records in the meantime.
        </p>
        @if (auth()->user()?->hasPermission('patients'))
            <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90 mt-4" style="background: var(--primary);">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
                View patient records
            </a>
        @endif
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
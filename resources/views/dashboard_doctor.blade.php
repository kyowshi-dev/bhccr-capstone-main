@extends('layouts.app')

@section('title', 'Doctor Dashboard')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Doctor Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Review queued patients and complete consultations efficiently.</p>
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
                <i class="fa-solid fa-stethoscope" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Consultations today</p>
                <p class="kpi-card__value">{{ $consultationsToday }}</p>
                <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View visits</a>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-3 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: {{ $pendingDoctorCount > 0 ? 'var(--accent-blue-soft)' : 'var(--bg-surface)' }}; border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: {{ $pendingDoctorCount > 0 ? 'var(--accent-blue)' : 'var(--teal-soft)' }}; color: {{ $pendingDoctorCount > 0 ? '#fff' : 'var(--accent-blue)' }};">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate" style="color: {{ $pendingDoctorCount > 0 ? 'var(--accent-blue)' : 'var(--ink-muted)' }};">Doctor queue</p>
                <p class="kpi-card__value">{{ $pendingDoctorCount }}</p>
                @if ($pendingDoctorCount > 0)
                    <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--accent-blue);">Open queue</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">All caught up</p>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-4 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Completed today</p>
                <p class="kpi-card__value">{{ $completedConsultationsToday }}</p>
                <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View records</a>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--amber);">
            <span class="kpi-card__icon" style="background: var(--amber-soft); color: var(--amber);">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Follow-ups today</p>
                <p class="kpi-card__value">{{ $followUpConsultationsToday }}</p>
                @if ($followUpConsultationsToday > 0)
                    <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--amber);">Review visits</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">None scheduled</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 animate-in opacity-0 delay-5 rounded-xl border p-4 lg:p-5"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Doctor queue</h2>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">Patients ready for clinical review or already in progress.</p>
                </div>
                <a href="{{ route('consultations.index') }}" class="text-xs font-semibold hover:underline" style="color: var(--primary);">
                    View all
                </a>
            </div>

            <div class="mt-4 divide-y divide-[var(--border)]">
                @forelse ($doctorQueue as $item)
                    @php
                        $isInProgress = str_contains(strtolower((string) $item['status']), 'progress');
                    @endphp
                    <a href="{{ route('consultations.show', $item['id']) }}"
                       class="block py-3 transition-colors hover:bg-black/[0.02] rounded-lg px-2 -mx-2">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold" style="color: var(--ink);">{{ ucwords($item['patient_name']) }}</p>
                                <p class="text-xs mt-1 capitalize flex items-center gap-1.5" style="color: var(--ink-muted);">
                                    <span class="inline-flex h-1.5 w-1.5 rounded-full shrink-0"
                                          style="background: {{ $isInProgress ? 'var(--accent-blue)' : 'var(--primary)' }};"></span>
                                    <span class="truncate">{{ $item['status'] }}@if ($item['complaint_text'] ?? null) · {{ Str::limit($item['complaint_text'], 60) }}@endif</span>
                                </p>
                            </div>
                            <span class="text-xs shrink-0" style="color: var(--ink-subtle);">{{ $item['time'] }}</span>
                        </div>
                    </a>
                @empty
                    <div class="py-10 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                             style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-list-check text-lg" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold" style="color: var(--ink);">No patients in the doctor queue</p>
                        <p class="text-xs mt-1" style="color: var(--ink-muted);">Cases appear here after nurse intake validation.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-4 space-y-4">
            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--primary); border-color: rgba(255,255,255,0.14); box-shadow: var(--shadow-md);">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-white">
                        <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i>
                    </span>
                    <h3 class="font-display font-semibold text-lg" style="color: #fff;">Clinical reminder</h3>
                </div>
                <p class="text-sm mt-2" style="color: rgba(255,255,255,0.88);">
                    Prioritize high-risk symptoms and ensure complete diagnosis notes before closing each consultation.
                </p>
            </div>

            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Quick actions</h3>
                <div class="mt-3 space-y-2">
                    <a href="{{ route('patients.index', ['queue' => 1]) }}"
                       class="w-full inline-flex items-center justify-center px-3 py-2.5 rounded-lg text-xs font-semibold text-white transition-[transform,box-shadow] duration-200 hover:shadow-sm hover:scale-[1.01]"
                       style="background: var(--primary); box-shadow: var(--shadow-sm);">
                        <i class="fa-solid fa-folder-open mr-2" aria-hidden="true"></i>
                        Open patient records
                    </a>
                    <a href="{{ route('referrals.index') }}"
                       class="w-full inline-flex items-center justify-center px-3 py-2.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                       style="border-color: var(--border); color: var(--primary);">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-2" aria-hidden="true"></i>
                        View referrals
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if ($showResultsReady ?? false)
        @include('dashboard.partials.results-ready', [
            'panelTitle' => 'Recent completed - print handouts',
            'panelSubtitle' => 'Today’s finalized consultations. Print Rx and diagnosis summaries for patient pickup.',
            'showFilters' => true,
            'filterAction' => route('dashboard'),
        ])
    @endif
</div>
@endsection

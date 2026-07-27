@extends('layouts.app')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-4 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">
            Welcome Back, <span style="font-style: italic; color: var(--primary);">{{ auth()->user()?->name ?? 'Admin!' }}</span>            
            </h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
            Ania ang kinatibuk-ang impormasyon bahin sa imong health center.
            </p>
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

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 lg:gap-3">
        <div class="kpi-card animate-in opacity-0 delay-2 flex items-center gap-2.5 p-2.5 lg:p-3 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fas fa-users text-lg"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider truncate" style="color: var(--ink-muted);">Total Patients</p>
                @if ($totalPatients === 0)
                    <p class="kpi-card__value">0</p>
                    <a href="{{ route('patients.create') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">Register first patient</a>
                @else
                    <p class="kpi-card__value">{{ $totalPatients }}</p>
                    <a href="{{ route('patients.create') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">Register patient</a>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-3 flex items-center gap-2.5 p-2.5 lg:p-3 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider truncate" style="color: var(--ink-muted);">Pending check-ups</p>
                @if ($pendingAppointments === 0)
                    <p class="kpi-card__value">0</p>
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">All caught up</p>
                @else
                    <p class="kpi-card__value">{{ $pendingAppointments }}</p>
                    <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">Manage queue</a>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-4 flex items-center gap-2.5 p-2.5 lg:p-3 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: {{ $overdueImmunizations > 0 ? '#fef2f2' : 'var(--bg-surface)' }}; border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid {{ $overdueImmunizations > 0 ? '#ef4444' : 'var(--primary)' }};">
            <span class="kpi-card__icon" style="background: {{ $overdueImmunizations > 0 ? '#fee2e2' : 'var(--teal-soft)' }}; color: {{ $overdueImmunizations > 0 ? '#b91c1c' : 'var(--primary)' }};">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider truncate" style="color: {{ $overdueImmunizations > 0 ? '#991b1b' : 'var(--ink-muted)' }};">Immunization Alerts</p>
                @if ($overdueImmunizations === 0)
                    <p class="kpi-card__value">0</p>
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">On track</p>
                @else
                    <p class="kpi-card__value" style="color: #b91c1c;">{{ $overdueImmunizations }}</p>
                    <a href="{{ route('immunizations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: #b91c1c;">View chart</a>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-2.5 p-2.5 lg:p-3 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid #f59e0b;">
            <span class="kpi-card__icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider truncate" style="color: var(--ink-muted);">Follow-up today</p>
                @if ($followUpConsultationsToday === 0)
                    <p class="kpi-card__value">0</p>
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">None scheduled</p>
                @else
                    <p class="kpi-card__value">{{ $followUpConsultationsToday }}</p>
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Visits today</p>
                @endif
            </div>
        </div>

                <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-2.5 p-2.5 lg:p-3 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider truncate" style="color: var(--ink-muted);">Health workers</p>
                <p class="kpi-card__value">{{ $doctorsOnDuty }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Staff on record</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 lg:gap-3">
        <div class="lg:col-span-2 dashboard-chart animate-in opacity-0 delay-6 rounded-xl border p-3 lg:p-4"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="font-display font-semibold text-base lg:text-lg" style="color: var(--ink);">Patient Volume</h2>
                    <p class="text-[11px] mt-0.5 truncate" style="color: var(--ink-muted);">Visits over the last 7 days</p>
                </div>
                <span class="hidden sm:inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                      style="background: var(--teal-soft); color: var(--primary);">
                    Weekly trend
                </span>
            </div>

            <div class="dashboard-chart__plot mt-2">
                <livewire:livewire-line-chart
                    key="{{ $patientVolumeChartModel->reactiveKey() }}"
                    :line-chart-model="$patientVolumeChartModel"
                />
            </div>
        </div>

        <div class="dashboard-chart animate-in opacity-0 delay-7 rounded-xl border p-3 lg:p-4"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="font-display font-semibold text-base lg:text-lg" style="color: var(--ink);">Top Illnesses</h2>
                    <p class="text-[11px] mt-0.5 truncate" style="color: var(--ink-muted);">Last 30 days</p>
                </div>
                <span class="hidden sm:inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                      style="background: var(--teal-soft); color: var(--primary);">
                    Diagnoses
                </span>
            </div>

            <div class="dashboard-chart__plot mt-2">
                <livewire:livewire-pie-chart
                    key="{{ $presentingIllnessesChartModel->reactiveKey() }}"
                    :pie-chart-model="$presentingIllnessesChartModel"
                />
            </div>

            <div class="mt-2 space-y-1.5 max-h-36 overflow-y-auto">
                @forelse($topPresentingIllnesses as $illness)
                    <div class="flex items-center justify-between gap-2 rounded-lg border px-2 py-1.5"
                         style="border-color: var(--border); background: var(--bg-surface);">
                        <div class="min-w-0">
                            <p class="font-semibold text-xs truncate" style="color: var(--ink);">{{ $illness->name }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center justify-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                              style="background: var(--teal-soft); color: var(--primary);">{{ $illness->total }}</span>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed p-2 text-center"
                         style="border-color: var(--border); color: var(--ink-muted);">
                        <p class="font-semibold text-xs" style="color: var(--ink);">No diagnosis data yet</p>
                        <p class="text-[10px] mt-0.5">Populates after consultations.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-5">
        <div class="lg:col-span-8 space-y-3 lg:space-y-5">
            @if ($showResultsReady ?? false)
                @include('dashboard.partials.results-ready', [
                    'panelTitle' => 'Ready-to-Print Results',
                    'panelSubtitle' => 'Finalized consultations by RHU doctors that are ready for printing and distribution to patients',
                    'showFilters' => true,
                    'filterAction' => route('dashboard'),
                ])
            @endif

            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h2 class="font-display font-semibold text-lg lg:text-xl mb-4" style="color: var(--ink);">Recent activity</h2>
                <ul class="divide-y divide-[var(--border)] space-y-0">
                    @forelse($recentActivity as $activity)
                        <li class="py-3 text-sm transition-colors hover:bg-black/[0.02]" style="color: var(--ink-muted);">
                            {{ $activity }}
                        </li>
                    @empty
                        <li class="py-8 px-4 text-center space-y-4">
                            <div class="space-y-2">
                                <div class="text-sm font-semibold" style="color: var(--ink);">No recent activity</div>
                                <div class="text-xs" style="color: var(--ink-subtle);">Activity from patient registrations, consultations, and updates will appear here</div>
                            </div>
                            <div class="pt-2">
                                <a href="{{ route('patients.create') }}"
                                   class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-bold transition-[transform,box-shadow,background] duration-200 hover:shadow-sm hover:scale-[1.01] active:scale-[0.98]"
                                   style="background: var(--primary); color: #fff; box-shadow: var(--shadow-sm);">
                                    Get started
                                </a>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-3 lg:space-y-5">
            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">On-duty staff</h3>
                    <span class="text-xs" style="color: var(--ink-subtle);">Today</span>
                </div>

                <div class="mt-3 max-h-60 overflow-y-auto space-y-3">
                    @forelse ($onDutyStaff as $staff)
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0"
                                 style="background: var(--teal-soft); color: var(--primary);">
                                {{ $staff['initials'] }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold truncate" style="color: var(--ink);">{{ $staff['name'] }}</div>
                                <div class="text-xs truncate" style="color: var(--ink-muted);">{{ $staff['role'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center space-y-3">
                            <div style="color: var(--ink-muted);">
                                <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292m0-5.292H8.646A4 4 0 0012 4.354zm0 0h3.354A4 4 0 0012 4.354m0 5.292L15.354 7.354M12 9.646l-3.354 2.292m0 0A4 4 0 004.354 12m0 0h5.292m-5.292 0a4 4 0 100 5.292m5.292 0H8.646m3.354-2.292l3.354 2.292m0 0A4 4 0 0019.646 12m0 0v5.292m0-5.292h-5.292"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" style="color: var(--ink);">No staff data</p>
                                <p class="text-xs mt-1" style="color: var(--ink-muted);">Add health workers to see them here</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

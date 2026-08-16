@extends('layouts.app')

@section('title', 'BHW Dashboard')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">BHW Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Enrol patients, start consultations, and keep the queue moving.</p>
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

    <div class="animate-in opacity-0 delay-2 rounded-xl border p-4" x-data="patientSearch()"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);" :style="loading && 'color: var(--primary)'">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input type="text" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search patients by name..."
                       class="w-full max-w-3xl pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="off">
            </div>
            <a href="{{ url('/patients/create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 shrink-0" style="background: var(--primary);">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                Enrol New Patient
            </a>
        </div>
        <div x-show="results.length > 0" x-transition class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <button type="button" @click="window.openConsultationCreateModal(patient.id)" class="block w-full text-left px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">
                                <span x-text="patient.subtext"></span>
                                <span class="font-semibold" style="color: var(--primary);"> - Create consultation</span>
                            </div>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-transition class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <div class="flex justify-center mb-2"><i class="fa-solid fa-user-plus text-3xl" style="color: var(--ink-subtle);" aria-hidden="true"></i></div>
            <p class="text-sm font-medium" style="color: var(--ink);">No patient found</p>
            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Try searching with a different name or ID</p>
            <a href="{{ url('/patients/create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus" aria-hidden="true"></i> Enrol a new patient</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <div class="kpi-card animate-in opacity-0 delay-3 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Total patients</p>
                <p class="kpi-card__value">{{ $totalPatients ?? 0 }}</p>
                @if (auth()->user()?->hasPermission('patients'))
                    <a href="{{ route('patients.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View registry</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Registered records</p>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-4 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-stethoscope" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Consultations today</p>
                <p class="kpi-card__value">{{ $consultationsToday ?? 0 }}</p>
                <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View visits</a>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: {{ ($pendingConsultations ?? 0) > 0 ? 'var(--accent-blue-soft)' : 'var(--bg-surface)' }}; border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: {{ ($pendingConsultations ?? 0) > 0 ? 'var(--accent-blue)' : 'var(--teal-soft)' }}; color: {{ ($pendingConsultations ?? 0) > 0 ? '#fff' : 'var(--accent-blue)' }};">
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate" style="color: {{ ($pendingConsultations ?? 0) > 0 ? 'var(--accent-blue)' : 'var(--ink-muted)' }};">Pending Queue</p>
                <p class="kpi-card__value">{{ $pendingConsultations ?? 0 }}</p>
                @if (($pendingConsultations ?? 0) > 0)
                    <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--accent-blue);">Open queue</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">Queue is clear</p>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-5 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--amber);">
            <span class="kpi-card__icon" style="background: var(--amber-soft); color: var(--amber);">
                <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Referrals today</p>
                <p class="kpi-card__value">{{ $referralsToday ?? 0 }}</p>
                @if (auth()->user()?->hasPermission('consultations'))
                    <a href="{{ route('referrals.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--amber);">View referrals</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">All referred patients</p>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Queue</h2>
                <p class="text-sm" style="color: var(--ink-muted);">Patients In-Waiting for Consultation Result</p>
            </div>
            <a href="{{ route('consultations.index') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--primary);">
                <i class="fa-solid fa-list" aria-hidden="true"></i>
                View all consultations
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--ink);">Patients waiting</h3>
                <ul class="space-y-2">
                    @forelse($pendingQueue ?? [] as $queue)
                        <li class="rounded-xl border px-4 py-3 flex items-center justify-between gap-3 transition hover:bg-black/[0.02]"
                            style="border-color: var(--border); background: var(--bg-surface-elevated);">
                            <div class="min-w-0">
                                <div class="font-medium text-sm truncate" style="color: var(--ink);">{{ ucwords($queue->name ?? $queue['name'] ?? 'Unknown patient') }}</div>
                                <div class="text-xs mt-1 truncate" style="color: var(--ink-muted);">{{ $queue->identifier ?? $queue['identifier'] ?? 'No ID available' }}</div>
                            </div>
                            @if (auth()->user()?->hasPermission('patients') && ($queue->patient_id ?? null))
                                <a href="{{ route('patients.show', $queue->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border shrink-0 transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--primary);">
                                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                                    View patient
                                </a>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-xl border px-4 py-10 text-center" style="border-color: var(--border); background: var(--bg-surface-elevated);">
                            <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                                 style="background: var(--teal-soft); color: var(--primary);">
                                <i class="fa-solid fa-user-clock text-lg" aria-hidden="true"></i>
                            </div>
                            <p class="mt-3 text-sm font-semibold" style="color: var(--ink);">No patients waiting</p>
                            <p class="text-xs mt-1" style="color: var(--ink-muted);">Patients in the queue will show up here.</p>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="space-y-4">
                <div class="animate-in opacity-0 delay-6 rounded-xl border p-4" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold" style="color: var(--ink);">Recently Enrolled Patients</p>
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Last 3</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($recentPatients ?? [] as $recent)
                            <div class="rounded-xl p-4 border" style="background: var(--bg-surface-elevated); border-color: var(--border);">
                                <div class="flex items-center justify-between gap-3 min-w-0">
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm truncate" style="color: var(--ink);" title="{{ ucwords($recent->name) }}">{{ ucwords($recent->name) }}</p>
                                        <p class="text-xs mt-1 truncate" style="color: var(--ink-muted);">{{ $recent->identifier }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 shrink-0">
                                        <a href="{{ route('consultations.create', $recent->id) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90 whitespace-nowrap" style="background: var(--primary);">
                                            <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                            Start Queue
                                        </a>
                                        <a href="{{ route('patients.show', $recent->id) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02] whitespace-nowrap" style="border-color: var(--primary); color: var(--primary);">
                                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                                            View profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl p-4 border text-center text-sm" style="background: var(--bg-surface-elevated); border-color: var(--border); color: var(--ink-muted);">
                                No recently registered patients available.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="animate-in opacity-0 delay-7 rounded-xl border p-4" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold" style="color: var(--ink);">Queue summary</p>
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Updated</span>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-xl p-4" style="background: var(--bg-surface-elevated); border: 1px solid var(--border);">
                            <p class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Total queued</p>
                            <p class="mt-2 font-display font-semibold text-2xl" style="color: var(--ink);">{{ $pendingConsultations ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl p-4" style="background: var(--bg-surface-elevated); border: 1px solid var(--border);">
                            <p class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Latest queue refresh</p>
                            <p class="mt-2 text-sm" style="color: var(--ink);">{{ $queueUpdatedAt ?? 'Not available' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

<script>
    function patientSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await safeFetch(`{{ route('search.patients') }}?query=${this.query}`);
                    this.results = response.ok ? await response.json() : [];
                    this.results = await response.json();
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }
</script>
@endsection

@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-display font-semibold text-3xl lg:text-4xl" style="color: var(--ink);">Dashboard</h1>
        </div>
    </div>

    <div class="rounded-xl p-4" x-data="patientSearch()">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);" :style="loading && 'color: var(--primary)'">
                </span>
                <input type="text" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search patients by name..."
                       class="w-full max-w-3xl pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="off">
            </div>
            <a href="{{ url('/patients/create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary text-white text-sm font-semibold transition hover:bg-primary-hover">
                <i class="fa-solid fa-user-plus"></i>
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
            <div class="flex justify-center mb-2"><i class="fa-solid fa-user-plus text-3xl" style="color: var(--ink-subtle);"></i></div>
            <p class="text-sm font-medium" style="color: var(--ink);">No patient found</p>
            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Try searching with a different name or ID</p>
            <a href="{{ url('/patients/create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Enrol a new patient</a>
        </div>
    </div>

    <div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl border"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Total patients</p>
                <i class="fa-solid fa-users text-lg" style="color: var(--primary);"></i>
            </div>
            <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $totalPatients ?? 0 }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Registered records in the barangay</p>
            </div>

            <div class="p-5 rounded-xl border"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Consultations today</p>
                <i class="fa-solid fa-stethoscope text-lg" style="color: var(--primary);"></i>
            </div>
            <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $consultationsToday ?? 0 }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Number of Patients Visited Today</p>
            </div>

            <div class="p-5 rounded-xl border"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Pending Queue</p>
                <i class="fa-solid fa-hourglass-end text-lg" style="color: var(--primary);"></i>
            </div>
            <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $pendingConsultations ?? 0 }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Patients Waiting for Triage Result</p>
            </div>

            <div class="p-5 rounded-xl border"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Referrals today</p>
                <i class="fa-solid fa-arrow-right-arrow-left text-lg" style="color: var(--primary);"></i>
            </div>
            <p class="font-display font-bold text-3xl" style="color: var(--ink);">{{ $referralsToday ?? 0 }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">All Referred Patients</p>
            </div>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Queue</h2>
                <p class="text-sm" style="color: var(--ink-muted);">Patients In-Waiting for Consultation Result</p>
            </div>
            <a href="{{ route('consultations.index') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-primary border border-primary transition hover:bg-primary/5">
                <i class="fa-solid fa-list"></i>
                View all consultations
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border bg-surface p-4" style="border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--ink);">Patients waiting</h3>
                <ul class="space-y-2">
                    @forelse($pendingQueue ?? [] as $queue)
                        <li class="rounded-xl border px-4 py-3" style="border-color: var(--border); background: var(--bg-surface-elevated);">
                            <div class="font-medium text-sm" style="color: var(--ink);">{{ ucwords($queue->name ?? $queue['name'] ?? 'Unknown patient') }}</div>
                            <div class="text-xs mt-1" style="color: var(--ink-muted);">{{ $queue->identifier ?? $queue['identifier'] ?? 'No ID available' }}</div>
                        </li>
                    @empty
                        <li class="rounded-xl border px-4 py-6 text-center" style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                            No patients waiting as of now...
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="space-y-4">
            

            <div class="rounded-xl border bg-surface p-4" style="border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold" style="color: var(--ink);">Recently Enrolled Patients</p>
                    <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Last 3</span>
                </div>
                <div class="space-y-3">
                    @forelse($recentPatients ?? [] as $recent)
                        <div class="rounded-xl bg-surface-elevated p-4 border" style="border-color: var(--border);">
                            <div class="flex items-center justify-between gap-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="font-medium text-sm truncate" style="color: var(--ink);" title="{{ ucwords($recent->name) }}">{{ ucwords($recent->name) }}</p>
                                    <p class="text-xs mt-1 truncate" style="color: var(--ink-muted);">{{ $recent->identifier }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2 shrink-0">
                                    <a href="{{ route('consultations.create', $recent->id) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary text-white text-xs font-semibold transition hover:bg-primary-hover whitespace-nowrap">
                                        <i class="fa-solid fa-clock"></i>
                                        Start Queue
                                    </a>
                                    <a href="{{ route('patients.show', $recent->id) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-primary text-primary text-xs font-semibold transition hover:bg-primary/10 whitespace-nowrap">
                                        <i class="fa-solid fa-user"></i>
                                        View profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-surface-elevated p-4 border text-center text-sm" style="border-color: var(--border); color: var(--ink-muted);">
                            No recently registered patients available.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border bg-surface p-4" style="border-color: var(--border); box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold" style="color: var(--ink);">Queue summary</p>
                    <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Updated</span>
                </div>
                <div class="space-y-3">
                    <div class="rounded-xl bg-surface-elevated p-4" style="border: 1px solid var(--border);">
                        <p class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Total queued</p>
                        <p class="mt-2 font-display font-semibold text-2xl" style="color: var(--ink);">{{ $pendingConsultations ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-surface-elevated p-4" style="border: 1px solid var(--border);">
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
                    const response = await fetch(`{{ route('search.patients') }}?query=${this.query}`);
                    this.results = await response.json();
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }
</script>
@endsection
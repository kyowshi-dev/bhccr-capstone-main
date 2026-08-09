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

    <div class="animate-in opacity-0 delay-6 space-y-4" x-data="maternalToday()" x-init="init()">
        <div class="relative">
            <label for="maternal-search" class="sr-only">Search patient</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input id="maternal-search" type="search" x-model="searchQuery" @input.debounce.300ms="searchPatients()"
                       placeholder="Search patient by name…"
                       autocomplete="off"
                       class="w-full rounded-xl border pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); background: var(--bg-surface); --tw-ring-color: var(--accent-blue);">
            </div>
            <div x-show="results.length" x-cloak x-transition:enter class="absolute z-20 left-0 right-0 mt-1 rounded-xl border shadow-lg overflow-hidden"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-md);">
                <template x-for="(p, i) in results" :key="p.id">
                    <button type="button" @click="openQuick(p)"
                            :class="{ 'border-b': i < results.length - 1 }"
                            class="w-full text-left px-4 py-3 text-sm transition hover:bg-black/[0.03] focus:outline-none focus:ring-2 focus:ring-inset flex items-center justify-between gap-2"
                            style="border-color: var(--border); --tw-ring-color: var(--accent-blue);">
                        <div class="min-w-0">
                            <div class="font-semibold truncate" style="color: var(--ink);" x-text="p.text"></div>
                            <div class="text-xs truncate" style="color: var(--ink-muted);" x-text="p.subtext"></div>
                        </div>
                        <span x-show="p.has_active_pregnancy" class="inline-flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold"
                              style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-baby-carriage text-[10px]" aria-hidden="true"></i> Active
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div class="flex gap-2 border-b overflow-x-auto" style="border-color: var(--border);">
            <button @click="activeTab = 'action'"
                    :class="activeTab === 'action' ? 'border-b-2 font-semibold' : 'font-medium'"
                    class="px-3 py-2 text-xs sm:text-sm whitespace-nowrap transition"
                    style="color: var(--ink); border-color: var(--primary);">
                <i class="fa-solid fa-list-check mr-1.5" aria-hidden="true"></i> Action queue
            </button>
            <button @click="activeTab = 'watchlist'"
                    :class="activeTab === 'watchlist' ? 'border-b-2 font-semibold' : 'font-medium'"
                    class="px-3 py-2 text-xs sm:text-sm whitespace-nowrap transition"
                    style="color: var(--ink); border-color: var(--primary);">
                <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i> Watchlist
                @if ($watchlist->isNotEmpty())
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-bold text-white" style="background: var(--danger);">{{ $watchlist->count() }}</span>
                @endif
            </button>
        </div>

        <div x-show="activeTab === 'action'" class="space-y-2">
            @php
                $hasQueue = $actionQueue['overdue']->isNotEmpty() || $actionQueue['due_soon']->isNotEmpty() || $actionQueue['due_this_month']->isNotEmpty() || $actionQueue['postnatal_slots']->isNotEmpty();
            @endphp

            @unless ($hasQueue)
                <div class="rounded-xl border border-dashed p-6 sm:p-8 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                    <i class="fa-solid fa-circle-check text-2xl mb-2" style="color: var(--teal-soft);" aria-hidden="true"></i>
                    <p class="font-semibold text-sm" style="color: var(--ink);">All caught up</p>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">No overdue or upcoming maternal visits.</p>
                </div>
            @endunless

            @if ($actionQueue['overdue']->isNotEmpty())
                <div class="rounded-xl border" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="px-4 py-2.5 border-b text-xs font-semibold uppercase tracking-wide flex items-center gap-2" style="border-color: var(--border); background: var(--danger-soft); color: var(--danger);">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                    </div>
                    @foreach ($actionQueue['overdue'] as $p)
                        @include('dashboard.partials.maternal-queue-row', [
                            'patientId' => $p->patient_id,
                            'identifier' => \App\Helpers\PatientCode::format((int) $p->patient_id),
                            'patientName' => fullName($p->patient->last_name, $p->patient->first_name, $p->patient->middle_name, $p->patient->suffix),
                            'detail' => 'EDC ' . optional($p->edc)->format('M d, Y') . ' · ' . ($p->aog_weeks !== null ? $p->aog_weeks . ' wks' : optional($p->edc ? \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($p->edc)->subDays(280)) : null) . ' wks'),
                            'badge' => 'overdue',
                            'badgeLabel' => 'Overdue',
                            'action' => 'log_prenatal_visit',
                            'hasActive' => true,
                        ])
                    @endforeach
                </div>
            @endif

            @if ($actionQueue['due_soon']->isNotEmpty())
                <div class="rounded-xl border" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="px-4 py-2.5 border-b text-xs font-semibold uppercase tracking-wide flex items-center gap-2" style="border-color: var(--border); background: var(--accent-blue-soft); color: var(--accent-blue);">
                        <i class="fa-solid fa-calendar-day" aria-hidden="true"></i> Due this week ({{ now()->addDays(7)->format('M d') }})
                    </div>
                    @foreach ($actionQueue['due_soon'] as $p)
                        @include('dashboard.partials.maternal-queue-row', [
                            'patientId' => $p->patient_id,
                            'identifier' => \App\Helpers\PatientCode::format((int) $p->patient_id),
                            'patientName' => fullName($p->patient->last_name, $p->patient->first_name, $p->patient->middle_name, $p->patient->suffix),
                            'detail' => 'EDC ' . optional($p->edc)->format('M d, Y') . ' · ' . ($p->aog_weeks !== null ? $p->aog_weeks . ' wks' : '—'),
                            'badge' => 'due',
                            'badgeLabel' => 'Due soon',
                            'action' => 'log_prenatal_visit',
                            'hasActive' => true,
                        ])
                    @endforeach
                </div>
            @endif

            @if ($actionQueue['postnatal_slots']->isNotEmpty())
                <div class="rounded-xl border" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="px-4 py-2.5 border-b text-xs font-semibold uppercase tracking-wide flex items-center gap-2" style="border-color: var(--border); background: var(--accent-soft); color: var(--accent);">
                        <i class="fa-solid fa-baby" aria-hidden="true"></i> Postnatal due
                    </div>
                    @foreach ($actionQueue['postnatal_slots'] as $record)
                        @include('dashboard.partials.maternal-queue-row', [
                            'patientId' => $record->patient_id,
                            'identifier' => \App\Helpers\PatientCode::format((int) $record->patient_id),
                            'patientName' => fullName($record->patient->last_name, $record->patient->first_name, $record->patient->middle_name, $record->patient->suffix),
                            'detail' => 'Delivered ' . optional($record->delivery_date)->format('M d, Y'),
                            'badge' => 'due',
                            'badgeLabel' => 'Follow-up due',
                            'action' => 'log_postpartum',
                            'hasActive' => true,
                        ])
                    @endforeach
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'watchlist'" class="space-y-2">
            @if ($watchlist->isEmpty())
                <div class="rounded-xl border border-dashed p-6 sm:p-8 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                    <i class="fa-solid fa-shield-check text-2xl mb-2" style="color: var(--teal-soft);" aria-hidden="true"></i>
                    <p class="font-semibold text-sm" style="color: var(--ink);">No high-risk pregnancies</p>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">High-risk mothers flagged during registration appear here.</p>
                </div>
            @else
                <div class="rounded-xl border" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="px-4 py-2.5 border-b text-xs font-semibold uppercase tracking-wide flex items-center gap-2" style="border-color: var(--border); background: var(--amber-soft); color: var(--amber);">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High-risk pregnancies
                    </div>
                    @foreach ($watchlist as $wp)
                        @php
                            $flags = is_array($wp->risk_flags) ? $wp->risk_flags : json_decode($wp->risk_flags, true);
                            $flagLabels = collect($flags ?? [])->map(function($f) {
                                return match($f) {
                                    'age_under_18' => '<18',
                                    'age_over_35' => '>35',
                                    'hypertension' => 'HTN',
                                    'diabetes' => 'DM',
                                    'previous_csection' => 'CS',
                                    'multiple_gestation' => 'Multi',
                                    'previous_stillbirth' => 'Stillbirth',
                                    'others' => 'Other',
                                    default => $f,
                                };
                            })->join(', ');
                        @endphp
                        @include('dashboard.partials.maternal-queue-row', [
                            'patientId' => $wp->patient_id,
                            'identifier' => \App\Helpers\PatientCode::format((int) $wp->patient_id),
                            'patientName' => fullName($wp->patient->last_name, $wp->patient->first_name, $wp->patient->middle_name, $wp->patient->suffix),
                            'detail' => 'Risk: ' . $flagLabels . ' · EDC ' . optional($wp->edc)->format('M d, Y'),
                            'badge' => 'high-risk',
                            'badgeLabel' => 'High-risk',
                            'action' => 'log_prenatal_visit',
                            'hasActive' => true,
                        ])
                    @endforeach
                </div>
            @endif
        </div>

        @include('dashboard.partials.maternal-quick-action')
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

@push('scripts')
<script>
function maternalToday() {
    const today = new Date();
    const todayStr = today.toISOString().slice(0, 10);

    return {
        searchQuery: '',
        results: [],
        activeTab: 'action',
        selectedPatient: null,
        modalOpen: false,
        modalAction: 'register',
        modalTitle: '',
        modalSubtitle: '',
        submitting: false,
        formData: {
            lmp: '',
            visit_date: todayStr,
            bp_systolic: '',
            bp_diastolic: '',
            weight: '',
            temperature: '',
            fundic_height_cm: '',
            fetal_heart_tone_bpm: '',
            next_visit_date: '',
        },
        errors: {},
        todayStr: todayStr,
        riskFlagOptions: [
            { value: 'age_under_18', label: 'Age under 18' },
            { value: 'age_over_35', label: 'Age over 35' },
            { value: 'hypertension', label: 'Hypertension' },
            { value: 'diabetes', label: 'Diabetes' },
            { value: 'previous_csection', label: 'Previous C-section' },
            { value: 'multiple_gestation', label: 'Multiple gestation' },
            { value: 'previous_stillbirth', label: 'Previous stillbirth' },
            { value: 'others', label: 'Other risk factor' },
        ],

        init() {
            window.addEventListener('maternal-quick-open', (e) => {
                this.openQuick(e.detail);
            });
        },

        async searchPatients() {
            const q = this.searchQuery.trim();
            if (q.length < 2) { this.results = []; return; }
            try {
                const resp = await fetch(`{{ route('search.patients') }}?query=${encodeURIComponent(q)}`);
                this.results = await resp.json();
            } catch (e) {
                this.results = [];
            }
        },

        openQuick(patient) {
            this.searchQuery = '';
            this.results = [];
            this.errors = {};
            this.formData = {
                lmp: '',
                visit_date: todayStr,
                bp_systolic: '',
                bp_diastolic: '',
                weight: '',
                temperature: '',
                fundic_height_cm: '',
                fetal_heart_tone_bpm: '',
                next_visit_date: '',
            };
            this.selectedPatient = patient;

            if (patient.hasActive && patient.action !== 'register') {
                this.modalAction = patient.action || 'log_prenatal_visit';
                this.modalTitle = 'Log Visit';
                this.modalSubtitle = patient.patientName || patient.name || '';
            } else {
                this.modalAction = 'register';
                this.modalTitle = 'Register Pregnancy';
                this.modalSubtitle = patient.patientName || patient.name || '';
            }
            this.modalOpen = true;
        },

        async submitQuickAction() {
            this.submitting = true;
            this.errors = {};

            const form = document.getElementById('maternal-quick-form');
            const fd = new FormData(form);
            fd.set('action', this.modalAction);

            try {
                const resp = await fetch(`/maternal/quick/${this.selectedPatient.id || this.selectedPatient.patientId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                const data = await resp.json();

                if (!resp.ok) {
                    this.errors = data.errors || {};
                    Swal.fire({
                        icon: 'warning',
                        title: data.message || 'Something went wrong.',
                        confirmButtonColor: '#0d4a3c',
                    });
                    return;
                }

                this.modalOpen = false;
                Swal.fire({
                    icon: 'success',
                    title: data.message || 'Done.',
                    confirmButtonColor: '#0d4a3c',
                }).then(() => {
                    window.location.reload();
                });
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network error. Please try again.',
                    confirmButtonColor: '#0d4a3c',
                });
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush
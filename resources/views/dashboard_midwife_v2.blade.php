@extends('layouts.app')

@section('title', 'Maternal Dashboard')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6"
     x-data="maternalQueueDashboard()"
     x-init="init()">

    {{-- Page Header --}}
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Service-Tabbed Operational Hub</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Maternal, postnatal &amp; family planning queues</p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm"
             style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                  style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-calendar" aria-hidden="true"></i>
            </span>
            <div class="leading-tight">
                <div class="font-semibold" style="color: var(--ink);">{{ $todayLabel }}</div>
                <div class="text-xs" style="color: var(--ink-muted);">{{ $weekdayLabel }}</div>
            </div>
        </div>
    </div>

    {{-- KPI Metric Cards (Desktop) --}}
    <div class="hidden sm:grid grid-cols-2 lg:grid-cols-5 gap-3 animate-in opacity-0 delay-2">
        {{-- Prenatal --}}
        <div class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-person-pregnant" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Active Prenatal</p>
                <p class="kpi-card__value" x-text="counts.prenatal ?? {{ $prenatalRegistrants ?? 0 }}">-</p>
            </div>
        </div>
        {{-- Due This Month --}}
        <div class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Due This Month</p>
                <p class="kpi-card__value">{{ $dueThisMonth ?? 0 }}</p>
            </div>
        </div>
        {{-- Postnatal --}}
        <div class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <span class="kpi-card__icon" style="background: var(--accent-soft); color: var(--accent);">
                <i class="fa-solid fa-baby" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Postnatal Due</p>
                <p class="kpi-card__value" x-text="counts.postnatal ?? {{ $postnatalDue ?? 0 }}">-</p>
            </div>
        </div>
        {{-- Family Planning --}}
        <div class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <i class="fa-solid fa-pills" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">FP Scheduled</p>
                <p class="kpi-card__value" x-text="counts.fp ?? {{ $fpScheduled ?? 0 }}">-</p>
            </div>
        </div>
        {{-- High Risk --}}
        <div class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--amber);">
            <span class="kpi-card__icon" style="background: var(--amber-soft); color: var(--amber);">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Watchlist</p>
                <p class="kpi-card__value" x-text="counts.watchlist ?? {{ $highRiskReferrals ?? 0 }}">-</p>
            </div>
        </div>
    </div>

    {{-- Mobile Metric Summary Strip --}}
    <div class="sm:hidden animate-in opacity-0 delay-2" x-data="{ metricsOpen: false }">
        <button @click="metricsOpen = !metricsOpen"
                class="w-full flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition"
                style="background: var(--bg-surface); border-color: var(--border); color: var(--ink); box-shadow: var(--shadow-sm);">
            <span class="flex items-center gap-2">
                <i class="fa-solid fa-chart-simple" aria-hidden="true" style="color: var(--primary);"></i>
                <span>
                    <span x-text="counts.prenatal ?? {{ $prenatalRegistrants ?? 0 }}">{{ $prenatalRegistrants ?? 0 }}</span> Prenatal
                    <span class="mx-1" style="color: var(--border);">|</span>
                    <span x-text="counts.postnatal ?? {{ $postnatalDue ?? 0 }}">{{ $postnatalDue ?? 0 }}</span> Postnatal
                    <span class="mx-1" style="color: var(--border);">|</span>
                    <span x-text="counts.fp ?? {{ $fpScheduled ?? 0 }}">{{ $fpScheduled ?? 0 }}</span> FP
                </span>
            </span>
            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="{ 'rotate-180': metricsOpen }" style="color: var(--ink-muted);" aria-hidden="true"></i>
        </button>
        <div x-show="metricsOpen" x-collapse class="mt-2 space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl border p-3" style="background: var(--bg-surface); border-color: var(--border);">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Active Prenatal</p>
                    <p class="font-display font-semibold text-lg" style="color: var(--ink);" x-text="counts.prenatal ?? {{ $prenatalRegistrants ?? 0 }}">-</p>
                </div>
                <div class="rounded-xl border p-3" style="background: var(--bg-surface); border-color: var(--border);">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Due This Month</p>
                    <p class="font-display font-semibold text-lg" style="color: var(--ink);">{{ $dueThisMonth ?? 0 }}</p>
                </div>
                <div class="rounded-xl border p-3" style="background: var(--bg-surface); border-color: var(--border);">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Postnatal Due</p>
                    <p class="font-display font-semibold text-lg" style="color: var(--ink);" x-text="counts.postnatal ?? {{ $postnatalDue ?? 0 }}">-</p>
                </div>
                <div class="rounded-xl border p-3" style="background: var(--bg-surface); border-color: var(--border);">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">FP Scheduled</p>
                    <p class="font-display font-semibold text-lg" style="color: var(--ink);" x-text="counts.fp ?? {{ $fpScheduled ?? 0 }}">-</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search + Quick Intake + Tabs + Queue --}}
    <div class="animate-in opacity-0 delay-3 space-y-3">

        {{-- Search Bar + Quick Intake --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="relative flex-1">
                <label for="maternal-search" class="sr-only">Search patient</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </span>
                    <input id="maternal-search" type="search" x-model="searchQuery" @input.debounce.300ms="searchPatients()"
                           placeholder="Search patient by name or ID..."
                           autocomplete="off"
                           class="w-full rounded-xl border pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); background: var(--bg-surface); --tw-ring-color: var(--accent-blue);">
                </div>
                <div x-show="searchResults.length" x-cloak x-transition:enter
                     class="absolute z-20 left-0 right-0 mt-1 rounded-xl border shadow-lg overflow-hidden"
                     style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-md);">
                    <template x-for="(p, i) in searchResults" :key="p.id">
                        <button type="button" @click="openQuickFromSearch(p)"
                                :class="{ 'border-b': i < searchResults.length - 1 }"
                                class="w-full text-left px-4 py-3 text-sm transition hover:bg-black/[0.03] focus:outline-none focus:ring-2 focus:ring-inset flex items-center justify-between gap-2"
                                style="border-color: var(--border); --tw-ring-color: var(--accent-blue);">
                            <div class="min-w-0">
                                <div class="font-semibold truncate" style="color: var(--ink);" x-text="p.text"></div>
                                <div class="text-xs truncate" style="color: var(--ink-muted);" x-text="p.subtext"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Quick Intake Dropdown --}}
            <div class="relative shrink-0" x-data="{ intakeOpen: false }">
                <button type="button" @click="intakeOpen = !intakeOpen"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition hover:opacity-90"
                        style="background: var(--primary);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Quick Intake
                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="{ 'rotate-180': intakeOpen }" aria-hidden="true"></i>
                </button>
                <div x-show="intakeOpen" x-transition @click.outside="intakeOpen = false"
                     class="absolute right-0 top-full mt-1 rounded-xl border z-20 min-w-[220px] py-1"
                     style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-md);">
                    @if(auth()->user()->hasPermission('patients'))
                        <a href="{{ route('patients.create') }}"
                           class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium transition hover:bg-black/[0.03]"
                           style="color: var(--ink);">
                            <i class="fa-solid fa-user-plus" aria-hidden="true" style="color: var(--primary);"></i>
                            Register New Patient
                        </a>
                        <div class="my-1 border-t" style="border-color: var(--border);"></div>
                    @endif
                    @if(auth()->user()->hasPermission('maternal'))
                        <button type="button" @click="intakeOpen = false; startQuickIntake('log_prenatal_visit')"
                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-xs font-medium transition hover:bg-black/[0.03]"
                                style="color: var(--ink);">
                            <i class="fa-solid fa-person-pregnant" aria-hidden="true" style="color: var(--primary);"></i>
                            Log Prenatal Visit
                        </button>
                        <button type="button" @click="intakeOpen = false; startQuickIntake('log_postpartum')"
                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-xs font-medium transition hover:bg-black/[0.03]"
                                style="color: var(--ink);">
                            <i class="fa-solid fa-baby" aria-hidden="true" style="color: var(--accent);"></i>
                            Log Postnatal Visit
                        </button>
                        <button type="button" @click="intakeOpen = false; startQuickIntake('log_fp_visit')"
                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-xs font-medium transition hover:bg-black/[0.03]"
                                style="color: var(--ink);">
                            <i class="fa-solid fa-pills" aria-hidden="true" style="color: var(--accent-blue);"></i>
                            Log Family Planning Service
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Service Tabs --}}
        <div class="flex gap-1 border-b overflow-x-auto" style="border-color: var(--border);">
            @php
                $tabs = [
                    ['key' => 'all',       'icon' => 'fa-list-check',          'label' => 'All Queues'],
                    ['key' => 'prenatal',  'icon' => 'fa-person-pregnant',     'label' => 'Prenatal'],
                    ['key' => 'postnatal', 'icon' => 'fa-baby',                'label' => 'Postnatal'],
                    ['key' => 'fp',        'icon' => 'fa-pills',               'label' => 'Family Planning'],
                    ['key' => 'watchlist', 'icon' => 'fa-triangle-exclamation','label' => 'Watchlist'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button type="button" @click="switchTab('{{ $tab['key'] }}')"
                        :class="activeTab === '{{ $tab['key'] }}' ? 'border-b-2 font-semibold' : 'font-medium'"
                        class="px-3 py-2 text-xs sm:text-sm whitespace-nowrap transition flex items-center gap-1.5"
                        style="color: var(--ink); border-color: var(--primary);">
                    <i class="fa-solid fa-{{ $tab['icon'] }}" aria-hidden="true"></i>
                    {{ $tab['label'] }}
                    <span x-show="counts.{{ $tab['key'] }} > 0"
                          class="ml-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold"
                          :class="activeTab === '{{ $tab['key'] }}' ? 'text-white' : ''"
                          :style="activeTab === '{{ $tab['key'] }}' ? 'background: var(--primary);' : 'background: var(--border); color: var(--ink-muted);'"
                          x-text="counts.{{ $tab['key'] }}">
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Queue Container --}}
        <div class="relative">
            <div x-ref="queueContainer" class="space-y-2" :class="{ 'opacity-50 pointer-events-none': queueLoading }">
                @include('dashboards.partials.queue-cards', [
                    'items' => $items ?? collect(),
                    'tab' => $initialTab ?? 'all',
                ])
            </div>
            <div x-show="queueLoading" x-cloak class="absolute inset-0 flex items-center justify-center">
                <span class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold" style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted);">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Updating queue…
                </span>
            </div>
        </div>

        {{-- Quick Action Modal --}}
        @include('dashboard.partials.maternal-quick-action')

        {{-- Quick Intake Patient Search Overlay --}}
        <div x-show="quickIntakeModalOpen" x-cloak x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="quickIntakeModalOpen = false"
             style="background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
            <div @click.outside="quickIntakeModalOpen = false"
                 class="w-full max-w-md rounded-2xl border p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-lg);">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display font-semibold text-base" style="color: var(--ink);">
                        Select Patient
                    </h3>
                    <button type="button" @click="quickIntakeModalOpen = false"
                            class="w-8 h-8 rounded-lg flex items-center justify-center transition hover:bg-black/[0.05]"
                            style="color: var(--ink-muted);">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </span>
                    <input id="quick-intake-search" type="search"
                           x-model="quickIntakePatientSearch"
                           @input.debounce.300ms="searchQuickIntakePatients()"
                           placeholder="Search patient by name..."
                           autocomplete="off"
                           class="w-full rounded-xl border pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); background: var(--bg-surface); --tw-ring-color: var(--accent-blue);">
                </div>
                <div x-show="quickIntakeResults.length" class="mt-2 rounded-xl border overflow-hidden max-h-[240px] overflow-y-auto"
                     style="background: var(--bg-surface); border-color: var(--border);">
                    <template x-for="(p, i) in quickIntakeResults" :key="p.id">
                        <button type="button" @click="selectQuickIntakePatient(p)"
                                :class="{ 'border-b': i < quickIntakeResults.length - 1 }"
                                class="w-full text-left px-4 py-3 text-sm transition hover:bg-black/[0.03] flex items-center justify-between gap-2"
                                style="border-color: var(--border);">
                            <div class="min-w-0">
                                <div class="font-semibold truncate" style="color: var(--ink);" x-text="p.text"></div>
                                <div class="text-xs truncate" style="color: var(--ink-muted);" x-text="p.subtext"></div>
                            </div>
                        </button>
                    </template>
                </div>
                <div x-show="!quickIntakeResults.length && quickIntakePatientSearch.length >= 2" class="mt-3 text-center text-xs py-4" style="color: var(--ink-muted);">
                    No patients found. Try a different search.
                </div>
            </div>
        </div>
    </div>

    {{-- Collapsible Results Ready --}}
    @if ($showResultsReady ?? false)
        @include('dashboard.partials.results-ready', [
            'panelTitle' => 'Results Ready',
            'panelSubtitle' => 'Completed & Finalized Consultations Ready for Print',
            'showFilters' => true,
            'filterAction' => route('dashboard'),
            'layout' => $layout ?? 'block',
        ])
    @endif
</div>
@endsection

@push('scripts')
<script>
function maternalQueueDashboard() {
    const today = new Date();
    const todayStr = today.toISOString().slice(0, 10);

    return {
        searchQuery: '',
        searchResults: [],
        activeTab: 'all',
        selectedPatient: null,
        modalOpen: false,
        modalAction: 'register',
        modalTitle: '',
        modalSubtitle: '',
        submitting: false,
        formData: {
            lmp: '',
            visit_date: todayStr,
            mode_of_transaction: '',
            nature_of_visit: '',
            chief_complaint: '',
            bp_systolic: '',
            bp_diastolic: '',
            weight: '',
            height: '',
            temperature: '',
            fundic_height_cm: '',
            fetal_heart_tone_bpm: '',
            next_visit_date: '',
            method: '',
        },
        errors: {},
        todayStr: todayStr,
        currentHash: '',
        queueLoading: false,
        counts: {
            all: {{ ($items ?? collect())->count() }},
            prenatal: {{ $prenatalRegistrants ?? 0 }},
            postnatal: {{ $postnatalDue ?? 0 }},
            fp: {{ $fpScheduled ?? 0 }},
            watchlist: {{ $highRiskReferrals ?? 0 }},
        },
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
        quickIntakePatientSearch: '',
        quickIntakeResults: [],
        quickIntakeAction: '',
        quickIntakeModalOpen: false,

        init() {
            window.addEventListener('maternal-quick-open', (e) => {
                this.openQuick(e.detail);
            });

            window.addEventListener('maternal-queue-stale', (e) => {
                if (e.detail && e.detail.counts) {
                    Object.assign(this.counts, e.detail.counts);
                }
                this.refreshQueue();
            });
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.refreshQueue();
        },

        async updateCounts() {
            try {
                const resp = await fetch("{{ route('consultations.live-requests') }}", {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (! resp.ok) return;
                const data = await resp.json();
                if (data.queue_counts) {
                    Object.assign(this.counts, data.queue_counts);
                }
            } catch (e) {
                console.error('Counts refresh failed:', e);
            }
        },

        async refreshQueue() {
            this.queueLoading = true;
            try {
                const resp = await fetch(`{{ route('maternal.queue-partial') }}?tab=${this.activeTab}`, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (resp.ok) {
                    this.$refs.queueContainer.innerHTML = await resp.text();
                }
            } catch (e) {
                console.error('Queue refresh failed:', e);
            } finally {
                this.queueLoading = false;
            }
        },

        async searchPatients() {
            const q = this.searchQuery.trim();
            if (q.length < 2) { this.searchResults = []; return; }
            try {
                const resp = await fetch(`{{ route('search.patients') }}?query=${encodeURIComponent(q)}`);
                this.searchResults = await resp.json();
            } catch (e) {
                this.searchResults = [];
            }
        },

        openQuickFromSearch(patient) {
            this.searchQuery = '';
            this.searchResults = [];
            this.openQuick(patient);
        },

        startQuickIntake(action) {
            this.quickIntakeAction = action;
            this.quickIntakeModalOpen = true;
            this.quickIntakePatientSearch = '';
            this.quickIntakeResults = [];
            this.$nextTick(() => {
                const el = document.getElementById('quick-intake-search');
                if (el) el.focus();
            });
        },

        async searchQuickIntakePatients() {
            const q = this.quickIntakePatientSearch.trim();
            if (q.length < 2) { this.quickIntakeResults = []; return; }
            try {
                const resp = await fetch(`{{ route('search.patients') }}?query=${encodeURIComponent(q)}`);
                this.quickIntakeResults = await resp.json();
            } catch (e) {
                this.quickIntakeResults = [];
            }
        },

        selectQuickIntakePatient(patient) {
            this.quickIntakeModalOpen = false;
            this.quickIntakeResults = [];
            this.openQuick({
                id: patient.id,
                patientId: patient.id,
                action: this.quickIntakeAction,
                patientName: patient.text || patient.name || '',
                hasActive: false,
            });
        },

        openQuick(patient) {
            this.errors = {};
            this.formData = {
                lmp: '',
                visit_date: todayStr,
                mode_of_transaction: '',
                nature_of_visit: '',
                chief_complaint: '',
                bp_systolic: '',
                bp_diastolic: '',
                weight: '',
                height: '',
                temperature: '',
                fundic_height_cm: '',
                fetal_heart_tone_bpm: '',
                next_visit_date: '',
                method: '',
            };
            this.selectedPatient = patient;

            const validActions = ['register', 'log_prenatal_visit', 'log_postpartum', 'log_fp_visit'];
            const action = validActions.includes(patient.action) ? patient.action : 'register';

            this.modalAction = action;
            this.modalTitle = action === 'register' ? 'Register Pregnancy' : 'Log Visit';
            this.modalSubtitle = patient.patientName || patient.name || '';
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
                    headers: { 'Accept': 'application/json' },
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
                });
                this.updateCounts();
                this.refreshQueue();
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

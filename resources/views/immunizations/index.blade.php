@extends('layouts.app')

@section('title', 'Immunization')

@section('content')
<div class="space-y-5 lg:space-y-6" x-data="immunizationIndex()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization tracking</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage today’s queue, record doses, and follow up defaulters.</p>
        </div>
        <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: #0d4a3c;">
            Add new patient
        </a>
    </div>

    <div class="rounded-xl" x-data="patientSearch($data)">
        <div class="relative">
            <span class="absolute inset-y-0 flex items-center pointer-events-none" style="color: var(--ink-subtle); left: calc(0.75rem);">
            </span>
            <input type="text" x-model="query" @input.debounce.300ms="search()"
                   placeholder="Search patient"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                   autocomplete="off">
        </div>
        <div x-show="results.length > 0" class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="patientUrl(patient.id)" class="block px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">
                                <span x-text="patient.subtext"></span>
                                <span class="font-semibold" style="color: var(--primary);"> - View immunization history</span>
                            </div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" class="mt-2 text-xs" style="display: none; color: var(--ink-muted);">
            No patient found. <a href="{{ route('patients.create') }}" class="font-semibold" style="color: var(--primary);">Register a new patient</a>.
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <button type="button" @click="activeTab = 'due'; dueFilter = 'today'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Due today</p>
            <div class="flex items-end justify-between gap-3">
                <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">{{ number_format($dueTodayCount ?? 0) }}</p>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">Tap to view queue</span>
            </div>
        </button>

        <button type="button" @click="activeTab = 'due'; dueFilter = 'overdue'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Overdue / defaulters</p>
            <div class="flex items-end justify-between gap-3">
                <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">{{ number_format($overdueCount ?? 0) }}</p>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-soft); color: var(--accent);">Priority</span>
            </div>
        </button>

        <button type="button" @click="activeTab = 'due'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Infant coverage (0–11 months)</p>
            <div class="flex items-end justify-between gap-3">
                <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">
                    @if (is_null($infantCoveragePercent))
                        —
                    @else
                        {{ $infantCoveragePercent }}%
                    @endif
                </p>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                    {{ number_format($infantTotal ?? 0) }} infants
                </span>
            </div>
        </button>
    </div>

    <div>
        <div class="flex items-end justify-between gap-3 mb-3">
            <div>
                <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Queue</h2>
                <p class="text-xs mt-0.5" style="color: var(--ink-muted);">Focus on who needs action today.</p>
            </div>
            <div class="inline-flex rounded-xl border p-1" style="border-color: var(--border); background: var(--bg-surface);">
                <button type="button" @click="activeTab = 'due'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeTab === 'due' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                    Due today
                </button>
                <button type="button" @click="activeTab = 'recent'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeTab === 'recent' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                    Recent
                </button>
            </div>
        </div>

        <div x-show="activeTab === 'due'" x-cloak class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Due date</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Dose</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @php($today = \Carbon\Carbon::today())
                        @forelse ($dueTodayPatients as $p)
                            @php($dueDate = $p->next_due_date ? \Carbon\Carbon::parse($p->next_due_date) : null)
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">
                                    <button type="button" class="text-left hover:underline font-medium" style="color: var(--primary);" @click="openPatient({{ (int) $p->patient_id }}, @js($p->last_name . ', ' . $p->first_name))">
                                        {{ $p->last_name }}, {{ $p->first_name }}
                                    </button>
                                </td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap" style="color: var(--ink);">
                                    @if ($dueDate)
                                        {{ $dueDate->format('M d, Y') }}
                                    @else
                                        —
                                    @endif

                                    @if ($dueDate && $dueDate->lt($today))
                                        <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-soft); color: var(--accent);">Overdue</span>
                                    @elseif ($dueDate && $dueDate->isSameDay($today))
                                        <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">Due today</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink-muted);">{{ $p->dose_number }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $p->vaccine_name }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--primary);" @click="openPatient({{ (int) $p->patient_id }}, @js($p->last_name . ', ' . $p->first_name))">
                                        Check-in / Record
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 lg:px-4 py-12 text-center">
                                    <div class="flex justify-center mb-3"><i class="fa-solid fa-syringe text-3xl" style="color: var(--ink-subtle);"></i></div>
                                    <p class="text-sm font-medium" style="color: var(--ink);">No patients due today</p>
                                    <p class="text-xs mt-1" style="color: var(--ink-muted);">Use the search box above to find a patient and record a vaccination dose</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTab === 'recent'" x-cloak class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Date</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Dose</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Status</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Given by</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @php($today = \Carbon\Carbon::today())
                        @forelse ($recentRecords as $r)
                            @php($nextDue = $r->next_due_date ? \Carbon\Carbon::parse($r->next_due_date) : null)
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap" style="color: var(--ink);">{{ \Carbon\Carbon::parse($r->date_given)->format('M d, Y') }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->last_name }}, {{ $r->first_name }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->vaccine_name }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink-muted);">{{ $r->dose_number }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell">
                                    @if (! $nextDue)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">Up to date</span>
                                    @elseif ($nextDue->lt($today))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-soft); color: var(--accent);">Overdue</span>
                                    @elseif ($nextDue->isSameDay($today))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">Due today</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">In progress</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $r->worker_name ?? '—' }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                    <button type="button" class="text-sm font-medium hover:underline" style="color: var(--primary);" @click="openPatient({{ (int) $r->patient_id }}, @js($r->last_name . ', ' . $r->first_name))">
                                        Open
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 lg:px-4 py-12 text-center">
                                    <div class="flex justify-center mb-3"><i class="fa-solid fa-clock-rotate-left text-3xl" style="color: var(--ink-subtle);"></i></div>
                                    <p class="text-sm font-medium" style="color: var(--ink);">No recent records</p>
                                    <p class="text-xs mt-1" style="color: var(--ink-muted);">Immunization records will appear here once you start recording doses</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="drawerOpen"
             x-cloak
             class="fixed inset-0 z-[100]"
             aria-modal="true"
             role="dialog"
             @keydown.escape.window="closeDrawer()">
            <div class="absolute inset-0" style="background: rgba(17, 24, 39, 0.55);" @click="closeDrawer()"></div>
            <div class="absolute inset-y-0 right-0 w-full sm:w-[520px] lg:w-[640px] border-l shadow-xl flex flex-col" style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <div class="p-4 border-b flex items-start justify-between gap-3" style="border-color: var(--border); background: var(--bg-surface);">
                    <div>
                        <p class="text-xs font-medium" style="color: var(--ink-muted);">Patient</p>
                        <p class="font-display font-semibold text-lg leading-tight" style="color: var(--ink);" x-text="drawerTitle || 'Immunizations'"></p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold transition" style="background: rgba(0,0,0,0.06); color: var(--ink);" @click="closeDrawer()">
                        Close
                    </button>
                </div>
                <div class="flex-1 overflow-hidden">
                    <iframe :src="drawerUrl" class="w-full h-full" style="background: var(--bg-page);" title="Patient immunizations"></iframe>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function immunizationIndex() {
        return {
            patientRouteTemplate: @json(route('immunizations.patient', ['id' => '__PATIENT_ID__'])),
            activeTab: 'due',
            dueFilter: 'today',
            drawerOpen: false,
            drawerUrl: '',
            drawerTitle: '',
            init() {
                this.$watch('drawerOpen', (open) => {
                    document.body.classList.toggle('overflow-hidden', open);
                });
            },
            patientUrl(patientId) {
                return this.patientRouteTemplate.replace('__PATIENT_ID__', patientId);
            },
            openPatient(patientId, title) {
                this.drawerUrl = this.patientUrl(patientId);
                this.drawerTitle = title ?? 'Immunizations';
                this.drawerOpen = true;
            },
            closeDrawer() {
                this.drawerOpen = false;
                this.drawerUrl = '';
            },
        };
    }

    function patientSearch(parent) {
        return {
            parent,
            patientRouteTemplate: @json(route('immunizations.patient', ['id' => '__PATIENT_ID__'])),
            query: '',
            results: [],
            loading: false,
            patientUrl(patientId) {
                return this.patientRouteTemplate.replace('__PATIENT_ID__', patientId);
            },
            openFromSearch(patient) {
                window.location.href = this.patientUrl(patient.id);
            },
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await fetch(`/search/patients?query=${this.query}`);
                    this.results = await response.json();
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }
</script>
@endsection

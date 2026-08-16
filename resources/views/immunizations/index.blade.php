@extends('layouts.app')

@section('title', 'Immunization')

@section('content')
@php
    $hasDateRange = filled($dateFrom) || filled($dateTo);
    $isCurrentMonth = $month === \Carbon\Carbon::today()->format('Y-m');
    $dueDateLabel = $hasDateRange
        ? 'Due from '.$dueWindowStart->format('M j').' to '.$dueWindowEnd->format('M j')
        : ($isCurrentMonth ? 'Due this month' : 'Due in '.$dueWindowStart->format('F Y'));
    $statusBadges = [
        'due' => ['bg' => 'var(--accent-blue-soft)', 'fg' => 'var(--accent-blue)'],
        'overdue' => ['bg' => 'var(--danger-soft)', 'fg' => 'var(--danger)'],
        'no_show' => ['bg' => 'var(--danger-soft)', 'fg' => 'var(--danger)'],
    ];
@endphp

<div class="space-y-5 lg:space-y-6" x-data="immunizationIndex()" @checkin-close.window="closeCheckin()">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization tracking</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage the queue, record doses, and follow up defaulters.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($mode === 'child')
                <a href="{{ route('immunizations.enroll-infant.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md"
                   style="background: var(--primary);">
                    <i class="fa-solid fa-baby mr-1.5" aria-hidden="true"></i> Enroll infant
                </a>
            @endif
            <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border text-sm font-semibold transition duration-200 hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                Add new patient
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex self-start rounded-xl border p-1" style="border-color: var(--border); background: var(--bg-surface);" role="tablist" aria-label="Immunization mode">
            <a href="{{ route('immunizations.index', ['mode' => 'child']) }}" role="tab" aria-selected="{{ $mode === 'child' ? 'true' : 'false' }}"
               class="px-4 py-1.5 rounded-lg text-xs font-semibold transition"
               style="{{ $mode === 'child' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);' }}">
                <i class="fa-solid fa-baby mr-1" aria-hidden="true"></i> Child
            </a>
            <a href="{{ route('immunizations.index', ['mode' => 'adult']) }}" role="tab" aria-selected="{{ $mode === 'adult' ? 'true' : 'false' }}"
               class="px-4 py-1.5 rounded-lg text-xs font-semibold transition"
               style="{{ $mode === 'adult' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);' }}">
                <i class="fa-solid fa-user mr-1" aria-hidden="true"></i> Adult
            </a>
        </div>

        <form method="GET" action="{{ route('immunizations.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <div>
                <label for="filter_zone" class="sr-only">Filter by purok</label>
                <select id="filter_zone" name="zone_id" @change="$el.form.submit()"
                        class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="">All puroks</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected((int) $zoneId === (int) $zone->id)>{{ $zone->zone_number }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_month" class="sr-only">Filter by month</label>
                <select id="filter_month" name="month" @change="$el.form.date_from.value = ''; $el.form.date_to.value = ''; $el.form.submit()"
                        class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach ($monthOptions as $monthValue => $monthLabel)
                        <option value="{{ $monthValue }}" @selected($month === $monthValue)>{{ $monthLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-1.5">
                <label for="filter_date_from" class="sr-only">Filter from date</label>
                <input id="filter_date_from" type="date" name="date_from" value="{{ $dateFrom ?? '' }}"
                       class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                <span class="text-xs" style="color: var(--ink-muted);">to</span>
                <label for="filter_date_to" class="sr-only">Filter to date</label>
                <input id="filter_date_to" type="date" name="date_to" value="{{ $dateTo ?? '' }}"
                       class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                <button type="submit" class="rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                    Apply range
                </button>
            </div>
            @if ($zoneId !== null || $month !== \Carbon\Carbon::today()->format('Y-m') || $hasDateRange)
                <a href="{{ route('immunizations.index', ['mode' => $mode]) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="color: var(--ink-muted);">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
                </a>
            @endif
        </form>
    </div>

    @if ($mode === 'child')
        <div class="rounded-xl border p-4 lg:p-5 max-w-md" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Infant coverage (0–11 mo)</p>
            <div class="flex items-end justify-between gap-3">
                <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">
                    {{ is_null($infantCoveragePercent) ? '-' : $infantCoveragePercent.'%' }}
                </p>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                    {{ number_format($infantTotal) }} infants
                </span>
            </div>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 max-w-2xl">
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Adults enrolled</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">{{ number_format($adultEnrolled) }}</p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                        <i class="fa-solid fa-user mr-1" aria-hidden="true"></i>18+ enrolled
                    </span>
                </div>
            </div>
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);">Doses given by vaccine</p>
                @forelse ($adultDosesByVaccine as $adultDose)
                    <div class="flex items-center justify-between gap-3 py-1 border-b last:border-0" style="border-color: var(--border);">
                        <span class="text-sm" style="color: var(--ink);">{{ $adultDose->vaccine_name }}</span>
                        <span class="text-sm font-semibold" style="color: var(--primary);">{{ number_format($adultDose->doses_count) }}</span>
                    </div>
                @empty
                    <p class="text-xs" style="color: var(--ink-muted);">No adult doses recorded yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="rounded-xl" x-data="patientSearch()">
        <div class="relative">
            <span class="absolute inset-y-0 flex items-center pointer-events-none pl-3" style="color: var(--ink-subtle);">
                <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
            </span>
            <label for="patient_search" class="sr-only">Search patient</label>
            <input id="patient_search" type="text" x-model="query" @input.debounce.300ms="search()"
                   placeholder="Search patient"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                   autocomplete="off">
        </div>
        <div x-show="results.length > 0" x-cloak class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="'{{ route('immunizations.patient', ['id' => '__PATIENT_ID__']) }}'.replace('__PATIENT_ID__', patient.id)" class="block w-full text-left px-4 py-2.5">
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
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-cloak class="mt-2 text-xs" style="display: none; color: var(--ink-muted);">
            No patient found. <a href="{{ route('patients.create') }}" class="font-semibold" style="color: var(--primary);">Register a new patient</a>.
        </div>
        <div x-show="loading" x-cloak class="mt-2 text-xs" style="display: none; color: var(--ink-muted);">
            Searching...
        </div>
    </div>

    @if ($mode === 'child')
        <div>
            <div class="flex items-end justify-between gap-3 mb-3">
                <div>
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Queue</h2>
                    <p class="text-xs mt-0.5" style="color: var(--ink-muted);">Focus on who needs action today.</p>
                </div>
                <div class="inline-flex flex-wrap rounded-xl border p-1" style="border-color: var(--border); background: var(--bg-surface);" role="tablist" aria-label="Queue view">
                    <button type="button" @click="activeQueue = 'due'" role="tab" :aria-selected="activeQueue === 'due' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeQueue === 'due' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                        {{ $dueDateLabel }}
                        <span class="ml-1 opacity-70">{{ number_format($dueTodayCount) }}</span>
                    </button>
                    <button type="button" @click="activeQueue = 'overdue'" role="tab" :aria-selected="activeQueue === 'overdue' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeQueue === 'overdue' ? 'background: var(--danger-soft); color: var(--danger);' : 'color: var(--ink-muted);'">
                        Overdue
                        <span class="ml-1 opacity-70">{{ number_format($overdueCount) }}</span>
                    </button>
                    <button type="button" @click="activeQueue = 'no_show'" role="tab" :aria-selected="activeQueue === 'no_show' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeQueue === 'no_show' ? 'background: var(--danger-soft); color: var(--danger);' : 'color: var(--ink-muted);'">
                        No-show
                        <span class="ml-1 opacity-70">{{ number_format($noShowCount) }}</span>
                    </button>
                    <button type="button" @click="activeQueue = 'recent'" role="tab" :aria-selected="activeQueue === 'recent' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeQueue === 'recent' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                        Recent
                    </button>
                </div>
            </div>

            @foreach (['due', 'overdue', 'no_show'] as $queueKey)
                @php
                    $badge = $statusBadges[$queueKey];
                    $queueGroups = ($queues[$queueKey] ?? collect())->groupBy(fn (array $entry) => $entry['patient']->id);
                @endphp
                <div x-show="activeQueue === '{{ $queueKey }}'" x-cloak class="rounded-xl border overflow-hidden"
                     @if ($queueKey === 'due') style="background: var(--bg-surface-elevated); border-color: var(--border);" @else style="display: none; background: var(--bg-surface-elevated); border-color: var(--border);" @endif>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead style="background: var(--teal-soft);">
                                <tr>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Due doses</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                @forelse ($queueGroups as $patientId => $entries)
                                    @php($queuePatient = $entries->first()['patient'])
                                    <tr class="transition-colors hover:bg-black/[0.02]">
                                        <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                            <a href="{{ route('immunizations.patient', $queuePatient->id) }}" class="hover:underline font-medium" style="color: var(--primary);">
                                                {{ fullName($queuePatient->last_name, $queuePatient->first_name, $queuePatient->middle_name, $queuePatient->suffix) }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                @include('immunizations.partials._age-chip', ['patient' => $queuePatient])
                                                <span class="text-xs" style="color: var(--ink-muted);">
                                                    <i class="fa-solid fa-location-dot mr-0.5" aria-hidden="true"></i>
                                                    {{ $queuePatient->household?->zone?->zone_number ?? 'No purok' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 lg:px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @foreach ($entries as $entry)
                                                    @php($queueVaccine = $entry['vaccine'])
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap" style="background: {{ $badge['bg'] }}; color: {{ $badge['fg'] }};">
                                                        {{ $queueVaccine->vaccine_name }} {{ $entry['dose_number'] }}
                                                        @if ($entry['due_date'] !== null)
                                                            · {{ $entry['due_date']->format('M j') }}
                                                        @endif
                                                    </span>
                                                    @if ($queueKey === 'no_show')
                                                        <form method="POST" action="{{ route('immunizations.no-show') }}" @submit.prevent="confirmClearNoShow($event.target, @js(fullName($queuePatient->last_name, $queuePatient->first_name, $queuePatient->middle_name, $queuePatient->suffix)))">
                                                            @csrf
                                                            <input type="hidden" name="no_show" value="0">
                                                            <input type="hidden" name="patient_id" value="{{ $queuePatient->id }}">
                                                            <input type="hidden" name="vaccine_id" value="{{ $queueVaccine->id }}">
                                                            <button type="submit" title="Clear no-show" aria-label="Clear no-show for {{ $queueVaccine->vaccine_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-full border text-xs transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                                                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-3 lg:px-4 py-3 text-right whitespace-nowrap">
                                            <button type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--primary);" @click="toggleCheckin('checkin_{{ $queueKey }}_{{ $patientId }}', {{ $patientId }})">
                                                <i class="fa-solid fa-syringe" aria-hidden="true"></i> Record doses
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="expandedPatientId === {{ $patientId }}"
                                        x-cloak
                                        class="bg-black/[0.01]"
                                        style="display: none;">
                                        <td colspan="3" class="p-0">
                                            <div x-show="checkinLoading && expandedPatientId === {{ $patientId }}" class="flex items-center justify-center gap-2 px-4 py-6">
                                                <div class="animate-spin inline-block w-5 h-5 border-2 border-current border-t-transparent rounded-full" style="color: var(--primary);" role="status" aria-label="Loading"></div>
                                                <p class="text-xs" style="color: var(--ink-muted);">Loading schedule...</p>
                                            </div>
                                            <div x-ref="checkin_{{ $queueKey }}_{{ $patientId }}"></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 lg:px-4 py-10 text-center">
                                            <x-empty-state
                                                :icon="match ($queueKey) {
                                                    'due' => 'fa-regular fa-calendar',
                                                    'overdue' => 'fa-solid fa-circle-check',
                                                    'no_show' => 'fa-solid fa-user-clock',
                                                }"
                                                :title="match ($queueKey) {
                                                    'due' => 'No patients '.($isCurrentMonth && ! $hasDateRange ? 'due this month' : ($hasDateRange ? 'due in the selected range' : 'due in '.$dueWindowStart->format('F Y'))),
                                                    'overdue' => 'No overdue patients',
                                                    'no_show' => 'No no-show cases',
                                                }"
                                                description="This queue is clear. New entries appear here as the schedule rolls." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div x-show="activeQueue === 'recent'" x-cloak style="display: none;">
                @include('immunizations.partials._recent-table', ['records' => $recentRecords])
            </div>
        </div>
    @else
        <div>
            <div class="flex items-end justify-between gap-3 mb-3">
                <div>
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Queue</h2>
                    <p class="text-xs mt-0.5" style="color: var(--ink-muted);">Focus on who needs action today.</p>
                </div>
                <div class="inline-flex rounded-xl border p-1" style="border-color: var(--border); background: var(--bg-surface);" role="tablist" aria-label="Queue view">
                    <button type="button" @click="activeTab = 'due'" role="tab" :aria-selected="activeTab === 'due' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeTab === 'due' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                        {{ $dueDateLabel }}
                    </button>
                    <button type="button" @click="activeTab = 'recent'" role="tab" :aria-selected="activeTab === 'recent' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeTab === 'recent' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                        Recent
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'due'" class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <div class="overflow-x-auto">
                    @php($dueGroups = ($queues['due'] ?? collect())->groupBy(fn (array $entry) => $entry['patient']->id))
                    <table class="min-w-full text-sm">
                        <thead style="background: var(--teal-soft);">
                            <tr>
                                <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                                <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Due doses</th>
                                <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            @forelse ($dueGroups as $patientId => $entries)
                                @php($duePatient = $entries->first()['patient'])
                                <tr class="transition-colors hover:bg-black/[0.02]">
                                    <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">
                                        <a href="{{ route('immunizations.patient', $duePatient->id) }}" class="hover:underline font-medium" style="color: var(--primary);">
                                            {{ fullName($duePatient->last_name, $duePatient->first_name, $duePatient->middle_name, $duePatient->suffix) }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-1.5">
                                            @include('immunizations.partials._age-chip', ['patient' => $duePatient])
                                            <span class="text-xs" style="color: var(--ink-muted);">
                                                <i class="fa-solid fa-location-dot mr-0.5" aria-hidden="true"></i>
                                                {{ $duePatient->household?->zone?->zone_number ?? 'No purok' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach ($entries as $entry)
                                                @php($dueVaccine = $entry['vaccine'])
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                                    {{ $dueVaccine->vaccine_name }} {{ $entry['dose_number'] }}
                                                    @if ($entry['due_date'] !== null)
                                                        · {{ $entry['due_date']->format('M j') }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                        <button type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--primary);" @click="toggleCheckin('checkin_adult_{{ $patientId }}', {{ $patientId }})">
                                            <i class="fa-solid fa-syringe" aria-hidden="true"></i> Record doses
                                        </button>
                                    </td>
                                </tr>
                                <tr x-show="expandedPatientId === {{ $patientId }}"
                                    x-cloak
                                    class="bg-black/[0.01]"
                                    style="display: none;">
                                    <td colspan="3" class="p-0">
                                        <div x-show="checkinLoading && expandedPatientId === {{ $patientId }}" class="flex items-center justify-center gap-2 px-4 py-6">
                                            <div class="animate-spin inline-block w-5 h-5 border-2 border-current border-t-transparent rounded-full" style="color: var(--primary);" role="status" aria-label="Loading"></div>
                                            <p class="text-xs" style="color: var(--ink-muted);">Loading schedule...</p>
                                        </div>
                                        <div x-ref="checkin_adult_{{ $patientId }}"></div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 lg:px-4 py-12 text-center">
                                        <x-empty-state icon="fa-regular fa-calendar" title="No patients in this queue"
                                                       description="Use the search box above to find a patient and record a vaccination dose." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="activeTab === 'recent'" x-cloak style="display: none;">
                @include('immunizations.partials._recent-table', ['records' => $recentRecords])
            </div>
        </div>
    @endif

</div>

<script>
    function immunizationIndex() {
        return {
            checkinRouteTemplate: @json(route('immunizations.checkin', ['patient' => '__PATIENT_ID__'])),
            activeQueue: 'due',
            activeTab: 'due',
            expandedPatientId: null,
            expandedRef: null,
            checkinLoading: false,
            checkinSeq: 0,
            checkinUrl(patientId) {
                return this.checkinRouteTemplate.replace('__PATIENT_ID__', patientId);
            },
            toggleCheckin(ref, patientId) {
                if (this.expandedPatientId === patientId) {
                    this.closeCheckin();
                    return;
                }
                this.destroyCheckinTree(this.expandedRef);
                this.expandedPatientId = patientId;
                this.expandedRef = ref;
                this.loadCheckin(ref, patientId);
            },
            closeCheckin() {
                this.checkinSeq++;
                this.destroyCheckinTree(this.expandedRef);
                this.expandedPatientId = null;
                this.expandedRef = null;
                this.checkinLoading = false;
            },
            destroyCheckinTree(ref) {
                this.clearCheckinHost(ref ? this.$refs[ref] : null);
            },
            clearCheckinHost(container) {
                if (!container) return;
                while (container.firstElementChild) {
                    if (window.Alpine) window.Alpine.destroyTree(container.firstElementChild);
                    container.removeChild(container.firstElementChild);
                }
            },
            async loadCheckin(ref, patientId) {
                const seq = ++this.checkinSeq;
                this.checkinLoading = true;
                const container = this.$refs[ref];
                this.clearCheckinHost(container);
                try {
                    const response = await safeFetch(this.checkinUrl(patientId));
                    const data = response.ok ? await response.json() : {};
                    if (! response.ok) throw new Error(`HTTP ${response.status}`);
                    const html = await response.text();
                    if (this.expandedPatientId !== patientId || seq !== this.checkinSeq) return;
                    const el = this.$refs[ref];
                    if (el) {
                        el.innerHTML = html;
                        if (window.Alpine) window.Alpine.initTree(el);
                    }
                } catch (e) {
                    console.error('Check-in load failed:', e);
                    if (this.expandedPatientId === patientId && seq === this.checkinSeq) {
                        const el = this.$refs[ref];
                        if (el) el.innerHTML = '<div class="px-4 py-6 text-center text-sm" style="color: var(--ink-muted);">Could not load the schedule. Close and try again.</div>';
                    }
                } finally {
                    if (seq === this.checkinSeq) this.checkinLoading = false;
                }
            },
            confirmNoShow(form, patientLabel) {
                Swal.fire({
                    title: 'Mark as no-show?',
                    html: `<p class="text-sm">${patientLabel} missed their scheduled dose. This is recorded as a missed appointment in their history.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, mark no-show',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            },
            confirmClearNoShow(form, patientLabel) {
                Swal.fire({
                    title: 'Clear no-show?',
                    html: `<p class="text-sm">${patientLabel} showed up after all. The missed appointment stays in history, but the patient returns to the queue.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, clear',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: 'var(--primary)',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            },
            confirmMarkDone(form) {
                Swal.fire({
                    title: 'Mark as done elsewhere?',
                    html: '<p class="text-sm">Use this only when the dose was given at another facility. The dose is recorded with <strong>today\'s date</strong> and no temperature is required.</p>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, mark done',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: 'var(--primary)',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            },
        };
    }

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
                    const data = await response.json();
                    this.results = data.map(item => ({
                        ...item,
                        text: item.text.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '),
                    }));
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }
</script>
@endsection
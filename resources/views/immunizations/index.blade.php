@extends('layouts.app')

@section('title', 'Immunization')

@section('content')
@php
    $dueDateLabel = \Carbon\Carbon::parse($date)->isToday() ? 'Due today' : 'Due '.\Carbon\Carbon::parse($date)->format('M d');
    $statusBadges = [
        'due' => ['bg' => 'var(--accent-blue-soft)', 'fg' => 'var(--accent-blue)', 'icon' => 'fa-regular fa-calendar', 'label' => 'Due'],
        'overdue' => ['bg' => 'var(--danger-soft)', 'fg' => 'var(--danger)', 'icon' => 'fa-solid fa-circle-exclamation', 'label' => 'Overdue'],
        'out_of_window' => ['bg' => 'var(--amber-soft)', 'fg' => 'var(--amber)', 'icon' => 'fa-solid fa-clock', 'label' => 'Out of window'],
        'no_show' => ['bg' => 'var(--danger-soft)', 'fg' => 'var(--danger)', 'icon' => 'fa-solid fa-user-clock', 'label' => 'No-show'],
    ];
@endphp

<div class="space-y-5 lg:space-y-6" x-data="immunizationIndex()">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization tracking</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage the queue, record doses, and follow up defaulters.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($mode === 'child')
                <button type="button" @click="$dispatch('open-enroll-modal')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md"
                        style="background: var(--primary);">
                    <i class="fa-solid fa-baby mr-1.5" aria-hidden="true"></i> Enroll infant
                </button>
            @endif
            <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border text-sm font-semibold transition duration-200 hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                Add new patient
            </a>
        </div>
    </div>

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
                <select id="filter_zone" name="zone_id" @change="this.form.submit()"
                        class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="">All puroks</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected((int) $zoneId === (int) $zone->id)>{{ $zone->zone_number }}</option>
                    @endforeach
                </select>
            </div>
            @if ($mode === 'child')
                <div>
                    <label for="filter_date" class="sr-only">Filter by due date</label>
                    <input id="filter_date" type="date" name="date" value="{{ $date }}" max="{{ \Carbon\Carbon::today()->addYear()->toDateString() }}"
                           @change="this.form.submit()"
                           class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            @endif
            @if ($zoneId !== null || ($mode === 'child' && $date !== \Carbon\Carbon::today()->toDateString()))
                <a href="{{ route('immunizations.index', ['mode' => $mode]) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="color: var(--ink-muted);">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
                </a>
            @endif
        </form>
    </div>

    @if ($mode === 'child')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <button type="button" @click="activeQueue = 'due'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">{{ $dueDateLabel }}</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--accent-blue);">{{ number_format($dueTodayCount) }}</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i> Queue
                    </span>
                </div>
            </button>

            <button type="button" @click="activeQueue = 'overdue'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Overdue / defaulters</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--danger);">{{ number_format($overdueCount) }}</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Priority
                    </span>
                </div>
            </button>

            <button type="button" @click="activeQueue = 'out_of_window'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Out of age window</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--amber);">{{ number_format($outOfWindowCount) }}</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i> Override
                    </span>
                </div>
            </button>

            <div class="text-left rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Infant coverage (0–11 mo)</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">
                        {{ is_null($infantCoveragePercent) ? '—' : $infantCoveragePercent.'%' }}
                    </p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                        {{ number_format($infantTotal) }} infants
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <button type="button" @click="activeTab = 'due'" class="text-left rounded-xl border p-4 lg:p-5 transition hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Due today</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--accent-blue);">{{ number_format($dueTodayCount) }}</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i> Queue
                    </span>
                </div>
            </button>

            <div class="text-left rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Overdue / defaulters</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--danger);">{{ number_format($overdueCount) }}</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Priority
                    </span>
                </div>
            </div>

            <div class="text-left rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="text-xs font-medium mb-0.5" style="color: var(--ink-muted);">Doses given</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-display font-semibold leading-none" style="color: var(--ink);">{{ number_format($totalGiven) }}</p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                        {{ number_format($patientsWithRecords) }} patients
                    </span>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-xl" x-data="patientSearch($data)">
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
                        <button type="button" class="block w-full text-left px-4 py-2.5" @click="parent.openPatient(patient.id, patient.text)">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">
                                <span x-text="patient.subtext"></span>
                                <span class="font-semibold" style="color: var(--primary);"> - View immunization history</span>
                            </div>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-cloak class="mt-2 text-xs" style="display: none; color: var(--ink-muted);">
            No patient found. <a href="{{ route('patients.create') }}" class="font-semibold" style="color: var(--primary);">Register a new patient</a>.
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
                    <button type="button" @click="activeQueue = 'out_of_window'" role="tab" :aria-selected="activeQueue === 'out_of_window' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeQueue === 'out_of_window' ? 'background: var(--amber-soft); color: var(--amber);' : 'color: var(--ink-muted);'">
                        Out of window
                        <span class="ml-1 opacity-70">{{ number_format($outOfWindowCount) }}</span>
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

            @foreach (['due', 'overdue', 'out_of_window', 'no_show'] as $queueKey)
                @php
                    $badge = $statusBadges[$queueKey];
                @endphp
                <div x-show="activeQueue === '{{ $queueKey }}'" x-cloak class="rounded-xl border overflow-hidden"
                     @if ($queueKey === 'due') style="background: var(--bg-surface-elevated); border-color: var(--border);" @else style="display: none; background: var(--bg-surface-elevated); border-color: var(--border);" @endif>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead style="background: var(--teal-soft);">
                                <tr>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Due date</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Status</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                @forelse (($queues[$queueKey] ?? collect()) as $entry)
                                    @php
                                        $queuePatient = $entry['patient'];
                                        $queueVaccine = $entry['vaccine'];
                                        $noShowAnchor = $queuePatient->immunizationRecords
                                            ->where('vaccine_id', $queueVaccine->id)
                                            ->last();
                                    @endphp
                                    <tr class="transition-colors hover:bg-black/[0.02]">
                                        <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                            <button type="button" class="text-left hover:underline font-medium" style="color: var(--primary);" @click="openPatient({{ $queuePatient->id }}, @js($queuePatient->last_name.', '.$queuePatient->first_name))">
                                                {{ $queuePatient->last_name }}, {{ $queuePatient->first_name }}
                                            </button>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                @include('immunizations.partials._age-chip', ['patient' => $queuePatient])
                                                <span class="text-xs" style="color: var(--ink-muted);">
                                                    <i class="fa-solid fa-location-dot mr-0.5" aria-hidden="true"></i>
                                                    {{ $queuePatient->household?->zone?->zone_number ?? 'No purok' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                            <div class="font-medium">{{ $queueVaccine->vaccine_name }}</div>
                                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">Dose {{ $entry['dose_number'] }}</div>
                                        </td>
                                        <td class="px-3 lg:px-4 py-3 whitespace-nowrap hidden sm:table-cell" style="color: var(--ink);">
                                            {{ $entry['due_date']?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 lg:px-4 py-3 hidden md:table-cell">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background: {{ $badge['bg'] }}; color: {{ $badge['fg'] }};">
                                                <i class="{{ $badge['icon'] }}" aria-hidden="true"></i>
                                                {{ $badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 lg:px-4 py-3 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                @if ($queueKey === 'no_show' && $noShowAnchor)
                                                    <form method="POST" action="{{ route('immunizations.no-show', $noShowAnchor->id) }}" @submit.prevent="confirmClearNoShow($event.target, @js($queuePatient->last_name.', '.$queuePatient->first_name))">
                                                        @csrf
                                                        <input type="hidden" name="no_show" value="0">
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                                                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Clear
                                                        </button>
                                                    </form>
                                                @elseif ($noShowAnchor)
                                                    <form method="POST" action="{{ route('immunizations.no-show', $noShowAnchor->id) }}" @submit.prevent="confirmNoShow($event.target, @js($queuePatient->last_name.', '.$queuePatient->first_name))">
                                                        @csrf
                                                        <input type="hidden" name="no_show" value="1">
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                                                            <i class="fa-solid fa-user-clock" aria-hidden="true"></i> No-show
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--primary);" @click="openPatient({{ $queuePatient->id }}, @js($queuePatient->last_name.', '.$queuePatient->first_name))">
                                                    <i class="fa-solid fa-syringe" aria-hidden="true"></i> Check-in / Record
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 lg:px-4 py-10 text-center">
                                            <x-empty-state
                                                :icon="match ($queueKey) {
                                                    'due' => 'fa-regular fa-calendar',
                                                    'overdue' => 'fa-solid fa-circle-check',
                                                    'out_of_window' => 'fa-solid fa-clock',
                                                    'no_show' => 'fa-solid fa-user-clock',
                                                }"
                                                :title="match ($queueKey) {
                                                    'due' => 'No patients '.($dueDateLabel === 'Due today' ? 'due today' : 'due on '.$date),
                                                    'overdue' => 'No overdue patients',
                                                    'out_of_window' => 'No out-of-window cases',
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

            <div x-show="activeQueue === 'recent'" x-cloak class="rounded-xl border overflow-hidden" style="display: none; background: var(--bg-surface-elevated); border-color: var(--border);">
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
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                            </span>
                                        @elseif ($nextDue->isSameDay($today))
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i> Due today
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">In progress</span>
                                        @endif
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $r->worker_name ?? '—' }}</td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                        <button type="button" class="text-sm font-medium hover:underline" style="color: var(--primary);" @click="openPatient({{ (int) $r->patient_id }}, @js($r->last_name.', '.$r->first_name))">
                                            Open
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 lg:px-4 py-12 text-center">
                                        <x-empty-state icon="fa-solid fa-clock-rotate-left" title="No recent records"
                                                       description="Immunization records will appear here once you start recording doses." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                        Due today
                    </button>
                    <button type="button" @click="activeTab = 'recent'" role="tab" :aria-selected="activeTab === 'recent' ? 'true' : 'false'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition" :style="activeTab === 'recent' ? 'background: var(--teal-soft); color: var(--primary);' : 'color: var(--ink-muted);'">
                        Recent
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'due'" class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
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
                                        <button type="button" class="text-left hover:underline font-medium" style="color: var(--primary);" @click="openPatient({{ (int) $p->patient_id }}, @js($p->last_name.', '.$p->first_name))">
                                            {{ $p->last_name }}, {{ $p->first_name }}
                                        </button>
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap" style="color: var(--ink);">
                                        {{ $dueDate?->format('M d, Y') ?? '—' }}
                                        @if ($dueDate && $dueDate->lt($today))
                                            <span class="ml-2 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                            </span>
                                        @elseif ($dueDate && $dueDate->isSameDay($today))
                                            <span class="ml-2 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i> Due today
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink-muted);">{{ $p->dose_number }}</td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $p->vaccine_name }}</td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                        <button type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--primary);" @click="openPatient({{ (int) $p->patient_id }}, @js($p->last_name.', '.$p->first_name))">
                                            <i class="fa-solid fa-syringe" aria-hidden="true"></i> Check-in / Record
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 lg:px-4 py-12 text-center">
                                        <x-empty-state icon="fa-regular fa-calendar" title="No patients due today"
                                                       description="Use the search box above to find a patient and record a vaccination dose." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="activeTab === 'recent'" x-cloak class="rounded-xl border overflow-hidden" style="display: none; background: var(--bg-surface-elevated); border-color: var(--border);">
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
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                            </span>
                                        @elseif ($nextDue->isSameDay($today))
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i> Due today
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">In progress</span>
                                        @endif
                                    </td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $r->worker_name ?? '—' }}</td>
                                    <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                        <button type="button" class="text-sm font-medium hover:underline" style="color: var(--primary);" @click="openPatient({{ (int) $r->patient_id }}, @js($r->last_name.', '.$r->first_name))">
                                            Open
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 lg:px-4 py-12 text-center">
                                        <x-empty-state icon="fa-solid fa-clock-rotate-left" title="No recent records"
                                                       description="Immunization records will appear here once you start recording doses." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <template x-teleport="body">
        <div x-show="patientModalOpen"
             x-cloak
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;"
             role="dialog"
             aria-modal="true"
             aria-labelledby="patientModalTitle"
             @keydown.escape.window="closePatientModal()">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePatientModal()"></div>
            <div class="relative flex flex-col w-full max-w-4xl h-[85vh] max-h-[90vh] overflow-hidden rounded-2xl border shadow-lg"
                 style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <div class="p-4 border-b flex items-start justify-between gap-3 shrink-0" style="border-color: var(--border); background: var(--bg-surface);">
                    <div>
                        <p class="text-xs font-medium" style="color: var(--ink-muted);">Patient</p>
                        <p id="patientModalTitle" class="font-display font-semibold text-lg leading-tight" style="color: var(--ink);" x-text="patientModalTitle || 'Immunizations'"></p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold transition" style="background: rgba(0,0,0,0.06); color: var(--ink);" @click="closePatientModal()">
                        Close
                    </button>
                </div>
                <div class="flex-1 overflow-hidden">
                    <iframe :src="patientModalUrl" class="w-full h-full" style="background: var(--bg-page);" title="Patient immunizations"></iframe>
                </div>
            </div>
        </div>
    </template>

    @include('immunizations.partials._enroll-infant-modal', ['zones' => $zones])
</div>

<script>
    function immunizationIndex() {
        return {
            patientRouteTemplate: @json(route('immunizations.patient', ['id' => '__PATIENT_ID__'])),
            activeQueue: 'due',
            activeTab: 'due',
            patientModalOpen: false,
            patientModalUrl: '',
            patientModalTitle: '',
            init() {
                this.$watch('patientModalOpen', (open) => {
                    document.body.classList.toggle('overflow-hidden', open);
                });
            },
            patientUrl(patientId) {
                return this.patientRouteTemplate.replace('__PATIENT_ID__', patientId);
            },
            openPatient(patientId, title) {
                this.patientModalUrl = this.patientUrl(patientId);
                this.patientModalTitle = title ?? 'Immunizations';
                this.patientModalOpen = true;
            },
            closePatientModal() {
                this.patientModalOpen = false;
                this.patientModalUrl = '';
            },
            confirmNoShow(form, patientLabel) {
                Swal.fire({
                    title: 'Mark as no-show?',
                    html: `<p class="text-sm">${patientLabel} missed their scheduled dose. The next dose slot will be reserved and the queue updated.</p>`,
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
                    html: `<p class="text-sm">${patientLabel} showed up after all. The reserved slot will be released.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, clear',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0d4a3c',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            },
        };
    }

    function patientSearch(parent) {
        return {
            parent,
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
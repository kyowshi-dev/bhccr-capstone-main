@extends('layouts.app')

@section('title', 'Prenatal')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Prenatal</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Active pregnancies, EDC, and next visit schedule.</p>
        </div>
    </div>

    <x-maternal-nav-tabs />

    <form method="GET" action="{{ route('maternal.prenatal.index') }}" class="flex flex-wrap items-center gap-2">
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
        <div>
            <label for="search" class="sr-only">Search patient</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Search patient name…"
                   class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
        </div>
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]"
                style="border-color: var(--border); color: var(--ink-muted);">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Filter
        </button>
        @if ($zoneId !== null || $search !== null)
            <a href="{{ route('maternal.prenatal.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="color: var(--ink-muted);">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
            </a>
        @endif
    </form>

    @if ($pregnancies->isEmpty())
        <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
            <i class="fa-solid fa-baby-carriage text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold" style="color: var(--ink);">No active pregnancies</p>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Open a patient profile and register a pregnancy under Maternal Care.</p>
            <a href="{{ route('patients.index') }}" class="inline-flex items-center justify-center mt-4 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition hover:shadow-md"
               style="background: var(--primary);">
                <i class="fa-solid fa-user-injured mr-1.5" aria-hidden="true"></i> Browse patients
            </a>
        </div>
    @else
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="border-b" style="background: var(--teal-soft);">
                        <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Patient</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap hidden md:table-cell">Zone</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Risk</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">G/P</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">EDC</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">AOG</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Visits</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Next visit</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--border);">
                        @foreach ($pregnancies as $pregnancy)
                            @php
                                $nextVisit = $pregnancy->latest_next_visit_date;
                                $edcDate = $pregnancy->edc;
                                $dueSoon = $edcDate !== null && \Carbon\Carbon::parse($edcDate)->lte(\Carbon\Carbon::today()->addDays(30));
                            @endphp
                            <tr class="hover:bg-black/[0.03] transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('maternal.prenatal.patient', $pregnancy->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                        {{ fullName($pregnancy->patient->last_name, $pregnancy->patient->first_name, $pregnancy->patient->middle_name, $pregnancy->patient->suffix) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $pregnancy->patient->household?->zone?->zone_number ?? $pregnancy->patient->household?->zone_id ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if (! empty($pregnancy->risk_flags))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High risk
                                        </span>
                                    @else
                                        <span style="color: var(--ink-subtle);">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">G{{ $pregnancy->gravidity }} P{{ $pregnancy->parity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $edcDate?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">
                                    @if ($pregnancy->aog_weeks !== null)
                                        {{ $pregnancy->aog_weeks }} wk{{ $pregnancy->aog_weeks == 1 ? '' : 's' }}
                                    @elseif ($edcDate !== null)
                                        {{ max(0, \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($edcDate)->subDays(280))) }} wks
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ $pregnancy->visits_count }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($nextVisit === null)
                                        <span style="color: var(--ink-subtle);">-</span>
                                    @elseif (\Carbon\Carbon::parse($nextVisit)->lt(\Carbon\Carbon::today()))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue {{ \Carbon\Carbon::parse($nextVisit)->format('M d') }}
                                        </span>
                                    @else
                                        <span style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($nextVisit)->format('M d, Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('maternal.prenatal.patient', $pregnancy->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                       style="background: var(--teal-soft); color: var(--primary);">
                                        Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

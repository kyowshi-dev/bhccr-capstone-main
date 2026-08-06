@extends(request()->query('bare') ? 'layouts.bare' : 'layouts.app')

@section('title', 'Immunization — ' . fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

@section('content')
@php
    $isBare = request()->query('bare');
    $recordsByVaccine = $records->groupBy('vaccine_id');
    $alertOverdue = collect($schedule)->filter(fn ($i) => ($statuses[$i->vaccine->id] ?? null) === 'overdue')->count();
    $alertOutOfWindow = collect($schedule)->filter(fn ($i) => ($statuses[$i->vaccine->id] ?? null) === 'out_of_window')->count();
    $alertNoShow = collect($schedule)->filter(fn ($i) => ($statuses[$i->vaccine->id] ?? null) === 'no_show')->count();
    $purok = $patient->household?->zone?->zone_number ?? 'No purok';
    [$y, $m, $d] = $patient->ageDetail !== null ? array_pad($patient->ageDetail, 3, 0) : [null, null, null];
    $ageText = $y !== null ? "{$y}y {$m}m {$d}d" : '—';
@endphp

<div class="space-y-5 lg:space-y-6" x-data="{}">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            @if (! $isBare)
                <a href="{{ route('patients.show', $patient->id) }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to patient</a>
            @else
                <p class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Patient immunization record</p>
            @endif
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization — {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</h1>
                @include('immunizations.partials._age-chip', ['patient' => $patient])
            </div>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
                {{ $patient->sex }} · DOB {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M j, Y') }}
                <span class="inline-flex items-center gap-1 ml-2">
                    <i class="fa-solid fa-location-dot text-xs" aria-hidden="true"></i>{{ $purok }}
                </span>
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('success') }}
        </div>
    @endif

    @if ($alertOverdue > 0 || $alertNoShow > 0)
        <div class="rounded-xl border px-4 py-3 text-sm" style="background: var(--danger-soft); border-color: var(--danger); color: var(--danger);">
            <i class="fa-solid fa-circle-exclamation mr-1.5" aria-hidden="true"></i>
            @if ($alertOverdue > 0){{ $alertOverdue }} vaccine{{ $alertOverdue === 1 ? '' : 's' }} overdue.@endif
            @if ($alertNoShow > 0)@if ($alertOverdue > 0) @endif{{ $alertNoShow }} marked no-show — follow up.@endif
        </div>
    @endif
    @if ($alertOutOfWindow > 0)
        <div class="rounded-xl border px-4 py-3 text-sm" style="background: var(--amber-soft); border-color: var(--amber); color: var(--amber);">
            <i class="fa-solid fa-clock mr-1.5" aria-hidden="true"></i>
            {{ $alertOutOfWindow }} vaccine{{ $alertOutOfWindow === 1 ? '' : 's' }} outside the recommended age window — an override reason will be required.
        </div>
    @endif

    <div>
        <h2 class="font-display font-semibold text-lg mb-1" style="color: var(--ink);">Immunization schedule</h2>
        <p class="text-sm mb-3" style="color: var(--ink-muted);">Dose numbers advance automatically from prior records.</p>
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium hidden md:table-cell" style="color: var(--ink-muted);">Schedule</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Status</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium" style="color: var(--ink-muted);">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($schedule as $item)
                            @php
                                $vaccineId = $item->vaccine->id;
                                $status = $statuses[$vaccineId] ?? 'waiting';
                                $elig = $eligibility[$vaccineId] ?? ['state' => 'waiting', 'earliest_date' => null, 'requires_override' => false];
                                $givenCount = $recordsByVaccine->get($vaccineId, collect())->where('no_show', false)->count();
                                $nextDose = $givenCount + 1;
                                $nextSchedule = $schedulesByVaccine[$vaccineId] ?? collect();
                                $nextScheduleRow = $nextSchedule->where('dose_number', $nextDose)->first();
                                $requiresTemp = (bool) ($nextScheduleRow->requires_temp ?? false);
                                $noShowEvent = $noShowEvents[$vaccineId] ?? null;
                                $earliestDate = $elig['earliest_date'] ?? null;
                            @endphp
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                    <div class="font-medium">{{ $item->vaccine->vaccine_name }}</div>
                                    @if ($item->vaccine->vaccine_code)
                                        <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $item->vaccine->vaccine_code }}</div>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-3 hidden md:table-cell text-xs" style="color: var(--ink-muted);">
                                    {{ $item->vaccine->description ?? 'Per DOH schedule' }}
                                </td>
                                <td class="px-3 lg:px-4 py-3">
                                    @if ($status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium" style="color: var(--ink-muted);">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                                        </span>
                                    @elseif ($status === 'no_show')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-user-clock" aria-hidden="true"></i> No-show
                                        </span>
                                    @elseif ($status === 'out_of_window')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                            <i class="fa-solid fa-clock" aria-hidden="true"></i> Out of window
                                        </span>
                                    @elseif ($status === 'overdue')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                        </span>
                                    @else
                                        <span class="text-xs font-medium" style="color: var(--ink-subtle);">Waiting</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-3 text-right whitespace-nowrap">
                                    @if ($status === 'completed')
                                        <span class="text-xs font-medium" style="color: var(--ink-muted);">Series complete</span>
                                    @elseif ($elig['state'] === 'too_early')
                                        <span class="text-xs font-medium" style="color: var(--ink-muted);">
                                            Earliest {{ \Carbon\Carbon::parse($earliestDate)->format('M d, Y') }}
                                        </span>
                                    @else
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($status === 'no_show' && $noShowEvent)
                                                <form method="POST" action="{{ route('immunizations.no-show') }}" @submit.prevent="confirmClearNoShow($event.target)">
                                                    @csrf
                                                    <input type="hidden" name="no_show" value="0">
                                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                    <input type="hidden" name="vaccine_id" value="{{ $vaccineId }}">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                                                        Clear no-show
                                                    </button>
                                                </form>
                                            @endif
<button type="button"
                                            @click="$dispatch('open-administer', {
                                                vaccineId: {{ $vaccineId }},
                                                vaccineName: @js($item->vaccine->vaccine_name),
                                                doseNumber: {{ $nextDose }},
                                                requiresTemp: {{ $requiresTemp ? 'true' : 'false' }},
                                                outOfWindow: {{ ($elig['state'] ?? '') === 'out_of_window' ? 'true' : 'false' }}
                                            })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:shadow-md"
                                            style="background: var(--primary);">
                                        <i class="fa-solid fa-syringe" aria-hidden="true"></i> Administer
                                    </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm" style="color: var(--ink-muted);">No vaccines in schedule for this age group.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">History</h2>
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Date</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Dose</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Temp (°C)</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Given by</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($records as $r)
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap" style="color: var(--ink);">{{ \Carbon\Carbon::parse($r->date_given)->format('M d, Y') }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->vaccine_name }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->dose_number }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink);">{{ $r->temp_recorded ?? '—' }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $r->administered_by_name ?? '—' }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                    @if ($r->no_show)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-user-clock" aria-hidden="true"></i> No-show
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 lg:px-4 py-6 text-center text-sm" style="color: var(--ink-muted);">No doses logged yet. Use Administer on the schedule above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('immunizations.partials._administer-modal')
</div>

<script>
    function confirmClearNoShow(form) {
        Swal.fire({
            title: 'Clear no-show?',
            text: 'The missed appointment stays in the patient history; the patient returns to the queue.',
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
    }
</script>
@endsection
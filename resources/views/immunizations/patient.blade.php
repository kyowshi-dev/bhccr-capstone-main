@extends(request()->query('bare') ? 'layouts.bare' : 'layouts.app')

@section('title', 'Immunization - ' . fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

@section('content')
@php
    $isBare = request()->query('bare');
    $purok = $patient->household?->zone?->zone_number ?? 'No purok';
    [$y, $m, $d] = $patient->ageDetail !== null ? array_pad($patient->ageDetail, 3, 0) : [null, null, null];
    $ageText = $y !== null ? "{$y}y {$m}m {$d}d" : '-';
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
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization - {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</h1>
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

    @if (! $isImmunizationEnrolled)
        <div class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-blue-soft); border-color: var(--accent-blue); color: var(--accent-blue);">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <i class="fa-solid fa-info-circle mr-1.5" aria-hidden="true"></i>
                    This patient is not enrolled in the immunization program. Enroll to track their vaccination schedule.
                </div>
                <form method="POST" action="{{ route('immunizations.enroll', $patient->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-1.5 rounded-lg text-white text-xs font-semibold transition hover:shadow-md" style="background: var(--accent-blue);">
                        <i class="fa-solid fa-syringe mr-1.5" aria-hidden="true"></i> Enroll in immunization
                    </button>
                </form>
            </div>
        </div>
    @endif

    @include('immunizations.partials._schedule-table', [
        'patient' => $patient,
        'schedule' => $schedule,
        'statuses' => $statuses,
        'eligibility' => $eligibility,
        'schedulesByVaccine' => $schedulesByVaccine,
        'noShowEvents' => $noShowEvents,
        'records' => $records,
    ])

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
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink);">{{ $r->temp_recorded ?? '-' }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $r->administered_by_name ?? '-' }}</td>
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

    function confirmMarkDone(form) {
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
    }
</script>
@endsection
@php
    $purok = $patient->household?->zone?->zone_number ?? 'No purok';
@endphp

<div x-data="{}">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b px-4 py-4 lg:px-5" style="background: var(--bg-surface); border-color: var(--border);">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="font-display font-semibold" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</p>
                @include('immunizations.partials._age-chip', ['patient' => $patient])
            </div>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">
                {{ $patient->sex }} · DOB {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M j, Y') }}
                <span class="inline-flex items-center gap-1 ml-2">
                    <i class="fa-solid fa-location-dot text-xs" aria-hidden="true"></i>{{ $purok }}
                </span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('immunizations.print-card', $patient->id) }}" target="_blank" rel="noopener" title="Print record" aria-label="Print record for {{ fullName($patient->last_name, $patient->first_name) }}" class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
            </a>
            <a href="{{ route('immunizations.patient', $patient->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Full record
            </a>
            <button type="button" @click="$dispatch('checkin-close')" aria-label="Close check-in" class="rounded-lg p-1.5 transition-colors hover:bg-black/5" style="color: var(--ink-muted);">
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="p-4 lg:p-5">
        @include('immunizations.partials._schedule-table', [
            'patient' => $patient,
            'schedule' => $schedule,
            'statuses' => $statuses,
            'eligibility' => $eligibility,
            'schedulesByVaccine' => $schedulesByVaccine,
            'noShowEvents' => $noShowEvents,
            'records' => $records,
        ])
    </div>

    @include('immunizations.partials._administer-modal', ['patient' => $patient, 'vaccines' => $vaccines])
</div>

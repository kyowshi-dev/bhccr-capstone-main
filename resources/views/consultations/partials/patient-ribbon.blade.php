@php
    $tempAlert = (float) ($latestVitals?->temperature_c ?? 0) > 37.5;
    $bpAlert = ((int) ($latestVitals?->bp_systolic ?? 0) > 140) || ((int) ($latestVitals?->bp_diastolic ?? 0) > 90);
    $vitalsCount = $allVitals?->count() ?? 0;
@endphp

<section class="sticky top-0 z-40 rounded-2xl border px-4 py-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-stretch">
        <div class="rounded-2xl border bg-white p-4" style="border-color: var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--ink)]">Patient Info</p>
            <p class="font-display text-lg font-semibold text-[var(--ink)] mt-2">
                {{ $patient->last_name }}, {{ ucwords($patient->first_name) }}
            </p>
            <p class="text-xs text-[var(--ink-muted)] mt-1">
                {{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} · {{ $patient->sex }} ·
                PhilHealth {{ ($patient->is_philhealth_member ?? 'n') === 'y' ? 'Member' : 'Non-member' }}
            </p>
        </div>

        <div class="rounded-2xl border bg-white p-4" style="border-color: var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--ink)]">Chief Complaint</p>
            <p class="text-sm italic text-[var(--ink-muted)] mt-2 leading-6">
                {{ ucwords($consultation->complaint_text ?? 'No complaint recorded') }}
            </p>
        </div>

        <div class="rounded-2xl border bg-white p-4" style="border-color: var(--border);">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--ink)]">Vitals</p>
                <button type="button" @click="$dispatch('open-vitals-modal')" class="inline-flex items-center gap-1 rounded-full bg-teal-soft px-2 py-1 text-[11px] font-semibold text-[var(--primary)] hover:bg-black/5" title="Re-Take Vitals" aria-label="Re-Take Vitals">
                    <i class="fa-solid fa-pencil text-[10px]"></i>
                    <span>Re-take</span>
                </button>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <p class="text-sm font-semibold" style="color: {{ $bpAlert ? 'var(--danger)' : 'var(--ink)' }};">
                        BP: {{ $latestVitals?->bp_systolic ?? '—' }}/{{ $latestVitals?->bp_diastolic ?? '—' }}
                    </p>
                    <p class="text-sm font-semibold mt-1" style="color: {{ $tempAlert ? 'var(--danger)' : 'var(--ink)' }};">
                        Temp: {{ $latestVitals?->temperature_c ?? '—' }}°C
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[var(--ink)]">
                        Weight: {{ $latestVitals?->weight_kg ?? '—' }} kg
                    </p>
                    <p class="text-sm font-semibold mt-1 text-[var(--ink)]">
                        Height: {{ $latestVitals?->height_cm ?? '—' }} cm
                    </p>
                </div>
            </div>
            <button type="button" @click="$dispatch('open-vitals-modal')" class="mt-3 rounded-full px-2.5 py-1 text-[11px] font-semibold text-white hover:opacity-90" style="background: var(--primary);">
                {{ $vitalsCount }} version{{ $vitalsCount !== 1 ? 's' : '' }}
            </button>
        </div>
    </div>
</section>

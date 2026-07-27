@php
    $tempAlert = (float) ($latestVitals?->temperature_c ?? 0) > 37.5;
    $bpAlert = ((int) ($latestVitals?->bp_systolic ?? 0) > 140) || ((int) ($latestVitals?->bp_diastolic ?? 0) > 90);
    $vitalsCount = $allVitals?->count() ?? 0;
@endphp

<section class="sticky top-0 z-40 rounded-2xl border border-gray-200 bg-slate-50 px-4 py-4 shadow-sm shadow-slate-200/80" style="border-color: var(--border);">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-stretch">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Patient Info</p>
            <p class="font-display text-lg font-semibold text-slate-900 mt-2">
                {{ $patient->last_name }}, {{ ucwords($patient->first_name) }}
            </p>
            <p class="text-xs text-slate-600 mt-1">
                {{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} · {{ $patient->sex }} ·
                PhilHealth {{ ($patient->is_philhealth_member ?? 'n') === 'y' ? 'Member' : 'Non-member' }}
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Chief Complaint</p>
            <p class="text-sm italic text-slate-600 mt-2 leading-6">
                {{ ucwords($consultation->complaint_text ?? 'No complaint recorded') }}
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Vitals</p>
                <button type="button" @click="$dispatch('open-vitals-modal')" class="inline-flex items-center gap-1 rounded-full bg-emerald-900/10 px-2 py-1 text-[11px] font-semibold text-emerald-900 hover:bg-emerald-900/15" title="Re-Take Vitals" aria-label="Re-Take Vitals">
                    <i class="fa-solid fa-pencil text-[10px]"></i>
                    <span>Re-take</span>
                </button>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <p class="text-sm font-semibold {{ $bpAlert ? 'text-red-600' : 'text-slate-900' }}">
                        BP: {{ $latestVitals?->bp_systolic ?? '—' }}/{{ $latestVitals?->bp_diastolic ?? '—' }}
                    </p>
                    <p class="text-sm font-semibold mt-1 {{ $tempAlert ? 'text-red-600' : 'text-slate-900' }}">
                        Temp: {{ $latestVitals?->temperature_c ?? '—' }}°C
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">
                        Weight: {{ $latestVitals?->weight_kg ?? '—' }} kg
                    </p>
                    <p class="text-sm font-semibold mt-1 text-slate-900">
                        Height: {{ $latestVitals?->height_cm ?? '—' }} cm
                    </p>
                </div>
            </div>
            <button type="button" @click="$dispatch('open-vitals-modal')" class="mt-3 rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:opacity-90">
                {{ $vitalsCount }} version{{ $vitalsCount !== 1 ? 's' : '' }}
            </button>
        </div>
    </div>
</section>

{{--
    Prenatal Record — print sheet
    Expects: $pregnancy (Pregnancy, loaded with patient.household.zone, visits)
--}}
@php
    $patient = $pregnancy->patient;
    $patientName = fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix);
    $aog = $pregnancy->aog_weeks
        ?? ($pregnancy->edc !== null ? max(0, \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($pregnancy->edc)->subDays(280))) : null);
@endphp

<section class="iclinic-form" aria-label="Prenatal Record">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'PRENATAL RECORD',
        'serialDigits' => 4,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="8" class="section-header">I. Patient Information</td>
        </tr>
        <tr>
            <td colspan="4">
                <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                <p class="field-value text-bold">{{ $patient->last_name }}</p>
            </td>
            <td colspan="3">
                <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                <p class="field-value text-bold">{{ $patient->first_name }}</p>
            </td>
            <td>
                <p class="field-label">Suffix</p>
                <p class="field-value">{{ $patient->suffix ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="field-label">Age</p>
                <p class="field-value">{{ $patient->age }}</p>
            </td>
            <td>
                <p class="field-label">Sex</p>
                <p class="field-value">{{ $patient->sex }}</p>
            </td>
            <td colspan="2">
                <p class="field-label">Civil Status</p>
                <p class="field-value">{{ $patient->civil_status }}</p>
            </td>
            <td colspan="2">
                <p class="field-label">Zone / Purok</p>
                <p class="field-value">Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '—' }}</p>
            </td>
            <td colspan="2">
                <p class="field-label">Spouse / Partner</p>
                <p class="field-value">{{ $patient->spouse_name ?? '' }}</p>
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td colspan="8" class="section-header">II. Pregnancy</td>
        </tr>
        <tr class="label-cell-sm text-center">
            <td>Gravida</td>
            <td>Para</td>
            <td>Term</td>
            <td>Preterm</td>
            <td>Live Births</td>
            <td>Abortions</td>
            <td colspan="2">Status</td>
        </tr>
        <tr class="text-center">
            <td class="text-bold">{{ $pregnancy->gravidity }}</td>
            <td class="text-bold">{{ $pregnancy->parity }}</td>
            <td>{{ $pregnancy->term }}</td>
            <td>{{ $pregnancy->preterm }}</td>
            <td>{{ $pregnancy->livebirth }}</td>
            <td>{{ $pregnancy->abortion }}</td>
            <td colspan="2" class="text-bold text-upper">{{ $pregnancy->status }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">LMP</td>
            <td colspan="2"><p class="field-value text-bold">{{ $pregnancy->lmp?->format('M d, Y') ?? '' }}</p></td>
            <td colspan="2" class="label-cell">EDC (LMP + 280 days)</td>
            <td colspan="2"><p class="field-value text-bold">{{ $pregnancy->edc?->format('M d, Y') ?? '' }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">AOG</td>
            <td colspan="2"><p class="field-value text-bold">{{ $aog !== null ? $aog.' weeks' : '' }}</p></td>
            <td colspan="2" class="label-cell">Syphilis (RPR)</td>
            <td colspan="2"><p class="field-value text-bold">{{ ucfirst($pregnancy->syphilis_result) }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Penicillin</td>
            <td colspan="2"><p class="field-value">{{ ucfirst($pregnancy->penicillin) }}</p></td>
            <td colspan="2" class="label-cell">TT Dose Date</td>
            <td colspan="2"><p class="field-value">{{ $pregnancy->tt_date?->format('M d, Y') ?? '' }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Iron Taken</td>
            <td colspan="2"><p class="field-value">{{ $pregnancy->iron_taken ? 'Yes' : 'No' }}</p></td>
            <td colspan="2" class="label-cell">Others</td>
            <td colspan="2"><p class="field-value">{{ $pregnancy->others ?? '' }}</p></td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td colspan="8" class="section-header">III. Prenatal Visits</td>
        </tr>
        <tr class="label-cell-sm text-center">
            <td style="width:16%;">Visit Date</td>
            <td style="width:18%;">Fundic Height (cm)</td>
            <td style="width:18%;">Fetal Heart Tone (bpm)</td>
            <td style="width:18%;">Next Visit</td>
            <td style="width:30%;">Remarks</td>
        </tr>
        @forelse ($pregnancy->visits->sortByDesc('visit_date') as $visit)
            <tr class="text-center">
                <td>{{ $visit->visit_date->format('M d, Y') }}</td>
                <td>{{ $visit->fundic_height_cm ?? '' }}</td>
                <td>{{ $visit->fetal_heart_tone_bpm ?? '' }}</td>
                <td>{{ $visit->next_visit_date?->format('M d, Y') ?? '' }}</td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="5">&nbsp;</td>
            </tr>
        @endforelse
        <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <div class="form-footer form-footer-flex">
        <span>Barangay Health Center — Sta. Ana</span>
        <span>Registered: {{ $pregnancy->created_at->format('M d, Y g:i A') }}</span>
    </div>
</section>

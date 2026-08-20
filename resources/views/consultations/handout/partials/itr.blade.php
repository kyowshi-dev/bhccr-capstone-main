{{--
    Individual Treatment Record (ITR) Section - iClinicSys FORM 2
--}}
@php
    $consultDate = $consultationAt ?? \Carbon\Carbon::parse($consultation->updated_at ?? $consultation->created_at);
    $isAm = $consultDate->format('A') === 'AM';
    $mode = $consultation->mode_of_transaction ?? '';
    $natureOfVisit = $consultation->nature_of_visit ?? '';
    $isReferral = $mode === 'Referral' || (bool) ($consultation->refer_to_higher_facility ?? false);
    $bp = ($vitals->bp_systolic ?? null) && ($vitals->bp_diastolic ?? null)
        ? $vitals->bp_systolic.'/'.$vitals->bp_diastolic
        : '';
    $temperature = $vitals->temperature_c ?? null;
    $height = $vitals->height_cm ?? null;
    $weight = $vitals->weight_kg ?? null;
    $provider = $attendingProvider ?? '';

    $diagnosisText = $diagnoses->map(function ($dx) {
        $line = $dx->diagnosis_name;
        if ($dx->diagnosis_code) {
            $line .= ' ('.$dx->diagnosis_code.')';
        }
        if ($dx->remarks) {
            $line .= ' - '.$dx->remarks;
        }

        return $line;
    })->implode("\n");

    $medicationText = $prescriptions->map(function ($rx) {
        $parts = array_filter([
            $rx->medicine_name,
            $rx->dosage,
            $rx->route,
            $rx->frequency,
            $rx->duration ? 'x '.$rx->duration : null,
            $rx->quantity ? 'Qty '.$rx->quantity : null,
            $rx->instructions,
        ]);

        return implode(' · ', $parts);
    })->implode("\n");


    $consultationTypesLeft = [
        'General', 'Prenatal', 'Dental Care', 'Child Care', 'Child Nutrition', 'Injury', 'Adult Immunization',
    ];
    $consultationTypesRight = [
        'Family Planning', 'Postpartum', 'Tuberculosis', 'Child Immunization', 'Sick Children', 'Firecracker Injury',
    ];

    $outwardReferral = $outwardReferral ?? null;
    $isReferral = $mode === 'Referral' || ($outwardReferral !== null) || (bool) ($consultation->refer_to_higher_facility ?? false);
    $referredFrom = $outwardReferral?->referred_from ?? $consultation->referred_from ?? '';
    $referredTo = $outwardReferral?->destination_facility ?? $consultation->referred_to ?? 'Higher facility';
    $referralReasonText = $outwardReferral?->specific_details ?? ($consultation->referral_reason ?? '');
@endphp

<section class="iclinic-form" aria-label="Individual Treatment Record">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'INDIVIDUAL TREATMENT RECORD',
        'serialDigits' => 5,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="12" class="section-header">I. Patient Information (Impormasyon ng Pasyente)</td>
        </tr>
        <tr>
            <td colspan="6">
                <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                <p class="field-value text-bold">{{ $patient->last_name ?? '' }}</p>
            </td>
            <td colspan="4">
                <p class="field-label">Suffix <span class="field-help">(e.g. Jr., Sr., II, III)</span></p>
                <p class="field-value">{{ $patient->suffix ?? '' }}</p>
            </td>
            <td colspan="2">
                <p class="field-label">Age <span class="field-help">(Edad)</span></p>
                <p class="field-value text-bold">{{ $age ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                <p class="field-value text-bold">{{ $patient->first_name ?? '' }}</p>
            </td>
            <td colspan="7">
                <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                <p class="field-value">{{ $patient->residential_address ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="12">
                <p class="field-label">Middle Name <span class="field-help">(Gitnang Pangalan)</span></p>
                <p class="field-value">{{ $patient->middle_name ?? '' }}</p>
            </td>
        </tr>

        <tr>
            <td colspan="12" class="section-header" style="border-top:0;">II. For CHU / RHU Personnel Only (Para sa Kinatawan ng CHU / RHU Lamang)</td>
        </tr>

        {{-- Block 1: Mode / Vitals (left) + Referral (right) --}}
        <tr>
            <td colspan="12" style="padding:0; border-bottom:3px solid #000;">
                <table class="form-table" style="border:0;">
                    {{-- Row: Mode of Transaction | Referral header + FROM/TO --}}
                    <tr>
                        <td class="label-cell text-center" rowspan="3" style="width:14%; border-top:0; border-left:0; vertical-align:middle !important;">Mode of<br>Transaction</td>
                        <td style="width:36%; border-top:0; padding:1px 3px;">
                            @include('consultations.handout.partials._mark', ['checked' => $mode === 'Walk-in', 'label' => 'Walk-in', 'inline' => false])
                        </td>
                        <td colspan="2" class="label-cell-sm text-center" style="width:50%; border-top:0; border-right:0;">For REFERRAL Transaction only.</td>
                    </tr>
                    <tr>
                        <td style="padding:1px 3px;">
                            @include('consultations.handout.partials._mark', ['checked' => $mode === 'Visited', 'label' => 'Visited', 'inline' => false])
                        </td>
                        <td class="label-cell text-center" style="width:16%; vertical-align:middle !important; font-size:7pt;">REFERRED FROM</td>
                        <td style="border-right:0;">{{ $isReferral ? $referredFrom : '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:1px 3px;">
                            @include('consultations.handout.partials._mark', ['checked' => $mode === 'Referral', 'label' => 'Referral', 'inline' => false])
                        </td>
                        <td class="label-cell text-center" style="vertical-align:middle !important; font-size:7pt;">REFERRED TO</td>
                        <td style="border-right:0;">{{ $isReferral ? $referredTo : '' }}</td>
                    </tr>

                    {{-- Vitals + Reason(s) for Referral --}}
                    <tr>
                        <td class="label-cell" style="border-left:0;">Date of Consultation</td>
                        <td>
                            <span class="field-value">{{ $consultDate->format('m/d/Y') }}</span>
                            <span class="field-help">(mm/dd/yyyy)</span>
                        </td>
                        <td class="label-cell text-center" rowspan="4" style="vertical-align:middle !important; font-size:7pt;">Reason(s) for<br>Referral</td>
                        <td rowspan="4" style="border-right:0; vertical-align:top; min-height:72px;">
                            <p class="field-value-sm whitespace-pre">{{ $isReferral ? $referralReasonText : '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell" style="border-left:0;">Consultation Time</td>
                        <td>
                            <span class="field-value">{{ $consultDate->format('g:i') }}</span>
                            <span class="am-pm-box">
                                <span @class(['active' => $isAm])>AM</span>
                                <span class="sep">/</span>
                                <span @class(['active' => ! $isAm])>PM</span>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-left:0; padding:0;">
                            <table class="form-table" style="border:0; width:100%;">
                                <tr>
                                    <td class="label-cell" style="width:55%; border:0; border-right:1px solid #000; padding:1px 3px;">Blood Pressure</td>
                                    <td style="border:0; padding:1px 3px;">{{ $bp }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding:0;">
                            <table class="form-table" style="border:0; width:100%;">
                                <tr>
                                    <td class="label-cell" style="width:45%; border:0; border-right:1px solid #000;">Temperature</td>
                                    <td style="border:0; padding:1px 3px;">{{ $temperature !== null ? "{$temperature}°C" : '' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-left:0; padding:0;">
                            <table class="form-table" style="border:0; width:100%;">
                                <tr>
                                    <td class="label-cell" style="width:55%; border:0; border-right:1px solid #000; padding:1px 3px;">Height (cm)</td>
                                    <td style="border:0; padding:1px 3px;">{{ $height }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding:0;">
                            <table class="form-table" style="border:0; width:100%;">
                                <tr>
                                    <td class="label-cell" style="width:45%; border:0; border-right:1px solid #000;">Weight (kg)</td>
                                    <td style="border:0; padding:1px 3px;">{{ $weight }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell" style="border-left:0; border-bottom:0;">Name of Attending Provider</td>
                        <td class="field-value text-bold" style="border-bottom:0;">{{ $provider }}</td>
                        <td class="label-cell text-center" style="border-bottom:0; vertical-align:middle !important; font-size:7pt;">Referred by</td>
                        <td class="field-value-sm" style="border-right:0; border-bottom:0;">{{ $isReferral ? $provider : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Block 2: Nature of Visit + Type (left) | Chief Complaints (right) --}}
        <tr>
            <td colspan="12" style="padding:0; border-bottom:3px solid #000;">
                <table class="form-table" style="border:0;">
                    <tr>
                        <td class="label-cell text-center" style="width:14%; border-top:0; border-left:0; vertical-align:middle !important;">Nature of Visit</td>
                        <td style="width:36%; border-top:0; padding:2px 3px;">
                            <div class="marks-stack">
                                @include('consultations.handout.partials._mark', ['checked' => in_array($natureOfVisit, ['Checkup', 'New Consultation/Case'], true), 'label' => 'New Consultation/Case', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $natureOfVisit === 'New Admission', 'label' => 'New Admission', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => in_array($natureOfVisit, ['Follow-up', 'Follow-up Visit', 'Follow-up visit'], true), 'label' => 'Follow-up visit', 'inline' => false])
                            </div>
                        </td>
                        <td class="label-cell text-center" rowspan="2" style="width:10%; border-top:0; vertical-align:middle !important; font-size:7pt;">Chief<br>Complaints:</td>
                        <td rowspan="2" class="field-value-sm whitespace-pre text-center" style="width:40%; border-top:0; border-right:0; vertical-align:middle; min-height:120px;">{{ $consultation->complaint_text ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell text-center" style="border-left:0; border-bottom:0; vertical-align:middle !important; font-size:7pt;">Type of Consultation /<br>Purpose of visit</td>
                        <td style="border-bottom:0; padding:2px 3px; vertical-align:top;">
                            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                <tr>
                                    <td style="width:50%; vertical-align:top; border:0; padding:0 2px 0 0;">
                                        <div class="marks-stack">
                                            @foreach ($consultationTypesLeft as $type)
                                                @include('consultations.handout.partials._mark', ['checked' => false, 'label' => $type, 'inline' => false])
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="width:50%; vertical-align:top; border:0; padding:0;">
                                        <div class="marks-stack">
                                            @foreach ($consultationTypesRight as $type)
                                                @include('consultations.handout.partials._mark', ['checked' => false, 'label' => $type, 'inline' => false])
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Block 3: Diagnosis (full width) --}}
        <tr>
            <td colspan="12" style="padding:0; min-height:56px; border-bottom:3px solid #000;">
                <table class="form-table" style="border:0; height:100%;">
                    <tr>
                        <td class="label-cell text-center" style="width:14%; border:0; border-right:1px solid #000; vertical-align:middle !important; font-size:7pt;">Diagnosis:</td>
                        <td class="field-value-sm whitespace-pre" style="border:0; vertical-align:top; min-height:56px;">{{ $diagnosisText ?: '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="8" style="padding:0; min-height:64px;">
                <table class="form-table nested-table" style="border:0; height:100%;">
                    <tr>
                        <td class="label-cell" style="width:14%; border-top:0; border-left:0; border-bottom:0; font-size:7pt;">Medication /<br>Treatment:</td>
                        <td class="field-value-sm whitespace-pre" style="border-top:0; border-right:0; border-bottom:0;">{{ $medicationText ?: ($consultation->notes ?? '-') }}</td>
                    </tr>
                </table>
            </td>
            <td colspan="4" style="padding:0; vertical-align:top;">
                <table class="form-table nested-table" style="border:0; height:100%;">
                    <tr>
                        <td class="label-cell-sm text-center" style="border-top:0; border-right:0; font-size:7pt;">Name of Health Care Provider:</td>
                    </tr>
                    <tr>
                        <td class="field-value-sm text-bold" style="border-right:0; border-bottom:0;">{{ $provider }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="8" style="padding:0; min-height:56px;">
                <table class="form-table nested-table" style="border:0; height:100%;">
                    <tr>
                        <td class="label-cell" style="width:14%; border-top:0; border-left:0; border-bottom:0; font-size:7pt;">Laboratory<br>Findings /<br>Impression:</td>
                    </tr>
                </table>
            </td>
            <td colspan="4" style="padding:0; vertical-align:top;">
                <table class="form-table nested-table" style="border:0; height:100%;">
                    <tr>
                        <td class="label-cell-sm text-center" style="border-top:0; border-right:0; font-size:7pt;">Performed Laboratory Test:</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="form-footer form-footer-flex">
        <span>Clinic Information System</span>
        <span>| FORM 2 |</span>
        <span>Page 1</span>
    </div>
</section>

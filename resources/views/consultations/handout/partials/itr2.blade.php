{{--
    Individual Treatment Record (ITR 2) - Family Planning / Prenatal / Immunization / Postpartum
    iClinicSys FORM 2, Page 2
--}}
@php
    $pregnancy = $pregnancy ?? null;
    $prenatalVisits = collect($prenatalVisits ?? [])->sortBy('visit_date')->values();
    $postnatalRecord = $postnatalRecord ?? null;
    $fpClient = $fpClient ?? null;
    $immunizations = collect($immunizations ?? []);
    $lastVisit = $prenatalVisits->last();

    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '';

    $immDate = function (array $codes, string $needle) use ($immunizations) {
        foreach ($immunizations as $imm) {
            $code = strtolower($imm->vaccine?->vaccine_code ?? '');
            if ($code !== '' && in_array($code, $codes, true)) {
                return $imm->date_given;
            }
        }
        $needle = strtolower($needle);
        foreach ($immunizations as $imm) {
            $name = strtolower($imm->vaccine?->vaccine_name ?? '');
            if ($needle !== '' && $name !== '' && str_contains($name, $needle)) {
                return $imm->date_given;
            }
        }

        return null;
    };

    $childVaccineRows = [
        ['Hepa B with 24 hrs.', ['hepab'], 'hepatitis b'],
        ['Hepa B ≥ 24 hrs', [], ''],
        ['PENTA 1', ['penta1'], 'pentavalent 1'],
        ['PENTA 2', ['penta2'], 'pentavalent 2'],
        ['PENTA 3', ['penta3'], 'pentavalent 3'],
        ['OPV 1', ['opv1'], 'oral polio vaccine 1'],
        ['OPV 2', ['opv2'], 'oral polio vaccine 2'],
        ['OPV 3', ['opv3'], 'oral polio vaccine 3'],
        ['MCV 1 (AMV)', ['mcv1'], 'measles containing vaccine 1'],
        ['MCV-2 (MMR)', ['mcv2'], 'measles containing vaccine 2'],
        ['ROTA 1', ['rota1'], 'rotavirus 1'],
        ['ROTA 2', ['rota2'], 'rotavirus 2'],
        ['PCV 1', ['pcv1'], 'pneumococcal 1'],
        ['PCV 2', ['pcv2'], 'pneumococcal 2'],
        ['PCV 3', ['pcv3'], 'pneumococcal 3'],
        ['Hepa B2', ['hepa b2'], 'hepatitis b 2'],
        ['Hepa B3', ['hepa b3'], 'hepatitis b 3'],
        ['Hepa A', ['hepa a'], 'hepatitis a'],
        ['Pneumonia', ['pneumonia'], 'pneumonia'],
        ['Influenza', [], 'influenza'],
        ['Others:', [], ''],
    ];

    $adultVaccineRows = [
        ['Pneumococcal', ['pneumococcal'], 'pneumococcal'],
        ['Flu', [], 'flu'],
        ['Others:', [], ''],
    ];

    $syphilis = strtolower($pregnancy->syphilis_result ?? '');
    $syphilisNegative = in_array($syphilis, ['negative', 'non-reactive', 'non_reactive'], true);
    $syphilisPositive = str_contains($syphilis, 'pos') || in_array($syphilis, ['reactive'], true);
    $penicillin = strtolower($pregnancy->penicillin ?? '');

    $fpType = \App\Models\FamilyPlanningClient::TYPES[$fpClient->type_of_client ?? ''] ?? ($fpClient->type_of_client ?? '');
    $outcome = \App\Models\PostnatalRecord::OUTCOMES[$postnatalRecord->pregnancy_outcome ?? ''] ?? ($postnatalRecord->pregnancy_outcome ?? '');
    $placeDelivered = \App\Models\PostnatalRecord::PLACES[$postnatalRecord->place_delivered ?? ''] ?? ($postnatalRecord->place_delivered ?? '');
    $modeOfDelivery = \App\Models\PostnatalRecord::MODES[$postnatalRecord->mode_of_delivery ?? ''] ?? ($postnatalRecord->mode_of_delivery ?? '');
    $attendantAtBirth = \App\Models\PostnatalRecord::ATTENDANTS[$postnatalRecord->attendant_at_birth ?? ''] ?? ($postnatalRecord->attendant_at_birth ?? '');
    $dangerSignsMother = implode(', ', $postnatalRecord->danger_signs_mother ?? []);
    $dangerSignsBaby = implode(', ', $postnatalRecord->danger_signs_baby ?? []);
@endphp

<section class="iclinic-form" aria-label="Individual Treatment Record 2">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'INDIVIDUAL TREATMENT RECORD (ITR 2)',
        'serialDigits' => 5,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="7" style="padding:0;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="8" class="section-header">Family Planning</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Type of Client</td>
                        <td colspan="5">{{ $fpType }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Method</td>
                        <td colspan="5">{{ $fpClient->method ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">If Drop-Out, state reason:</td>
                        <td colspan="5">{{ $fpClient->drop_out_reason ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Schedule of Next Visit:</td>
                        <td colspan="5">{{ $fmtDate($fpClient->schedule_next_visit ?? null) }}</td>
                    </tr>

                    <tr>
                        <td colspan="8" class="section-header">Prenatal</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Gravidity</td>
                        <td colspan="2">{{ $pregnancy->gravidity ?? '' }}</td>
                        <td colspan="2" class="label-cell">LMP</td>
                        <td colspan="2">{{ $fmtDate($pregnancy->lmp ?? null) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Parity</td>
                        <td colspan="2">{{ $pregnancy->parity ?? '' }}</td>
                        <td colspan="2" class="label-cell">EDC</td>
                        <td colspan="2">{{ $fmtDate($pregnancy->edc ?? null) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Term</td>
                        <td colspan="2">{{ $pregnancy->term ?? '' }}</td>
                        <td colspan="2" class="label-cell">AOG</td>
                        <td colspan="2">{{ $pregnancy->aog_weeks ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Preterm</td>
                        <td colspan="2">{{ $pregnancy->preterm ?? '' }}</td>
                        <td colspan="2" class="label-cell">TT</td>
                        <td colspan="2">{{ $fmtDate($pregnancy->tt_date ?? null) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Livebirth</td>
                        <td colspan="2">{{ $pregnancy->livebirth ?? '' }}</td>
                        <td colspan="2" class="label-cell">Iron</td>
                        <td colspan="2">{{ $pregnancy ? (($pregnancy->iron_taken ?? false) ? 'Yes' : 'No') : '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Abortion</td>
                        <td colspan="2">{{ $pregnancy->abortion ?? '' }}</td>
                        <td colspan="2" class="label-cell">Others</td>
                        <td colspan="2">{{ $pregnancy->others ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Syphilis Result:</td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $syphilisNegative, 'label' => 'Negative'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $syphilisPositive, 'label' => 'Positive'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Penicillin</td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $penicillin === 'no', 'label' => 'No'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $penicillin === 'yes', 'label' => 'Yes'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Schedule of Next Visit</td>
                        <td colspan="5">{{ $fmtDate($lastVisit->next_visit_date ?? null) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Fundic Height (cm)</td>
                        <td colspan="5">{{ $lastVisit->fundic_height_cm ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="label-cell">Fetal Heart Tone</td>
                        <td colspan="5">{{ $lastVisit->fetal_heart_tone_bpm ?? '' }}</td>
                    </tr>

                    <tr>
                        <td colspan="8" class="section-header">Prenatal Visits</td>
                    </tr>
                    <tr>
                        @for ($i = 0; $i < 8; $i++)
                            <td class="field-value-sm">{{ $fmtDate($prenatalVisits[$i]->visit_date ?? null) }}</td>
                        @endfor
                    </tr>
                    <tr>
                        @for ($i = 0; $i < 8; $i++)
                            <td>&nbsp;</td>
                        @endfor
                    </tr>

                    <tr>
                        <td colspan="8" class="section-header">Menstrual History</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Menarche</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                        <td colspan="2" class="label-cell">Onset of sexual intercourse</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Period/ Duration</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                        <td colspan="2" class="label-cell">Birth Control Method</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label-cell">Interval/ Cycle</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                        <td colspan="2" class="label-cell">Menopause? (Yes/No)</td>
                        <td colspan="2">
                            {{-- TODO: map --}}
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="5" style="padding:0;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="2" class="section-header">Child Immunization</td>
                    </tr>
                    <tr>
                        <td class="label-cell" style="width:55%;">Birth Weight</td>
                        <td>
                            {{-- TODO: map --}}
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell text-center">Immunization</td>
                        <td class="label-cell text-center">Date</td>
                    </tr>
                    @foreach ($childVaccineRows as [$rowLabel, $rowCodes, $rowNeedle])
                        <tr>
                            <td>{{ $rowLabel }}</td>
                            <td>{{ $fmtDate($immDate($rowCodes, $rowNeedle)) }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="2" class="section-header">Adult Immunization</td>
                    </tr>
                    <tr>
                        <td class="label-cell text-center">Immunization</td>
                        <td class="label-cell text-center">Date</td>
                    </tr>
                    @foreach ($adultVaccineRows as [$rowLabel, $rowCodes, $rowNeedle])
                        <tr>
                            <td>{{ $rowLabel }}</td>
                            <td>{{ $fmtDate($immDate($rowCodes, $rowNeedle)) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="12" class="section-header" style="border-top:0;">Postpartum</td>
        </tr>
        <tr>
            <td colspan="3" rowspan="4" style="vertical-align:top;">
                <p class="field-label">Prenatal Outcome</p>
                <p class="field-value">{{ $outcome }}</p>
            </td>
            <td colspan="9" class="label-cell text-center">Child Information</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Last Name</td>
            <td colspan="3" class="field-value text-bold">{{ $postnatalRecord->child_last_name ?? '' }}</td>
            <td colspan="2" class="label-cell">Sex (M / F)</td>
            <td colspan="2">{{ $postnatalRecord->child_sex ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">First Name</td>
            <td colspan="3" class="field-value text-bold">{{ $postnatalRecord->child_first_name ?? '' }}</td>
            <td colspan="2" class="label-cell">Birth length</td>
            <td colspan="2">{{ $postnatalRecord->child_birth_length_cm ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Middle Name</td>
            <td colspan="3">{{ $postnatalRecord->child_middle_name ?? '' }}</td>
            <td colspan="2" class="label-cell">Birth weight</td>
            <td colspan="2">{{ $postnatalRecord->child_birth_weight_kg ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3" class="label-cell">Prenatal Delivered</td>
            <td colspan="3">
                {{-- TODO: map --}}
            </td>
            <td colspan="3" class="label-cell">Delivery Date</td>
            <td colspan="3">{{ $fmtDate($postnatalRecord->delivery_date ?? null) }}</td>
        </tr>
        <tr>
            <td colspan="3" class="label-cell">Place Delivered</td>
            <td colspan="3">{{ $placeDelivered }}</td>
            <td colspan="3" class="label-cell">Delivery Time</td>
            <td colspan="3">{{ $postnatalRecord->delivery_time ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3" class="label-cell">Mode of Delivery</td>
            <td colspan="3">{{ $modeOfDelivery }}</td>
            <td colspan="3" class="label-cell">Date Initiated Breastfeeding</td>
            <td colspan="3">{{ $fmtDate($postnatalRecord->breastfeeding_date ?? null) }}</td>
        </tr>
        <tr>
            <td colspan="3" class="label-cell">Attendant at Birth</td>
            <td colspan="3">{{ $attendantAtBirth }}</td>
            <td colspan="3" class="label-cell">Time Initiated Breastfeeding</td>
            <td colspan="3">{{ $postnatalRecord->breastfeeding_time ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="6" class="label-cell-sm">Date of postpartum visit within 24hrs after delivery</td>
            <td colspan="2">{{ $fmtDate($postnatalRecord->postpartum_24h_date ?? null) }}</td>
            <td colspan="2" class="label-cell-sm">Danger Signs (Mother)</td>
            <td colspan="2" class="field-value-sm">{{ $dangerSignsMother }}</td>
        </tr>
        <tr>
            <td colspan="6" class="label-cell-sm">Date of postpartum visit within 1 week after delivery</td>
            <td colspan="2">{{ $fmtDate($postnatalRecord->postpartum_7d_date ?? null) }}</td>
            <td colspan="2" class="label-cell-sm">Danger Signs (Baby)</td>
            <td colspan="2" class="field-value-sm">{{ $dangerSignsBaby }}</td>
        </tr>
        <tr>
            <td colspan="3" class="label-cell">Date Vitamin A Given</td>
            <td colspan="2">{{ $fmtDate($postnatalRecord->vitamin_a_date ?? null) }}</td>
            <td colspan="2" class="label-cell">Date Iron Given</td>
            <td colspan="2">{{ $fmtDate($postnatalRecord->iron_date ?? null) }}</td>
            <td colspan="2" class="label-cell-sm">No. of Iron Given</td>
            <td colspan="1">{{ $postnatalRecord->iron_count ?? '' }}</td>
        </tr>
    </table>

    <div class="form-footer form-footer-flex">
        <span>Clinic Information System</span>
        <span>| FORM 2 |</span>
        <span>Page 2</span>
    </div>
</section>

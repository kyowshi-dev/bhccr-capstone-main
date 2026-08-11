{{--
    Postpartum Care Record - print sheet
    Expects: $record (PostnatalRecord, loaded with patient.household.zone, pregnancy, childPatient)
--}}
@php
    $patient = $record->patient;
    $patientName = fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix);
    $slots = [
        ['col' => 'postpartum_24h_date', 'label' => '24 Hours'],
        ['col' => 'postpartum_7d_date', 'label' => '7 Days'],
        ['col' => 'postpartum_14d_date', 'label' => '14 Days'],
        ['col' => 'postpartum_28d_date', 'label' => '28 Days'],
    ];
@endphp

<section class="iclinic-form" aria-label="Postpartum Care Record">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'POSTPARTUM CARE RECORD',
        'serialDigits' => 4,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="8" class="section-header">I. Mother Information</td>
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
            <td colspan="2">
                <p class="field-label">Zone / Purok</p>
                <p class="field-value">Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-' }}</p>
            </td>
            <td colspan="5">
                <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                <p class="field-value">{{ $patient->residential_address }}</p>
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td colspan="8" class="section-header">II. Delivery</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Date</td>
            <td colspan="2"><p class="field-value text-bold">{{ $record->delivery_date->format('M d, Y') }}</p></td>
            <td colspan="2" class="label-cell">Time</td>
            <td colspan="2"><p class="field-value text-bold">{{ \Carbon\Carbon::parse($record->delivery_time)->format('g:i A') }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Outcome</td>
            <td colspan="2"><p class="field-value">{{ \App\Models\PostnatalRecord::OUTCOMES[$record->pregnancy_outcome] ?? $record->pregnancy_outcome }}</p></td>
            <td colspan="2" class="label-cell">Place of Delivery</td>
            <td colspan="2"><p class="field-value">{{ \App\Models\PostnatalRecord::PLACES[$record->place_delivered] ?? $record->place_delivered }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Mode of Delivery</td>
            <td colspan="2"><p class="field-value">{{ \App\Models\PostnatalRecord::MODES[$record->mode_of_delivery] ?? $record->mode_of_delivery }}</p></td>
            <td colspan="2" class="label-cell">Attendant</td>
            <td colspan="2"><p class="field-value">{{ \App\Models\PostnatalRecord::ATTENDANTS[$record->attendant_at_birth] ?? $record->attendant_at_birth }}</p></td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Prenatal Visits</td>
            <td colspan="2"><p class="field-value">{{ $record->prenatal_visits_completed ?? '' }}</p></td>
            <td colspan="2" class="label-cell">Breastfeeding Initiated</td>
            <td colspan="2">
                <p class="field-value">
                    {{ $record->breastfeeding_date->format('M d, Y') }}
                    {{ \Carbon\Carbon::parse($record->breastfeeding_time)->format('g:i A') }}
                </p>
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td colspan="8" class="section-header">III. Postpartum Visits</td>
        </tr>
        <tr class="label-cell-sm text-center">
            @foreach ($slots as $slot)
                <td style="width:25%;">{{ $slot['label'] }}</td>
            @endforeach
        </tr>
        <tr class="text-center">
            @foreach ($slots as $slot)
                <td>
                    <p class="field-value text-bold">{{ $record->{$slot['col']}?->format('M d, Y') ?? '' }}</p>
                    <p class="field-value-sm" style="min-height:20px;">&nbsp;</p>
                </td>
            @endforeach
        </tr>
        <tr>
            <td colspan="4" class="label-cell">Mother Danger Signs</td>
            <td colspan="4" class="label-cell">Baby Danger Signs</td>
        </tr>
        <tr>
            <td colspan="4" class="whitespace-pre">{{ implode("\n", $record->danger_signs_mother ?? []) }}</td>
            <td colspan="4" class="whitespace-pre">{{ implode("\n", $record->danger_signs_baby ?? []) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Vitamin A</td>
            <td colspan="2"><p class="field-value">{{ $record->vitamin_a_date?->format('M d, Y') ?? '' }}</p></td>
            <td colspan="2" class="label-cell">Iron (date / count)</td>
            <td colspan="2">
                <p class="field-value">
                    {{ $record->iron_date?->format('M d, Y') ?? '' }}
                    {{ $record->iron_date !== null && $record->iron_count !== null ? '· ' : '' }}{{ $record->iron_count !== null ? 'x'.$record->iron_count : '' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td colspan="8" class="section-header">IV. Newborn</td>
        </tr>
        <tr>
            <td colspan="4">
                <p class="field-label">Name <span class="field-help">(Pangalan)</span></p>
                <p class="field-value text-bold">
                    {{ $record->child_first_name }} {{ $record->child_middle_name }} {{ $record->child_last_name }}
                </p>
            </td>
            <td colspan="2" class="label-cell">Sex</td>
            <td colspan="2">
                <p class="mark-block">
                    <span class="mark-box">{{ $record->child_sex === 'M' ? 'X' : '' }}</span>
                    <span class="mark-label">Male</span>
                    <span style="display:inline-block;width:16px;"></span>
                    <span class="mark-box">{{ $record->child_sex === 'F' ? 'X' : '' }}</span>
                    <span class="mark-label">Female</span>
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="label-cell">Birth Weight (kg)</td>
            <td colspan="2"><p class="field-value">{{ $record->child_birth_weight_kg ?? '' }}</p></td>
            <td colspan="2" class="label-cell">Birth Length (cm)</td>
            <td colspan="2"><p class="field-value">{{ $record->child_birth_length_cm ?? '' }}</p></td>
        </tr>
    </table>

    <div class="form-footer form-footer-flex">
        <span>Barangay Health Center - Sta. Ana</span>
        <span>Recorded: {{ $record->created_at->format('M d, Y g:i A') }}</span>
    </div>
</section>

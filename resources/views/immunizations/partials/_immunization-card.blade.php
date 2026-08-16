{{--
    DOH-style Child Immunization Record card.
    Print/PDF mode only - black 1px borders, Arial, fixed grid. Never app-shell tokens.
    Data: $patient (with household.zone), $records (vaccine_name + doses), $vaccines (with schedules).
--}}
@php
    $isChild = $patient->age < 18;
    $cardTitle = $isChild ? 'CHILD IMMUNIZATION RECORD' : 'IMMUNIZATION RECORD';
    $cardTitleFil = $isChild ? 'REKORD NG BAKUNA NG BATA' : 'REKORD NG BAKUNA';

    $recordsByVaccine = $records->groupBy('vaccine_id');

    $fatherName = trim((string) ($patient->spouse_name ?? '')) !== ''
        ? $patient->spouse_name
        : ($patient->guardian_name ?? '');

    $birthWeightRaw = $patient->getRawOriginal('birth_weight');
    $birthWeight = $birthWeightRaw !== null
        ? number_format((float) $birthWeightRaw, 2).' kg'
        : '';

    $doseAgeLabel = function (int $days): string {
        return match ($days) {
            0 => 'pagkapanganak',
            2 => '≥ 24 oras',
            42 => '1½ buwan',
            70 => '2½ buwan',
            98 => '3½ buwan',
            270 => '9 buwan',
            365 => '12 buwan',
            547 => '18 buwan',
            default => $days.' araw',
        };
    };
@endphp

<section class="card-sheet" aria-label="{{ $cardTitle }}">
    <table class="card-table" style="border-bottom:0;">
        <tr>
            <td style="width:38%; padding:3px 5px; vertical-align:middle;">
                @include('partials._doh-logo')
            </td>
            <td style="vertical-align:middle;">
                <p class="card-title">{{ $cardTitle }}</p>
                <p class="card-subtitle">{{ $cardTitleFil }}</p>
            </td>
            <td style="width:14%; vertical-align:middle; text-align:center;">
                <p class="sig-hint" style="font-weight:bold;">Patient Code</p>
                <p style="font-size:9pt; font-weight:bold;">{{ $patient->patient_code }}</p>
            </td>
        </tr>
    </table>

    <table class="card-table facility-strip" style="border-top:0;">
        <tr>
            <td class="strip-label">Health Center<br><span class="fil" style="text-transform:none;">(Sentro ng Kalusugan)</span></td>
            <td class="strip-label">Barangay</td>
            <td class="strip-label">Purok</td>
            <td class="strip-label">Family No.<br><span class="fil" style="text-transform:none;">(Blg. ng Pamilya)</span></td>
        </tr>
        <tr>
            <td class="strip-value">Sta. Ana Health Center</td>
            <td class="strip-value">Sta. Ana</td>
            <td class="strip-value">{{ $patient->household?->zone?->zone_number ?? '' }}</td>
            <td class="strip-value">{{ $patient->household?->id ?? '' }}</td>
        </tr>
    </table>

    <table class="card-table" style="border-top:0;">
        <tr>
            <td colspan="4" class="section-header">Child Information (Impormasyon ng Bata)</td>
        </tr>
        <tr>
            <td class="info-label">Child's Name<span class="fil">(Pangalan ng Bata)</span></td>
            <td class="info-value" colspan="3">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</td>
        </tr>
        <tr>
            <td class="info-label">Sex<span class="fil">(Kasarian)</span></td>
            <td class="info-value">{{ $patient->sex }}</td>
            <td class="info-label">Date of Birth<span class="fil">(Petsa ng Kapanganakan)</span></td>
            <td class="info-value">{{ \Carbon\Carbon::parse($patient->date_of_birth)->format('m/d/Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Place of Birth<span class="fil">(Lugar ng Kapanganakan)</span></td>
            <td class="info-value">{{ $patient->birth_place ?? '' }}</td>
            <td class="info-label">Birth Weight<span class="fil">(Timbang sa Pagsilang)</span></td>
            <td class="info-value">{{ $birthWeight }}</td>
        </tr>
        <tr>
            <td class="info-label">Mother's Name<span class="fil">(Pangalan ng Ina)</span></td>
            <td class="info-value">{{ $patient->mother_name ?? '' }}</td>
            <td class="info-label">Father's Name<span class="fil">(Pangalan ng Ama)</span></td>
            <td class="info-value">{{ $fatherName }}</td>
        </tr>
        <tr>
            <td class="info-label">Complete Address<span class="fil">(Kumpletong Tirahan)</span></td>
            <td class="info-value" colspan="3">{{ $patient->residential_address ?? '' }}</td>
        </tr>
    </table>

    <table class="card-table" style="border-top:0;">
        <tr>
            <td colspan="4" class="section-header">Immunization (Bakuna)</td>
        </tr>
        <tr>
            <td class="vac-head" style="width:24%;">Vaccine<span class="fil" style="display:block; text-transform:none;">(Bakuna)</span></td>
            <td class="vac-head" style="width:26%;">Doses<span class="fil" style="display:block; text-transform:none;">(Bilang at Panahon)</span></td>
            <td class="vac-head" style="width:28%;">Date Given<span class="fil" style="display:block; text-transform:none;">(Petsa ng Bakuna)</span></td>
            <td class="vac-head" style="width:22%;">Remarks<span class="fil" style="display:block; text-transform:none;">(Puna)</span></td>
        </tr>
        @foreach ($vaccines as $vaccine)
            @php
                $doseSchedules = $vaccine->schedules->sortBy('dose_number');
                $doseLabels = $doseSchedules->map(fn ($s) => $doseAgeLabel((int) $s->min_age_days));
                $dosesText = $doseSchedules->count().' ('.implode(', ', $doseLabels->all()).')';
                $given = $recordsByVaccine->get($vaccine->id, collect())->sortBy('dose_number');
                $remarks = $given->filter(fn ($d) => trim((string) ($d->notes ?? '')) !== '')
                    ->map(fn ($d) => $vaccine->vaccine_name.' '.$d->dose_number.': '.$d->notes);
            @endphp
            <tr>
                <td class="vac-name">{{ $vaccine->vaccine_name }}</td>
                <td class="vac-doses">{{ $dosesText }}</td>
                <td class="vac-dates">
                    @foreach ($given as $dose)
                        <span class="dose-line">{{ $vaccine->vaccine_name }} {{ $dose->dose_number }}: {{ \Carbon\Carbon::parse($dose->date_given)->format('m/d/Y') }}</span>
                    @endforeach
                </td>
                <td class="vac-remarks">{{ $remarks->join('; ') }}</td>
            </tr>
        @endforeach
    </table>

    <table class="card-table" style="border-top:0;">
        <tr>
            <td colspan="4" class="card-footer">
                <p><strong>Note:</strong> Keep this record in a safe place and present it at every visit. / <em>Ingatan ang rekord na ito at ipakita sa bawat pagbisita.</em></p>
            </td>
        </tr>
    </table>

    <div class="sig-area">
        <div class="sig-cell">
            <div class="sig-line">Prepared by (Inihanda ni)</div>
            <p class="sig-hint">Nurse / Midwife / BHW</p>
        </div>
        <div class="sig-cell">
            <div class="sig-line">Date (Petsa)</div>
        </div>
    </div>
</section>

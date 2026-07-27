@php
    $reasonsForReferral = $referral->specific_details ?: 'For further evaluation and management';
    $bp = ($vitals->bp_systolic ?? null) && ($vitals->bp_diastolic ?? null)
        ? $vitals->bp_systolic.'/'.$vitals->bp_diastolic
        : '';
    $temperature = $vitals->temperature_c ?? null;
    $weight = $vitals->weight_kg ?? null;
    $patientName = trim(($patient->last_name ?? '').', '.($patient->first_name ?? '').($patient->suffix ? ' '.$patient->suffix : ''));
    $dob = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('m/d/Y') : '';
    $philhealthNo = ($patient->is_philhealth_member ?? 'n') === 'y' ? ($patient->philhealth_no ?? '') : '';
    $isPhilhealth = ($patient->is_philhealth_member ?? 'n') === 'y';
    $hasNone = ! $isPhilhealth && ! ($patient->has_nhts ?? false) && ! ($patient->has_4ps ?? false);
    $category = strtolower((string) ($patient->membership_category ?? ''));
    $isLguSponsored = str_contains($category, 'government') || str_contains($category, 'lgu');
    $isDepEd = str_contains($category, 'deped');

    $philhealthOptions = [
        ['checked' => $hasNone, 'label' => 'NONE'],
        ['checked' => (bool) ($patient->has_nhts ?? false), 'label' => 'NHTS'],
        ['checked' => (bool) ($patient->has_4ps ?? false), 'label' => '4Ps'],
        ['checked' => $isLguSponsored, 'label' => 'LGU SPONSORED'],
        ['checked' => $isDepEd, 'label' => 'DepEd'],
    ];

    $historyLines = preg_split("/\r\n|\n|\r/", trim((string) ($referral->pertinent_history ?? ''))) ?: [];
    $historyLines = array_pad(array_slice($historyLines, 0, 3), 3, '');

    $actionLines = preg_split("/\r\n|\n|\r/", trim((string) ($referral->actions_taken ?? ''))) ?: [];
    $actionLines = array_pad(array_slice($actionLines, 0, 4), 4, '');
@endphp

<style>
    .referral-slip {
        width: 850px;
        height: 722px;
        box-sizing: border-box;
        padding: 28px 34px 24px;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        line-height: 1.25;
        color: #000;
        background: #fff;
        display: flex;
        flex-direction: column;
        margin-top: 100px;
    }

    .referral-top {
        display: flex;
        align-items: flex-start;
        gap: 28px;
        margin-bottom: 18px;
    }

    .referral-patient {
        flex: 1 1 0;
        min-width: 0;
    }

    .referral-philhealth {
        flex: 0 0 198px;
        padding-top: 2px;
    }

    .field-row {
        display: flex;
        align-items: baseline;
        margin-bottom: 10px;
    }

    .field-row:last-child {
        margin-bottom: 0;
    }

    .field-label {
        white-space: nowrap;
        margin-right: 6px;
    }

    .field-line {
        display: inline-block;
        border-bottom: 1px solid #000;
        min-height: 16px;
        vertical-align: baseline;
        padding: 0 2px 1px;
    }

    .field-line.date {
        width: 92px;
        margin-right: 18px;
    }

    .field-line.time {
        width: 118px;
    }

    .field-line.full {
        flex: 1 1 auto;
        width: 100%;
    }

    .field-line.name {
        width: 290px;
    }

    .field-line.address {
        width: 360px;
    }

    .field-line.age {
        width: 34px;
        margin-right: 18px;
    }

    .field-line.dob {
        width: 118px;
    }

    .philhealth-title {
        font-weight: 700;
        letter-spacing: 0.06em;
        margin: 0 0 8px;
        font-size: 11px;
    }

    .philhealth-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .philhealth-list li {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 5px;
        font-size: 10px;
        letter-spacing: 0.02em;
    }

    .philhealth-list li:last-child {
        margin-bottom: 0;
    }

    .check-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 12px;
        height: 12px;
        border: 1px solid #000;
        font-size: 9px;
        line-height: 1;
        flex-shrink: 0;
    }

    .philhealth-no {
        margin-left: auto;
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        white-space: nowrap;
        font-size: 10px;
    }

    .philhealth-no .field-line {
        width: 58px;
    }

    .referral-section {
        margin-bottom: 14px;
    }

    .section-heading {
        font-weight: 700;
        margin: 0 0 8px;
    }

    .section-heading.upper {
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .vitals-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        column-gap: 24px;
        row-gap: 8px;
        margin-right: 348px;
    }

    .vital-item {
        display: flex;
        align-items: baseline;
        gap: 6px;
        white-space: nowrap;
    }

    .vital-item .field-line {
        width: 72px;
    }

    .vital-item .field-line.weight {
        width: 72px;
    }

    .lined-block {
        min-height: 18px;
        border-bottom: 1px solid #000;
        margin-bottom: 10px;
        padding: 0 2px 1px;
        white-space: pre-wrap;
        overflow-wrap: break-word;
        word-wrap: break-word;
        overflow: hidden;
    }

    .lined-block:last-child {
        margin-bottom: 0;
    }

    .lined-block.empty::after {
        content: '\00a0';
    }

    .reason-line {
        margin: 0 0 14px;
        line-height: 1.35;
    }

    .reason-label {
        font-weight: 700;
        text-transform: uppercase;
    }

    .signature-row {
        display: flex;
        justify-content: space-between;
        gap: 80px;
        margin-top: 0.5rem;
        padding-top: 8px;
    }

    .signature-block {
        flex: 1 1 0;
        text-align: center;
    }

    .signature-name {
        min-height: 18px;
        margin: 0 0 4px;
        font-weight: 700;
        font-size: 10px;
    }

    .signature-line {
        border-top: 1px solid #000;
        padding-top: 4px;
        margin: 0;
        font-weight: 700;
        letter-spacing: 0.08em;
        font-size: 10px;
    }
</style>

<section class="referral-slip" aria-label="Outward Referral Slip">
    <div class="referral-top">
        <div class="referral-patient">
            <div class="field-row">
                <span class="field-label">Date:</span>
                <span class="field-line date">{{ $referredAt->format('m/d/Y') }}</span>
                <span class="field-label">Time Referred:</span>
                <span class="field-line time">{{ $referredAt->format('g:i A') }}</span>
            </div>

            <div class="field-row">
                <span class="field-label">Name:</span>
                <span class="field-line name">{{ $patientName }}</span>
            </div>

            <div class="field-row">
                <span class="field-label">Age:</span>
                <span class="field-line age">{{ $age ?? '' }}</span>
                <span class="field-label">Date of Birth:</span>
                <span class="field-line dob">{{ $dob }}</span>
            </div>

            <div class="field-row">
                <span class="field-label">Address:</span>
                <span class="field-line address">{{ $patient->residential_address ?? '' }}</span>
            </div>
        </div>

        <aside class="referral-philhealth" aria-label="PhilHealth membership">
            <p class="philhealth-title">PHILHEALTH</p>
            <ul class="philhealth-list">
                @foreach ($philhealthOptions as $option)
                    <li>
                        <span class="check-box" aria-hidden="true">{!! $option['checked'] ? 'X' : '&nbsp;' !!}</span>
                        <span>{{ $option['label'] }}</span>
                        <span class="philhealth-no">
                            <span>No.</span>
                            <span class="field-line">{{ $option['checked'] && $philhealthNo ? $philhealthNo : '' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>

    <div class="referral-section">
        <p class="section-heading">Vitals Signs:</p>
        <div class="vitals-grid">
            <div class="vital-item">
                <span class="field-label">Temp:</span>
                <span class="field-line">{{ $temperature !== null ? $temperature : '' }}</span>
                <span>°C</span>
            </div>
            <div class="vital-item">
                <span class="field-label">B/P:</span>
                <span class="field-line">{{ $bp }}</span>
            </div>
            <div class="vital-item">
                <span class="field-label">LMP:</span>
                <span class="field-line"></span>
            </div>
            <div class="vital-item">
                <span class="field-label">Weight:</span>
                <span class="field-line weight">{{ $weight !== null ? $weight : '' }}</span>
                <span>kg.</span>
            </div>
            <div class="vital-item">
                <span class="field-label">RR:</span>
                <span class="field-line"></span>
            </div>
            <div class="vital-item">
                <span class="field-label">PR:</span>
                <span class="field-line"></span>
            </div>
        </div>
    </div>

    <div class="referral-section">
        <p class="section-heading upper">Pertinent History of Illnesses and Findings</p>
        @foreach ($historyLines as $line)
            <div class="lined-block">{{ $line }}</div>
        @endforeach
    </div>

    <div class="referral-section">
        <p class="section-heading upper">
        {{ $reasonsForReferral }}
        </p>
    </div>

    <div class="referral-section">
        <p class="section-heading upper">Action/s Taken:</p>
        @foreach ($actionLines as $line)
            <div class="lined-block">{{ trim($line) !== '' ? $line : '' }}</div>
        @endforeach
    </div>

    <div class="signature-row">
        <div class="signature-block">
            <p class="signature-name">&nbsp;</p>
            <p class="signature-line">RHM</p>
        </div>
        <div class="signature-block">
            <p class="signature-name">&nbsp;</p>
            <p class="signature-line">NDP</p>
        </div>
    </div>
</section>

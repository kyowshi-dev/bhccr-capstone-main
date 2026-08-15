<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal, EPI & Family Planning Report - {{ $reportDate }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        .report-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        h2 {
            font-size: 14px;
            margin: 16px 0 8px 0;
        }
        .summary-block {
            display: flex;
            gap: 16px;
            margin-bottom: 8px;
        }
        .summary-block table {
            margin-bottom: 0;
        }
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 45%;
            text-align: center;
        }
        .signature-line hr {
            border: none;
            border-top: 1px solid #333;
            margin: 40px 0 5px 0;
        }
        .signature-title {
            font-size: 11px;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Barangay Health Center Information System</h1>
        <p>Sta. Ana Barangay Health Center</p>
        <p>Department of Health Field Health Service Information System (FHSIS)</p>
    </div>

    <div class="report-info">
        <h2 style="font-size: 14px; margin: 0;">Monthly Maternal, EPI &amp; Family Planning Report</h2>
        <p style="margin: 5px 0;">Report Period: {{ $reportDate }} | Zone: {{ $zoneLabel }} | Program: {{ $programLabel }}</p>
    </div>

    @php
        $maternal = $summaries['maternal'] ?? null;
        $epi = $summaries['epi'] ?? null;
        $fp = $summaries['fp'] ?? null;
    @endphp

    @if ($maternal !== null)
        <h2>Maternal Care</h2>
        <div class="summary-block">
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th style="width: 80px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>New prenatal clients registered</td><td class="text-right">{{ number_format($maternal['newPrenatalClients']) }}</td></tr>
                    <tr><td>Prenatal visits conducted</td><td class="text-right">{{ number_format($maternal['prenatalVisits']) }}</td></tr>
                    <tr><td>Pregnant women with 4+ prenatal visits</td><td class="text-right">{{ number_format($maternal['prenatalFourPlus']) }}</td></tr>
                    <tr><td>Deliveries</td><td class="text-right">{{ number_format($maternal['totalDeliveries']) }}</td></tr>
                    <tr><td>Postnatal within 24 hours</td><td class="text-right">{{ number_format($maternal['postpartum24h']) }}</td></tr>
                    <tr><td>Postnatal within 7 days</td><td class="text-right">{{ number_format($maternal['postpartum7d']) }}</td></tr>
                    <tr><td>Postnatal within 14 days</td><td class="text-right">{{ number_format($maternal['postpartum14d']) }}</td></tr>
                    <tr><td>Postnatal within 28 days</td><td class="text-right">{{ number_format($maternal['postpartum28d']) }}</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Place of Delivery</th>
                        <th style="width: 60px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maternal['deliveriesByPlace'] as $row)
                        <tr><td>{{ \App\Services\MchEpiFpReportService::placeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Outcome</th>
                        <th style="width: 60px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maternal['deliveriesByOutcome'] as $row)
                        <tr><td>{{ \App\Services\MchEpiFpReportService::outcomeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($epi !== null)
        <h2>EPI Immunization</h2>
        <div class="summary-block">
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th style="width: 80px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Child doses</td><td class="text-right">{{ number_format($epi['childDoses']) }}</td></tr>
                    <tr><td>Adult doses</td><td class="text-right">{{ number_format($epi['adultDoses']) }}</td></tr>
                    <tr><td>Total doses given</td><td class="text-right">{{ number_format($epi['totalDoses']) }}</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Antigen &amp; Dose</th>
                        <th style="width: 110px;" class="text-center">Doses Administered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($epi['dosesByVaccine'] as $row)
                        <tr>
                            <td>{{ $row->vaccine_name }} {{ $row->dose_number }}</td>
                            <td class="text-right">{{ number_format($row->doses) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No doses recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($fp !== null)
        <h2>Family Planning</h2>
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th class="text-center">New Acceptors</th>
                    <th class="text-center">Continuing Users</th>
                    <th class="text-center">Drop Outs</th>
                    <th class="text-center">Others</th>
                    <th class="text-center">Visits</th>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fp['rows'] as $row)
                    <tr>
                        <td>{{ $row->method }}</td>
                        <td class="text-right">{{ number_format($row->new_acceptors) }}</td>
                        <td class="text-right">{{ number_format($row->continuing_users) }}</td>
                        <td class="text-right">{{ number_format($row->drop_outs) }}</td>
                        <td class="text-right">{{ number_format($row->others) }}</td>
                        <td class="text-right">{{ number_format($row->visits) }}</td>
                        <td class="text-right">{{ number_format($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center no-data">No family planning clients for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <h2>Service Register ({{ number_format($totalRows) }} records)</h2>

    @php
        $registerPrograms = [
            \App\Services\MchEpiFpReportService::PROGRAM_MATERNAL,
            \App\Services\MchEpiFpReportService::PROGRAM_FP,
            \App\Services\MchEpiFpReportService::PROGRAM_EPI,
        ];
        $rowsByProgram = $rows->groupBy('program');
    @endphp

    @foreach ($registerPrograms as $registerProgram)
        @if ($summaries[$registerProgram] === null)
            @continue
        @endif

        @php
            $programRows = $rowsByProgram->get($registerProgram, collect());
            $programLabel = \App\Services\MchEpiFpReportService::PROGRAMS[$registerProgram];
        @endphp

        <h3>{{ $programLabel }} ({{ number_format($programRows->count()) }} records)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Date</th>
                    <th>Service</th>
                    <th>Patient</th>
                    <th>Zone</th>
                    <th>Health Worker</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programRows as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->date)->format('m/d/Y') }}</td>
                        <td>{{ $row->service }}</td>
                        <td>{{ $row->patient_name }} <span style="color: #666;">({{ $row->patient_code }})</span></td>
                        <td>{{ $row->zone }}</td>
                        <td>{{ $row->worker_name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center no-data">No records for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="signatures">
        <div class="signature-line">
            <div class="signature-title">Prepared By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Midwife / Barangay Health Worker</p>
        </div>
        <div class="signature-line">
            <div class="signature-title">Noted By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Health Center In-Charge</p>
        </div>
    </div>
</body>
</html>

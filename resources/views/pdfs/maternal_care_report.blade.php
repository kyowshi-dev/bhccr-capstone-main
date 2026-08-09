<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Care Report - {{ $reportDate }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px;
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
        <h2 style="font-size: 14px; margin: 0;">Monthly Maternal Care Report</h2>
        <p style="margin: 5px 0;">Report Period: {{ $reportDate }} | Zone: {{ $zoneLabel }}</p>
    </div>

    <h2>Prenatal Care</h2>
    <table>
        <thead>
            <tr>
                <th>Indicator</th>
                <th style="width: 100px;" class="text-center">Count</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>New prenatal clients registered</td><td class="text-right">{{ number_format($newPrenatalClients) }}</td></tr>
            <tr><td>Prenatal visits conducted</td><td class="text-right">{{ number_format($prenatalVisits) }}</td></tr>
            <tr><td>Pregnant women with 4+ prenatal visits</td><td class="text-right">{{ number_format($prenatalFourPlus) }}</td></tr>
            <tr><td>TT/Td doses given</td><td class="text-right">{{ number_format($ttDoses) }}</td></tr>
            <tr><td>With iron supplementation</td><td class="text-right">{{ number_format($ironSupplemented) }}</td></tr>
            <tr><td>Syphilis-positive screened</td><td class="text-right">{{ number_format($syphilisPositive) }}</td></tr>
        </tbody>
    </table>

    <h2>Deliveries (Total: {{ number_format($totalDeliveries) }})</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center">Place of Delivery</th>
                <th style="width: 100px;" class="text-center">Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveriesByPlace as $row)
                <tr><td>{{ \App\Services\MaternalCareReportService::placeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-center">Attendant at Birth</th>
                <th style="width: 100px;" class="text-center">Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveriesByAttendant as $row)
                <tr><td>{{ \App\Services\MaternalCareReportService::attendantLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-center">Outcome</th>
                <th style="width: 100px;" class="text-center">Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveriesByOutcome as $row)
                <tr><td>{{ \App\Services\MaternalCareReportService::outcomeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Postpartum / Postnatal Visits</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center">Within 24 hours</th>
                <th class="text-center">Within 7 days</th>
                <th class="text-center">Within 14 days</th>
                <th class="text-center">Within 28 days</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-right">{{ number_format($postpartum24h) }}</td>
                <td class="text-right">{{ number_format($postpartum7d) }}</td>
                <td class="text-right">{{ number_format($postpartum14d) }}</td>
                <td class="text-right">{{ number_format($postpartum28d) }}</td>
            </tr>
        </tbody>
    </table>

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
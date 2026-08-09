<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Report - {{ $reportDate }}</title>
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
        <h2 style="font-size: 14px; margin: 0;">Monthly Referral Report</h2>
        <p style="margin: 5px 0;">Outward and Incoming Referrals</p>
        <p style="margin: 5px 0;">Report Period: {{ $reportDate }} | Zone: {{ $zoneLabel }}</p>
    </div>

    <h2>Outward Referrals (Total: {{ number_format($totalOutward) }})</h2>
    @if($outwardByDestination->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Destination Facility</th>
                    <th style="width: 75px;" class="text-center">Pending</th>
                    <th style="width: 85px;" class="text-center">Completed</th>
                    <th style="width: 75px;" class="text-center">No-Show</th>
                    <th style="width: 75px;" class="text-center">Cancelled</th>
                    <th style="width: 70px;" class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outwardByDestination as $row)
                    <tr>
                        <td>{{ $row->destination }}</td>
                        <td class="text-right">{{ number_format($row->pending) }}</td>
                        <td class="text-right">{{ number_format($row->completed) }}</td>
                        <td class="text-right">{{ number_format($row->no_shows) }}</td>
                        <td class="text-right">{{ number_format($row->cancelled) }}</td>
                        <td class="text-right">{{ number_format($row->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">No outward referrals recorded for this period.</div>
    @endif

    @if($outwardByStatus->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th class="text-center">Status</th>
                    <th style="width: 100px;" class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outwardByStatus as $statusRow)
                    <tr>
                        <td>{{ \App\Services\ReferralReportService::statusLabel($statusRow->status) }}</td>
                        <td class="text-right">{{ number_format($statusRow->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Incoming Referrals (Total: {{ number_format($totalInward) }})</h2>
    @if($inwardBySource->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Source Facility</th>
                    <th style="width: 100px;" class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inwardBySource as $row)
                    <tr>
                        <td>{{ $row->source }}</td>
                        <td class="text-right">{{ number_format($row->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">No incoming referrals recorded for this period.</div>
    @endif

    <div class="signatures">
        <div class="signature-line">
            <div class="signature-title">Prepared By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Nurse / Barangay Health Worker</p>
        </div>
        <div class="signature-line">
            <div class="signature-title">Noted By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Health Center In-Charge</p>
        </div>
    </div>
</body>
</html>
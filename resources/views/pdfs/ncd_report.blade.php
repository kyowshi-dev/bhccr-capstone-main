<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adult Care / NCD Report - {{ $reportDate }}</title>
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
        <p>Department of Health - Non-Communicable Diseases (NCD) / Adult Care</p>
    </div>

    <div class="report-info">
        <h2 style="font-size: 14px; margin: 0;">Monthly Adult Care / NCD Report</h2>
        <p style="margin: 5px 0;">Report Period: {{ $reportDate }} | Zone: {{ $zoneLabel }}</p>
    </div>

    @if($rows->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Condition</th>
                    <th style="width: 110px;" class="text-center">Patients Seen</th>
                    <th style="width: 110px;" class="text-center">Consultations</th>
                    <th style="width: 110px;" class="text-center">On Registry</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->label }}</td>
                        <td class="text-right">{{ number_format($row->patients_seen) }}</td>
                        <td class="text-right">{{ number_format($row->consultations) }}</td>
                        <td class="text-right">{{ number_format($row->registry_patients) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">No NCD data recorded for this period.</div>
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
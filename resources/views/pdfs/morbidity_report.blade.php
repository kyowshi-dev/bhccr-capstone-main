<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FHSIS Morbidity Report - {{ $reportDate }}</title>
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
            border: 1px solid #ddd;
            padding: 8px;
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
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
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
        <p>Department of Health — Field Health Service Information System (FHSIS)</p>
    </div>

    <div class="report-info">
        <h2 style="font-size: 14px; margin: 0;">FHSIS Morbidity Report</h2>
        <p style="margin: 5px 0;">Leading Causes of Morbidity</p>
        <p style="margin: 5px 0;">Report Period: {{ $reportDate }}</p>
    </div>

    @if($rows->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">Rank</th>
                    <th style="width: 100px;">ICD Code</th>
                    <th>Diagnosis / Cause</th>
                    <th style="width: 80px;" class="text-center">Cases</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $rank => $row)
                    <tr>
                        <td class="text-center">{{ $rank + 1 }}</td>
                        <td>{{ $row->diagnosis_code }}</td>
                        <td>{{ $row->diagnosis_name }}</td>
                        <td class="text-right">{{ number_format($row->case_count) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total Cases:</td>
                    <td class="text-right">{{ number_format($totalCases) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            No morbidity data available for this period.
        </div>
    @endif

    <div class="signatures">
        <div class="signature-line">
            <div class="signature-title">Prepared By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Barangay Health Worker</p>
        </div>
        <div class="signature-line">
            <div class="signature-title">Approved By:</div>
            <hr>
            <p style="font-size: 10px; margin: 5px 0;">Barangay Captain / Health Center In-Charge</p>
        </div>
    </div>
</body>
</html>
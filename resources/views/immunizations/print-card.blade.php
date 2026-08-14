<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Immunization Record - {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</title>
    @include('immunizations.partials._card-styles')
    <style>
        .toolbar-inner {
            max-width: 190mm;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            justify-content: space-between;
            font-family: system-ui, sans-serif;
        }
        .toolbar-inner .tb-title { font-size: 14px; font-weight: 600; color: #1f2937; margin: 0; }
        .toolbar-inner .tb-sub { font-size: 12px; color: #6b7280; margin: 0; }
        .tb-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .tb-btn {
            border-radius: 8px;
            background: #064e3b;
            color: #fff;
            border: 0;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .tb-btn-secondary {
            border-radius: 8px;
            border: 1px solid #064e3b;
            color: #064e3b;
            background: #fff;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body class="preview-body">
    <div class="no-print sticky top-0 z-10 border-b border-gray-300 bg-white px-4 py-3">
        <div class="toolbar-inner">
            <div>
                <p class="tb-title">Child Immunization Record</p>
                <p class="tb-sub">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }} · {{ $patient->patient_code }}</p>
            </div>
            <div class="tb-actions">
                <a href="{{ route('immunizations.print-card.pdf', $patient->id) }}" target="_blank" rel="noopener" class="tb-btn">
                    Download PDF
                </a>
                <button type="button" onclick="window.print()" class="tb-btn">
                    Print
                </button>
                <a href="{{ route('immunizations.patient', $patient->id) }}" class="tb-btn-secondary">
                    Back to record
                </a>
            </div>
        </div>
    </div>

    <main>
        @include('immunizations.partials._immunization-card', [
            'patient' => $patient,
            'records' => $records,
            'vaccines' => $vaccines,
        ])
    </main>
</body>
</html>

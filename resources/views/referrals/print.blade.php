<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Slip — R{{ str_pad($referral->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: 850px 722px;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #d1d5db;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        p {
            margin: 0;
        }

        .no-print {
            display: block;
        }

        main {
            padding: 16px 12px 24px;
            display: flex;
            justify-content: center;
        }

        .referral-page {
            width: 850px;
            height: 722px;
            margin: 0 auto;
            overflow: hidden;
        }

        @media screen {
            .referral-page {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html, body {
                background: #fff;
            }

            main {
                padding: 0 !important;
            }

            .referral-page {
                margin: 0;
                box-shadow: none;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print sticky top-0 z-10 border-b border-gray-300 bg-white px-4 py-3" style="font-family: system-ui, sans-serif;">
        <div style="max-width:900px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:8px;justify-content:space-between;">
            <div>
                <p style="font-size:14px;font-weight:600;color:#1f2937;margin:0;">Outward Referral Slip</p>
                <p style="font-size:12px;color:#6b7280;margin:0;">
                    R{{ str_pad($referral->id, 4, '0', STR_PAD_LEFT) }} ·
                    {{ $patient->last_name ?? '' }}, {{ $patient->first_name ?? '' }}
                </p>
                <p style="font-size:11px;color:#6b7280;margin:4px 0 0;">
                    Form size: 850 × 722 px — use 100% scale when printing.
                </p>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" onclick="window.print()"
                        style="border-radius:8px;background:#065f46;color:#fff;border:0;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                    Print referral
                </button>
                <a href="{{ route('referrals.index') }}"
                   style="border-radius:8px;border:1px solid #064e3b;color:#064e3b;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none;">
                    Back to referrals
                </a>
            </div>
        </div>
    </div>

    <main>
        <div class="referral-page">
            @include('referrals.partials._print-form')
        </div>
    </main>
</body>
</html>

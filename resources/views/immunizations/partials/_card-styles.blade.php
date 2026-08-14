<style>
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        background: #fff;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9pt;
        line-height: 1.15;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    p {
        margin: 0;
    }

    .card-sheet {
        width: 100%;
        max-width: 190mm;
        margin: 0 auto;
        border: 2px solid #000;
        background: #fff;
        page-break-inside: avoid;
    }

    .card-toolbar {
        max-width: 190mm;
        margin: 0 auto 10px;
    }

    table.card-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.card-table td,
    table.card-table th {
        border: 1px solid #000;
        padding: 2px 4px;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* DOH brand block (mirrors consultation handout classes) */
    .doh-header-brand {
        display: table;
        width: 100%;
    }

    .doh-logo-wrap {
        display: table-cell;
        width: 40px;
        vertical-align: middle;
        padding-right: 4px;
    }

    .doh-logo-wrap .logo-circle {
        width: 34px;
        height: 34px;
        border: 1px solid #000;
        border-radius: 50%;
        overflow: hidden;
        text-align: center;
        background: #fff;
        line-height: 32px;
    }

    .doh-logo-wrap img {
        width: 28px;
        height: 28px;
        vertical-align: middle;
    }

    .doh-brand {
        display: table-cell;
        vertical-align: middle;
        line-height: 1.08;
    }

    .doh-brand .rep { font-size: 7pt; }
    .doh-brand .dept {
        font-size: 10pt;
        font-weight: bold;
        color: #1a5c2e;
        line-height: 1;
    }
    .doh-brand .dept-fil {
        font-size: 8pt;
        font-style: italic;
        line-height: 1;
    }

    .card-title {
        text-align: center;
        font-size: 11pt;
        font-weight: bold;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 2px 4px;
    }

    .card-subtitle {
        text-align: center;
        font-size: 8.5pt;
        padding: 0 4px 2px;
    }

    .facility-strip td {
        text-align: center;
        font-size: 8pt;
    }

    .facility-strip .strip-label {
        font-weight: bold;
        text-transform: uppercase;
        background: #e5e7eb;
        font-size: 7pt;
        text-align: center;
        padding: 1px 2px;
    }

    .facility-strip .strip-value {
        min-height: 12px;
        font-size: 8.5pt;
        font-weight: bold;
    }

    .info-label {
        background: #e5e7eb;
        font-weight: bold;
        font-size: 7.5pt;
        text-transform: uppercase;
        vertical-align: middle !important;
        width: 24%;
    }

    .info-label .fil {
        font-weight: normal;
        font-style: italic;
        text-transform: none;
        font-size: 7pt;
        display: block;
    }

    .info-value {
        min-height: 14px;
        font-size: 9pt;
    }

    .section-header {
        background: #6b7280;
        color: #fff;
        font-weight: bold;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        padding: 2px 4px !important;
        vertical-align: middle !important;
    }

    .vac-head {
        background: #e5e7eb;
        font-weight: bold;
        font-size: 8pt;
        text-align: center;
        text-transform: uppercase;
        vertical-align: middle !important;
        padding: 2px 3px !important;
    }

    .vac-name {
        font-weight: bold;
        font-size: 8.5pt;
        vertical-align: middle !important;
    }

    .vac-doses {
        font-size: 8pt;
    }

    .vac-dates {
        font-size: 8pt;
        white-space: pre-line;
    }

    .vac-remarks {
        font-size: 7.5pt;
        font-style: italic;
    }

    .vac-dates .dose-line {
        display: block;
    }

    .card-footer {
        padding: 3px 4px;
        font-size: 7.5pt;
    }

    .sig-area {
        display: table;
        width: 100%;
        margin-top: 10px;
    }

    .sig-cell {
        display: table-cell;
        width: 50%;
        padding: 0 8px;
        text-align: center;
        font-size: 8pt;
    }

    .sig-line {
        margin-top: 24px;
        border-top: 1px solid #000;
        padding-top: 2px;
        font-weight: bold;
    }

    .sig-hint {
        font-weight: normal;
        font-style: italic;
        font-size: 7.5pt;
    }

    .no-print { display: block; }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .card-sheet {
            border: 2px solid #000;
            box-shadow: none !important;
            max-width: none;
        }
    }

    @media screen {
        body.preview-body {
            background: #e8e4dc;
            padding: 14px 8px 24px;
        }

        .card-sheet {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
    }
</style>

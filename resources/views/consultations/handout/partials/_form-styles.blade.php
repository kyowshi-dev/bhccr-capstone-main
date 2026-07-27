<style>
    @page {
        size: A4 portrait;
        margin: 6mm;
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
        font-size: 8pt;
        line-height: 1.12;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    p {
        margin: 0;
    }

    .iclinic-sheet {
        width: 100%;
        margin: 0 auto;
        page-break-after: always;
        page-break-inside: avoid;
        background: #fff;
    }

    .iclinic-sheet:last-child {
        page-break-after: auto;
    }

    .iclinic-form {
        width: 100%;
        border: 2px solid #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8pt;
        line-height: 1.12;
        color: #000;
    }

    table.form-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.form-table td,
    table.form-table th {
        border: 1px solid #000;
        padding: 1px 3px;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
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

    .label-cell {
        background: #d1d5db;
        font-weight: bold;
        font-size: 8pt;
        vertical-align: middle !important;
    }

    .label-cell-sm {
        background: #d1d5db;
        font-weight: bold;
        font-size: 7pt;
        vertical-align: middle !important;
    }

    .sub-label {
        font-weight: normal;
        font-size: 7pt;
    }

    .field-label {
        font-weight: bold;
        font-size: 8pt;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .field-help {
        font-weight: normal;
        font-size: 7pt;
        line-height: 1.1;
    }

    .field-value {
        min-height: 12px;
        font-size: 9pt;
        line-height: 1.15;
    }

    .field-value-sm {
        min-height: 11px;
        font-size: 8pt;
        line-height: 1.15;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .text-upper { text-transform: uppercase; }
    .whitespace-pre { white-space: pre-wrap; }

 

    .form-header-block {
        border: 1px solid #000;
        border-bottom: 0;
        text-align: center;
        padding: 2px 4px;
    }

    .form-header-block .title-caption {
        font-size: 9pt;
        letter-spacing: 0.06em;
        margin-bottom: 0;
    }

    .form-header-block h1 {
        font-size: 11pt;
        font-weight: bold;
        letter-spacing: 0.06em;
        margin: 0;
        text-transform: uppercase;
    }

    .instructions {
        border: 1px solid #000;
        border-bottom: 0;
        padding: 2px 4px;
        font-size: 7pt;
        font-style: italic;
        line-height: 1.15;
    }

    .doh-header-brand {
        display: table;
        width: 100%;
    }

    .doh-logo-wrap {
        display: table-cell;
        width: 42px;
        vertical-align: middle;
        padding-right: 4px;
    }

    .doh-logo-wrap .logo-circle {
        width: 38px;
        height: 38px;
        border: 1px solid #000;
        border-radius: 50%;
        overflow: hidden;
        text-align: center;
        background: #fff;
        line-height: 36px;
    }

    .doh-logo-wrap img {
        width: 32px;
        height: 32px;
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

    .form-footer {
        border: 1px solid #000;
        border-top: 0;
        background: #9ca3af;
        font-size: 7pt;
        font-weight: bold;
        padding: 2px 4px;
    }

    .form-footer-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .mark {
        display: inline-flex;
        align-items: flex-start;
        gap: 2px;
        margin-right: 4px;
        vertical-align: top;
    }

    .mark-block {
        display: flex;
        align-items: flex-start;
        gap: 2px;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .mark-box {
        display: inline-block;
        width: 10px;
        height: 10px;
        min-width: 10px;
        border: 1px solid #000;
        text-align: center;
        font-size: 7pt;
        font-weight: bold;
        line-height: 10px;
        flex-shrink: 0;
    }

    .mark-label {
        font-size: 8pt;
        line-height: 1.1;
    }

    .marks-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 4px;
    }

    .marks-stack {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .am-pm-box {
        display: inline-flex;
        border: 1px solid #000;
        font-size: 7pt;
        margin-left: 2px;
        vertical-align: middle;
    }

    .am-pm-box span { padding: 0 3px; }
    .am-pm-box span.active {
        background: #d1d5db;
        font-weight: bold;
    }
    .am-pm-box .sep {
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        padding: 0 2px;
    }

    .consent-col-title {
        background: #d1d5db;
        font-weight: bold;
        text-align: center;
        font-size: 8pt;
        padding: 2px;
        border-bottom: 1px solid #000;
    }

    .consent-text {
        padding: 3px;
        font-size: 7pt;
        text-align: justify;
        line-height: 1.2;
    }

    .consent-text p { margin-bottom: 2px; }

    .sig-line {
        margin-top: 16px;
        border-top: 1px dotted #000;
    }

    table.form-table.nested-table > tbody > tr > td,
    table.form-table.nested-table > tbody > tr > th {
        border-left: 0;
        border-right: 0;
    }

    table.form-table.nested-table > tbody > tr:first-child > td,
    table.form-table.nested-table > tbody > tr:first-child > th {
        border-top: 0;
    }

    table.form-table.nested-table > tbody > tr:last-child > td,
    table.form-table.nested-table > tbody > tr:last-child > th {
        border-bottom: 0;
    }

    .no-print { display: block; }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .iclinic-sheet { box-shadow: none !important; }
    }

    @media screen {
        body.preview-body {
            background: #e8e4dc;
            padding: 12px 0 24px;
        }

        .iclinic-sheet {
            max-width: 198mm;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            margin-bottom: 16px;
        }
    }
</style>

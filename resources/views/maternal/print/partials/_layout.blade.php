<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} - BHCIS Sta. Ana</title>
    @include('consultations.handout.partials._form-styles')
</head>
<body class="preview-body text-black">
    <div class="no-print sticky top-0 z-10 border-b border-gray-300 bg-white px-4 py-3" style="font-family: system-ui, sans-serif;">
        <div style="max-width:760px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:8px;justify-content:space-between;">
            <div>
                <p style="font-size:14px;font-weight:600;color:#1f2937;margin:0;">BHCIS Sta. Ana - {{ $pageTitle }}</p>
                <p style="font-size:12px;color:#6b7280;margin:0;">{{ $subtitle ?? '' }}</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" onclick="window.print()"
                        style="border-radius:8px;background:#065f46;color:#fff;border:0;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                    Print preview
                </button>
                <a href="{{ $backUrl ?? 'javascript:history.back()' }}"
                   style="border-radius:8px;border:1px solid #064e3b;color:#064e3b;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none;">
                    Back
                </a>
            </div>
        </div>
    </div>

    <main style="padding:12px 8px;">
        <div class="iclinic-sheet">
            @include($sheet)
        </div>
    </main>
</body>
</html>

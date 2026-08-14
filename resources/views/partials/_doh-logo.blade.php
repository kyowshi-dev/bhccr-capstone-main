{{--
    Shared DOH brand block (logo + Republic of the Philippines / Department of Health).
    Used by DOH print forms (iClinicSys headers, immunization record card).
    Styled by each page's own stylesheet via the .doh-* classes.
--}}
@php
    $logoPath = public_path('img/Department_of_Health_(DOH)_PHL.svg.webp');
    if (! file_exists($logoPath)) {
        $logoPath = public_path('img/logo.svg');
    }
    $logoMime = match (pathinfo($logoPath, PATHINFO_EXTENSION)) {
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        default => 'image/jpeg',
    };
    $logoSrc = "data:{$logoMime};base64," . base64_encode((string) file_get_contents($logoPath));
@endphp

<div class="doh-header-brand">
    <div class="doh-logo-wrap">
        <div class="logo-circle" style="border:none;">
            <img src="{{ $logoSrc }}" alt="DOH">
        </div>
    </div>
    <div class="doh-brand">
        <p class="rep">Republic of the Philippines</p>
        <p class="dept">Department of Health</p>
        <p class="dept-fil">Kagawaran ng Kalusugan</p>
    </div>
</div>

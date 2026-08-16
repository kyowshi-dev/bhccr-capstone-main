<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iClinicSys Handout - C{{ str_pad($consultation->id, 4, '0', STR_PAD_LEFT) }}</title>
    @include('consultations.handout.partials._form-styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="preview-body text-black" x-data="{ showEnrollment: true, showItr: true, showItr2: true }">
    <div class="no-print sticky top-0 z-10 border-b border-gray-300 bg-white px-4 py-3" style="font-family: system-ui, sans-serif;">
        <div style="max-width:760px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:8px;justify-content:space-between;">
            <div>
                <p style="font-size:14px;font-weight:600;color:#1f2937;margin:0;">iClinicSys Consultation Handout</p>
                <p style="font-size:12px;color:#6b7280;margin:0;">
                    C{{ str_pad($consultation->id, 4, '0', STR_PAD_LEFT) }}
                    {{ fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null) }}
                </p>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#374151;cursor:pointer;">
                    <input type="checkbox" x-model="showEnrollment">
                    Form 1 - Patient Enrollment
                </label>
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#374151;cursor:pointer;">
                    <input type="checkbox" x-model="showItr">
                    Form 2 - Individual Treatment Record
                </label>
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#374151;cursor:pointer;">
                    <input type="checkbox" x-model="showItr2">
                    Form 2 (Page 2) - ITR 2
                </label>
                <button type="button" onclick="window.print()"
                        style="border-radius:8px;background:#065f46;color:#fff;border:0;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                    Print preview
                </button>
                <a href="{{ route('consultations.show', $consultation->id) }}"
                   style="border-radius:8px;border:1px solid #064e3b;color:#064e3b;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none;">
                    Back to consultation
                </a>
            </div>
        </div>
    </div>

    <main style="padding:12px 8px;">
        <div class="iclinic-sheet" x-show="showEnrollment" x-cloak>
            @include('consultations.handout.partials.patient-enrollment', [
                'patient' => $patient,
                'age' => $age,
            ])
        </div>

        <div class="iclinic-sheet" x-show="showItr" x-cloak>
            @include('consultations.handout.partials.itr', [
                'patient' => $patient,
                'consultation' => $consultation,
                'diagnoses' => $diagnoses,
                'prescriptions' => $prescriptions,
                'vitals' => $vitals,
                'age' => $age,
                'consultationAt' => $consultationAt,
                'attendingProvider' => $attendingProvider,
            ])
        </div>

        <div class="iclinic-sheet" x-show="showItr2" x-cloak>
            @include('consultations.handout.partials.itr2', [
                'pregnancy' => $pregnancy,
                'prenatalVisits' => $prenatalVisits,
                'postnatalRecord' => $postnatalRecord,
                'fpClient' => $fpClient,
                'immunizations' => $immunizations,
            ])
        </div>
    </main>
</body>
</html>

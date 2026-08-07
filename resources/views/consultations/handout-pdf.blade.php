<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iClinicSys Handout — C{{ str_pad($consultation->id, 4, '0', STR_PAD_LEFT) }}</title>
    @include('consultations.handout.partials._form-styles')
</head>
<body>
    <div class="iclinic-sheet">
        @include('consultations.handout.partials.patient-enrollment', [
            'patient' => $patient,
            'age' => $age,
        ])
    </div>

    <div class="iclinic-sheet">
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

    <div class="iclinic-sheet">
        @include('consultations.handout.partials.itr2', [
            'pregnancy' => $pregnancy,
            'prenatalVisits' => $prenatalVisits,
            'postnatalRecord' => $postnatalRecord,
            'fpClient' => $fpClient,
            'immunizations' => $immunizations,
        ])
    </div>
</body>
</html>

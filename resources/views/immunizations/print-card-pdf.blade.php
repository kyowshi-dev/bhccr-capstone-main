<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Immunization Record - {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</title>
    @include('immunizations.partials._card-styles')
</head>
<body>
    @include('immunizations.partials._immunization-card', [
        'patient' => $patient,
        'records' => $records,
        'vaccines' => $vaccines,
    ])
</body>
</html>

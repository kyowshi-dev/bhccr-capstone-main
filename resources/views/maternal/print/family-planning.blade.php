@include('maternal.print.partials._layout', [
    'pageTitle' => 'Family Planning Client Record',
    'subtitle' => fullName($client->patient->last_name, $client->patient->first_name, $client->patient->middle_name, $client->patient->suffix),
    'backUrl' => route('maternal.family-planning.patient', $client->patient_id),
    'sheet' => 'maternal.print.partials._fp_sheet',
])
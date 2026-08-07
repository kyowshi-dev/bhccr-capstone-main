@include('maternal.print.partials._layout', [
    'pageTitle' => 'Prenatal Record',
    'subtitle' => fullName($pregnancy->patient->last_name, $pregnancy->patient->first_name, $pregnancy->patient->middle_name, $pregnancy->patient->suffix),
    'backUrl' => route('maternal.prenatal.patient', $pregnancy->patient_id),
    'sheet' => 'maternal.print.partials._prenatal_sheet',
])
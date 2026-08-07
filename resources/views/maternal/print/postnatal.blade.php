@include('maternal.print.partials._layout', [
    'pageTitle' => 'Postpartum Care Record',
    'subtitle' => fullName($record->patient->last_name, $record->patient->first_name, $record->patient->middle_name, $record->patient->suffix),
    'backUrl' => route('maternal.postnatal.patient', $record->patient_id),
    'sheet' => 'maternal.print.partials._postnatal_sheet',
])
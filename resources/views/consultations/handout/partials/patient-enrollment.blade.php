{{--
    Patient Enrollment Section - iClinicSys FORM 1
--}}
@php
    use Illuminate\Support\Str;

    $dob = $patient?->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth) : null;
    $dobFormatted = $dob?->format('m/d/Y') ?? '';
    $isFemale = ($patient->sex ?? '') === 'Female';
    $isMale = ($patient->sex ?? '') === 'Male';
    $civilStatus = $patient->civil_status ?? '';
    $education = $patient->educational_attainment ?? '';
    $employment = Str::lower($patient->employment_status ?? '');
    $relationship = $patient->family_relationship ?? '';
    $isPhilhealth = ($patient->is_philhealth_member ?? 'n') === 'y';
    $isPcb = ($patient->is_pcb_member ?? 'n') === 'y';
    $hasNhts = (bool) ($patient->has_nhts ?? false);
    $has4ps = (bool) ($patient->has_4ps ?? false);
    $philhealthCategory = str_replace('–', '-', $patient->membership_category ?? '');
    $statusType = $patient->status_type ?? '';
    $contactNumber = $patient->household_contact_number ?? '';
    $householdNo = $patient->household_record_id ?? '';
@endphp

<section class="iclinic-form" aria-label="Patient Enrolment Record">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'PATIENT ENROLMENT RECORD',
        'serialDigits' => 5,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="12" class="section-header">I. Patient Information (Impormasyon ng Pasyente)</td>
        </tr>
        <tr>
            <td colspan="6" style="width:52%;">
                <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                <p class="field-value text-bold">{{ $patient->last_name ?? '' }}</p>
            </td>
            <td colspan="6" style="width:48%;">
                <p class="field-label">Suffix <span class="field-help">(e.g. Jr., Sr., II, III)</span></p>
                <p class="field-value">{{ $patient->suffix ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="6" style="padding:0;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="12">
                            <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                            <p class="field-value text-bold">{{ $patient->first_name ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Middle Name <span class="field-help">(Gitnang Pangalan)</span></p>
                            <p class="field-value">{{ $patient->middle_name ?? '' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="6">
                <p class="field-help">Please write Maiden Name (for married women)</p>
                <p class="field-help">Pangalan sa pagkadalaga (para sa mga babaeng may-asawa)</p>
                <p class="field-value">&nbsp;</p>
            </td>
        </tr>
        <tr>
            <td colspan="6" style="padding:0;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="4" style="width:34%;">
                            <p class="field-label">Sex <span class="field-help">(Kasarian)</span></p>
                        </td>
                        <td colspan="4">
                            @include('consultations.handout.partials._mark', ['checked' => $isFemale, 'label' => 'Female (Babae)'])
                        </td>
                        <td colspan="4">
                            @include('consultations.handout.partials._mark', ['checked' => $isMale, 'label' => 'Male (Lalaki)'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Birth Date <span class="field-help">(Kapanganakan)</span></p>
                        </td>
                        <td colspan="8">
                            <p class="field-value">{{ $dobFormatted }} <span class="field-help">(mm/dd/yyyy)</span></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Birthplace <span class="field-help">(Lugar ng Kapanganakan)</span></p>
                        </td>
                        <td colspan="8">
                            <p class="field-value">{{ $patient->birth_place ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Blood Type</p>
                        </td>
                        <td colspan="8">
                            <p class="field-value">{{ $patient->blood_type ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Civil Status <span class="field-help">(Kayangang Sibil)</span></p>
                        </td>
                        <td colspan="8" style="padding:2px 3px;">
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Single', 'label' => 'Single (Walang Asawa)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => in_array($civilStatus, ['Widowed', 'Widow/er'], true), 'label' => 'Widow/er (Balo)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Married', 'label' => 'Married (May Asawa)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Separated', 'label' => 'Separated (Hiwalay)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Annulled', 'label' => 'Annulled (Anulado)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => in_array($civilStatus, ['Common Law', 'Co-Habitation'], true), 'label' => 'Co-Habitation (Paninirahang magkasama)', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Spouse's Name <span class="field-help">(Asawa)</span></p>
                        </td>
                        <td colspan="8">
                            <p class="field-value">{{ $patient->spouse_name ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Educational Attainment <span class="field-help">(Antas ng Edukasyon)</span></p>
                        </td>
                        <td colspan="8" style="padding:2px 3px;">
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => in_array($education, ['None', 'No Formal Education'], true), 'label' => 'No Formal Education (Walang Pormal na Edukasyon)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Elementary', 'label' => 'Elementary (Elementarya)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'High School', 'label' => 'High School (Mayskul)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Vocational', 'label' => 'Vocational (Bokasyonal)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'College', 'label' => 'College (Kolehiyo)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Post Graduate', 'label' => 'Post Graduate', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Employment Status <span class="field-help">(Katayuan sa Pagtatrabaho)</span></p>
                        </td>
                        <td colspan="8" style="padding:2px 3px;">
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'student'), 'label' => 'Student', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'unknown'), 'label' => 'Unknown (Hindi malaman)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'employ'), 'label' => 'Employed (May trabaho)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'retir'), 'label' => 'Retired (Retirado)', 'inline' => false])
                                @include('consultations.handout.partials._mark', [
                                    'checked' => in_array($employment, ['none', 'unemployed', 'none/unemployed', '']) || str_contains($employment, 'unemploy'),
                                    'label' => 'None/Unemployed (Walang Trabaho)',
                                    'inline' => false,
                                ])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <p class="field-label">Family Member <span class="field-help">(Posisyon sa Pamilya)</span></p>
                        </td>
                        <td colspan="8" style="padding:2px 3px;">
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Father', 'label' => 'Father (Ama)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Mother', 'label' => 'Mother (Ina)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Son', 'label' => 'Son (Anak na lalaki)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Daughter', 'label' => 'Daughter (Anak na babae)', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Others', 'label' => 'Others (Iba)', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="6" style="padding:0;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Mother's Name <span class="field-help">(Pangalan ng Ina)</span></p>
                            <p class="field-value">{{ $patient->mother_name ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" style="min-height:56px;">
                            <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                            <p class="field-value whitespace-pre">{{ $patient->residential_address ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Contact Number</p>
                            <p class="field-value">{{ $contactNumber }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">DSWD NHTS?</p>
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $hasNhts, 'label' => 'Yes'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => ! $hasNhts, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Facility Household No.</p>
                            <p class="field-value">{{ $householdNo ? 'HH'.str_pad((string) $householdNo, 4, '0', STR_PAD_LEFT) : '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">4Ps Member?</p>
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $has4ps, 'label' => 'Yes'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => ! $has4ps, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Household No.</p>
                            <p class="field-value">{{ $householdNo }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">PhilHealth Member?</p>
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $isPhilhealth, 'label' => 'Yes'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => ! $isPhilhealth, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">Status Type:</p>
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $statusType === 'Member', 'label' => 'Member'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $statusType === 'Dependent', 'label' => 'Dependent'])
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">PhilHealth No.</p>
                            <p class="field-value">{{ $patient->philhealth_no ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">If Member, please indicate category</p>
                        </td>
                        <td colspan="6" style="padding:2px 3px;">
                            <div class="marks-stack">
                                @include('consultations.handout.partials._mark', ['checked' => $philhealthCategory === 'FE - Private', 'label' => 'FE – Private:', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $philhealthCategory === 'FE - Government', 'label' => 'FE – Government:', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $philhealthCategory === 'IE', 'label' => 'IE:', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $philhealthCategory === 'Others', 'label' => 'Others:', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p class="field-label">Primary Care Benefit (PCB) Member?</p>
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => $isPcb, 'label' => 'Yes'])
                        </td>
                        <td colspan="3">
                            @include('consultations.handout.partials._mark', ['checked' => ! $isPcb, 'label' => 'No'])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="12" class="section-header">II. Patient's Consent (Pahintulot ng Pasyente)</td>
        </tr>
        <tr>
            <td colspan="6" style="padding:0; vertical-align:top;">
                <div class="consent-col-title">IN ENGLISH</div>
                <div class="consent-text">
                    <p>I have read and understood the <em>Patient's Information</em> after I have been made aware of its contents. During an informational conversation I was informed in a very comprehensible way about the essence and importance of the Integrated Clinic Information System (iClinicSys) by the CHU/RHU representative. All my questions during the conversation were answered sufficiently and I had been given enough time to decide on this.</p>
                    <p>Furthermore, I permit the CHU/RHU to encode the information concerning my person and the collected data regarding disease symptoms and consultations for said information system.</p>
                    <p>I wish to be informed about the medical results concerning me personally or my direct descendants. Also, I can cancel my consent at the CHU/RHU any time without giving reasons and without concerning any disadvantage for my medical treatment.</p>
                </div>
            </td>
            <td colspan="6" style="padding:0; vertical-align:top;">
                <div class="consent-col-title">SA FILIPINO</div>
                <div class="consent-text">
                    <p>Aking nabasa at naintindihan ang Impormasyon ng Pasyente matapos akoy bigyang-kaalaman ng mga nalalaman nito. Sa isang pag-uusap kasama ang kinatawan ng CHU/RHU, ako ay bingyang-paunawa nang mahusay tungkol sa kakanyahan at kahalagahan ng Integrated Clinic Information System (iClinicSys). Lahat ng aking mga katanungan sa panahon ng pag-uusap ay nasagot ng sapat at ako ay binigyan ng sapat na oras upang magpasya nito.</p>
                    <p>Higit pa rito, pinapayagan ko ang CHU/RHU upang i-encode ang mga impormasyon patungkol sa akin at mga nakolektang impormasyon tungkol sa mga sintomas ng aking sakit at konsultasyon kaagnay dito para sa nasabing information system.</p>
                    <p>Nais kong malaman at malapalaam sa aking direktang kapamilya ang aking mga medikal na resulta. Gayundin, msan kong kanselahin ang aking pahintulot sa CHU/RHU anumang oras na walang ibinibigay na dahilan at walang kinaiaman sa anumang kawalan para sa aking medikal na pagpapagamot.</p>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="6" class="text-center" style="min-height:48px;">
                <div class="sig-line"></div>
                <p class="field-label text-upper">Signature of Patient / Date</p>
                <p class="field-help text-upper">Pirma ng Pasyente / Petsa</p>
            </td>
            <td colspan="6" class="text-center" style="min-height:48px;">
                <div class="sig-line"></div>
                <p class="field-label text-upper">Name of CHU/RHU Representative</p>
                <p class="field-help text-upper">Kinatawan ng CHU / RHU</p>
            </td>
        </tr>
    </table>

    <div class="form-footer form-footer-flex">
        <span>Clinic Information System</span>
        <span>| FORM 1 |</span>
        <span>Page 1</span>
    </div>
</section>

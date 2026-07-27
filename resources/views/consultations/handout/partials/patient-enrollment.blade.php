{{--
    Patient Enrollment Section — iClinicSys FORM 1
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
    $philhealthCategory = $patient->membership_category ?? '';
    $statusType = $patient->status_type ?? '';
    $contactNumber = $patient->household_contact_number ?? '';
    $householdNo = $patient->household_record_id ?? $patient->household_id ?? '';
@endphp

<section class="iclinic-form" aria-label="Patient Enrolment Record">
    @include('consultations.handout.partials._doh-header', [
        'formTitle' => 'PATIENT ENROLMENT RECORD',
        'serialDigits' => 4,
        'patient' => $patient,
    ])

    <table class="form-table" style="border-top:0;">
        <tr>
            <td colspan="12" class="section-header">I. Patient Information (Impormasyon ng Pasyente)</td>
        </tr>
        <tr>
            <td colspan="8" style="width:66%;">
                <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                <p class="field-value text-bold">{{ $patient->last_name ?? '' }}</p>
            </td>
            <td colspan="4" style="width:34%;">
                <p class="field-label">Suffix <span class="field-help">(e.g. Jr., Sr., II, III)</span></p>
                <p class="field-value">{{ $patient->suffix ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="6">
                <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                <p class="field-value text-bold">{{ $patient->first_name ?? '' }}</p>
            </td>
            <td colspan="6">
                <p class="field-label">Maiden Name <span class="field-help">(for married women)</span></p>
                <p class="field-value">&nbsp;</p>
            </td>
        </tr>
        <tr>
            <td colspan="6">
                <p class="field-label">Middle Name <span class="field-help">(Gitnang Pangalan)</span></p>
                <p class="field-value">{{ $patient->middle_name ?? '' }}</p>
            </td>
            <td colspan="6">
                <p class="field-label">Mother's Name <span class="field-help">(Pangalan ng Ina)</span></p>
                <p class="field-value">{{ $patient->mother_name ?? '' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="8" style="padding:0; vertical-align:top;">
                <table class="form-table nested-table" style="border:0;">
                    <tr>
                        <td colspan="5" style="width:42%;">
                            <p class="field-label">Sex <span class="field-help">(Kasarian)</span></p>
                            @include('consultations.handout.partials._mark', ['checked' => $isFemale, 'label' => 'Female (Babae)'])
                            @include('consultations.handout.partials._mark', ['checked' => $isMale, 'label' => 'Male (Lalaki)'])
                        </td>
                        <td colspan="7">
                            <p class="field-label">Birth Date <span class="field-help">(mm/dd/yyyy)</span></p>
                            <p class="field-value">{{ $dobFormatted }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Birthplace <span class="field-help">(Lugar ng Kapanganakan)</span></p>
                            <p class="field-value">{{ $patient->birth_place ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Blood Type</p>
                            <p class="field-value">{{ $patient->blood_type ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Civil Status</p>
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Single', 'label' => 'Single', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Married', 'label' => 'Married', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => false, 'label' => 'Annulled', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => in_array($civilStatus, ['Widowed', 'Widow/er'], true), 'label' => 'Widow/er', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $civilStatus === 'Separated', 'label' => 'Separated', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => in_array($civilStatus, ['Common Law', 'Co-Habitation'], true), 'label' => 'Co-Habitation', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Spouse's Name <span class="field-help">(Asawa)</span></p>
                            <p class="field-value">{{ $patient->spouse_name ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Educational Attainment</p>
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => in_array($education, ['None', 'No Formal Education'], true), 'label' => 'No Formal Education', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'High School', 'label' => 'High School', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'College', 'label' => 'College', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Elementary', 'label' => 'Elementary', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Vocational', 'label' => 'Vocational', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $education === 'Post Graduate', 'label' => 'Post Graduate', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <p class="field-label">Employment Status</p>
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'student'), 'label' => 'Student', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'employ'), 'label' => 'Employed', 'inline' => false])
                                @include('consultations.handout.partials._mark', [
                                    'checked' => in_array($employment, ['none', 'unemployed', 'none/unemployed', '']) || str_contains($employment, 'unemploy'),
                                    'label' => 'None/Unemployed',
                                    'inline' => false,
                                ])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'unknown'), 'label' => 'Unknown', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => str_contains($employment, 'retir'), 'label' => 'Retired', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" style="border-bottom:0;">
                            <p class="field-label">Family Member <span class="field-help">(Kasapi ng Pamilya)</span></p>
                            <div class="marks-2col">
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Father', 'label' => 'Father', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Son', 'label' => 'Son', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Mother', 'label' => 'Mother', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Daughter', 'label' => 'Daughter', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $relationship === 'Others', 'label' => 'Others', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="4" style="padding:0; vertical-align:top;">
                <table class="form-table nested-table" style="border:0; height:100%;">
                    <tr>
                        <td style="min-height:72px; border-top:0; border-right:0;">
                            <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                            <p class="field-value whitespace-pre">{{ $patient->residential_address ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">Contact Number</p>
                            <p class="field-value">{{ $contactNumber }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">DSWD NHTS?</p>
                            @include('consultations.handout.partials._mark', ['checked' => $hasNhts, 'label' => 'Yes'])
                            @include('consultations.handout.partials._mark', ['checked' => ! $hasNhts, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">Facility Household No.</p>
                            <p class="field-value">{{ $householdNo ? 'HH'.str_pad($householdNo, 4, '0', STR_PAD_LEFT) : '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">4Ps Member?</p>
                            @include('consultations.handout.partials._mark', ['checked' => $has4ps, 'label' => 'Yes'])
                            @include('consultations.handout.partials._mark', ['checked' => ! $has4ps, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">Household No.</p>
                            <p class="field-value">{{ $householdNo }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">PhilHealth Member?</p>
                            @include('consultations.handout.partials._mark', ['checked' => $isPhilhealth, 'label' => 'Yes'])
                            @include('consultations.handout.partials._mark', ['checked' => ! $isPhilhealth, 'label' => 'No'])
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">Status Type</p>
                            <div class="marks-stack">
                                @include('consultations.handout.partials._mark', ['checked' => $statusType === 'Member', 'label' => 'Member', 'inline' => false])
                                @include('consultations.handout.partials._mark', ['checked' => $statusType === 'Dependent', 'label' => 'Dependent', 'inline' => false])
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">PhilHealth No.</p>
                            <p class="field-value">{{ $patient->philhealth_no ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0;">
                            <p class="field-label">PhilHealth Category</p>
                            <div class="marks-stack">
                                @foreach (['FE - Private', 'FE - Government', 'IE', 'Others'] as $category)
                                    @include('consultations.handout.partials._mark', ['checked' => $philhealthCategory === $category, 'label' => $category, 'inline' => false])
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right:0; border-bottom:0;">
                            <p class="field-label">Primary Care Benefit (PCB) Member?</p>
                            @include('consultations.handout.partials._mark', ['checked' => $isPcb, 'label' => 'Yes'])
                            @include('consultations.handout.partials._mark', ['checked' => ! $isPcb, 'label' => 'No'])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="12" class="section-header" style="border-top:0;">II. Patient's Consent (Pahintulot ng Pasyente)</td>
        </tr>
        <tr>
            <td colspan="6" style="padding:0; vertical-align:top;">
                <div class="consent-col-title">IN ENGLISH</div>
                <div class="consent-text">
                    <p>I hereby voluntarily give my consent to the collection, use, storage, and processing of my personal health information by the Department of Health and its authorized health facilities under the iClinicSys program.</p>
                    <p>I understand that my information shall be used for treatment, health care delivery, program monitoring, and reporting in accordance with applicable laws and issuances on data privacy.</p>
                    <p>I acknowledge that I have been informed of my rights regarding my health information and that I may withdraw this consent subject to legal and operational limitations.</p>
                </div>
            </td>
            <td colspan="6" style="padding:0; vertical-align:top;">
                <div class="consent-col-title">SA FILIPINO</div>
                <div class="consent-text">
                    <p>Kusang loob kong pinahihintulutan ang pagkolekta, paggamit, pag-iimbak, at pagproseso ng aking personal na impormasyong pangkalusugan ng Kagawaran ng Kalusugan at mga awtorisadong pasilidad nito sa ilalim ng iClinicSys.</p>
                    <p>Nauunawaan ko na gagamitin ang aking impormasyon para sa paggamot, paghahatid ng serbisyong pangkalusugan, pagmo-monitor ng programa, at pag-uulat alinsunod sa mga batas at alituntunin ukol sa privacy ng datos.</p>
                    <p>Kinikilala ko na ipinaliwanag sa akin ang aking mga karapatan hinggil sa aking impormasyong pangkalusugan at maaari kong bawiin ang pahintulot na ito alinsunod sa mga legal at operasyonal na limitasyon.</p>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="6" style="min-height:48px;">
                <p class="field-label text-upper">Signature of Patient / Date</p>
                <p class="field-help" style="font-style:italic;">Pirma ng Pasyente / Petsa</p>
                <div class="sig-line"></div>
            </td>
            <td colspan="6" style="min-height:48px;">
                <p class="field-label text-upper">Name of CHU/RHU Representative</p>
                <p class="field-help" style="font-style:italic;">Kinatawan ng CHU / RHU</p>
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>

    <div class="form-footer text-right">
        Clinic Information System | FORM 1
    </div>
</section>

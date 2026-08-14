{{--
    Shared DOH / iClinicSys form header.
    $formTitle - e.g. PATIENT ENROLMENT RECORD
    $serialDigits - number of digit boxes for Family Serial Number (default 5)
--}}
@php
    $formTitle ??= 'FORM';
    $serialDigits = (int) ($serialDigits ?? 5);
    $serialChars = array_fill(0, $serialDigits, '');
    $facilityChars = array_fill(0, $serialDigits, '');
@endphp

<table class="form-table" style="border-bottom:0;">
    <tr>
        <td style="width:52%; padding:3px 5px; vertical-align:middle;">
            @include('partials._doh-logo')
        </td>
        <td style="padding:0; width:48%;">
            <table class="form-table nested-table" style="border:0; width:100%; border-collapse:collapse;">
                <tr>
                    <td class="label-cell" style="width:50%; text-align:center;">Family Serial Number</td>
                    <td class="label-cell" style="width:50%; text-align:center;">Facility Code</td>
                </tr>
                <tr>
                    <td style="padding:0;">
                        <table class="digit-row form-table" style="border:0; width:100%; height:100%; border-collapse:collapse;">
                            <tr>
                                @foreach ($serialChars as $digit)
                                    <td class="digit-box">{!! $digit ?: '&nbsp;' !!}</td>
                                @endforeach
                            </tr>
                        </table>
                    </td>
                    <td style="padding:0;">
                        <table class="digit-row form-table" style="border:0; width:100%; height:100%; border-collapse:collapse;">
                            <tr>
                                @foreach ($facilityChars as $char)
                                    <td class="digit-box">{!! $char ?: '&nbsp;' !!}</td>
                                @endforeach
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="form-header-block">
    <p class="title-caption">Integrated Clinic Information System (iCLINICSYS)</p>
    <h1>{{ $formTitle }}</h1>
</div>

<div class="instructions">
    @if (($formTitle ?? '') === 'PATIENT ENROLMENT RECORD')
        <strong>Instructions:</strong> For new patient only. Please print legibly and mark appropriate boxes with "X".
        <span style="font-style:normal;">/</span>
        <strong>Tagubilin:</strong> Para sa bagong pasyente lamang. Sulatan nang malinaw at lagyan ng "X" ang naaangkop na kahon.
    @else
        <strong>Instructions:</strong> Please print legibly and mark appropriate boxes with "X".
        <span style="font-style:normal;">/</span>
        <strong>Tagubilin:</strong> Sulatan nang malinaw at lagyan ng "X" ang naaangkop na kahon.
    @endif
</div>

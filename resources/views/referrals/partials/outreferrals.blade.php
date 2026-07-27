<div class="mb-5 lg:mb-6" aria-live="polite">
    <div class="flex items-center justify-between gap-3 mb-2">
        <p id="outwardReferralWizardStepLabel" class="text-xs lg:text-sm font-semibold" style="color: var(--ink);">Step 1 of 3</p>
        <p id="outwardReferralWizardStepName" class="text-xs" style="color: var(--ink-muted);">Referral Details</p>
    </div>
    <div class="h-1.5 rounded-full overflow-hidden" style="background: var(--teal-soft);">
        <div id="outwardReferralWizardProgressBar" class="h-full rounded-full transition-all duration-300 ease-out" style="width: 33%; background: var(--primary);"></div>
    </div>
</div>

<form id="outwardReferralWizardForm" class="space-y-4 lg:space-y-5" novalidate>
    <input type="hidden" name="patient_id" id="outwardReferralWizardPatientId" value="{{ $patient->id }}">

    <div id="outwardReferralWizardStep1" data-wizard-step="1" class="outward-referral-wizard-step" aria-hidden="false">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-6">
            <div class="space-y-5">
                <div>
                    <label for="outward_referred_to" class="block text-xs font-medium mb-1.5" style="color: var(--ink-muted);">
                        Destination facility <span style="color: #b91c1c;">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="referred_to"
                            id="outward_referred_to"
                            list="outwardReferralDestinations"
                            autocomplete="off"
                            placeholder="Search or select receiving facility…"
                            class="w-full rounded-md border py-2.5 pl-9 pr-3 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                            required
                        >
                        <datalist id="outwardReferralDestinations">
                            @foreach ($destinationFacilities as $facility)
                                <option value="{{ $facility }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <p class="mt-1.5 text-[11px] leading-relaxed" style="color: var(--ink-subtle);">
                        Select the RHU, district hospital, or higher-level facility that will receive this referral.
                    </p>
                </div>

                <fieldset>
                    <legend class="block text-xs font-medium mb-2" style="color: var(--ink-muted);">
                        Reasons for referral <span style="color: #b91c1c;">*</span>
                    </legend>
                    <div class="space-y-2.5 rounded-md border p-3.5" style="border-color: var(--border); background: var(--bg-surface);">
                        @foreach ($referralReasonOptions as $value => $label)
                            <label class="flex items-start gap-2.5 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    name="referral_reasons[]"
                                    value="{{ $value }}"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border"
                                    style="border-color: var(--border); accent-color: var(--primary);"
                                >
                                <span class="text-sm leading-snug group-hover:opacity-90" style="color: var(--ink);">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label for="outward_referral_reason_details" class="block text-xs font-medium mb-1.5" style="color: var(--ink-muted);">
                            Specific details
                        </label>
                        <textarea
                            name="referral_reason_details"
                            id="outward_referral_reason_details"
                            rows="3"
                            placeholder="Add clinical justification, urgency level, or other DOH-required notes…"
                            class="w-full rounded-md border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 resize-y min-h-[5rem]"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                        ></textarea>
                    </div>
                </fieldset>
            </div>

            <div class="space-y-5 flex flex-col min-h-0">
                <div class="flex flex-col h-full min-h-0">
                    <label for="outward_pertinent_history" class="block text-xs font-medium mb-1.5" style="color: var(--ink-muted);">
                        Pertinent history of illness <span style="color: #b91c1c;">*</span>
                    </label>
                    <textarea
                        name="pertinent_history"
                        id="outward_pertinent_history"
                        rows="1"
                        placeholder="e.g., Patient presented with high-grade fever (39°C) for 4 days, persistent dry cough, and loss of appetite. No rashes observed."
                        class="w-full flex-1 rounded-md border px-3 py-2.5 text-sm leading-relaxed focus:outline-none focus:ring-2 resize-none min-h-0"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                        required
                    ></textarea>
                    <p class="mt-1.5 text-[11px] leading-relaxed" style="color: var(--ink-subtle);">
                        Document onset, duration, progression of symptoms, and relevant negatives or positives.
                    </p>
                </div>

                <div class="flex flex-col h-full min-h-0">
                    <label for="outward_actions_taken" class="block text-xs font-medium mb-1.5" style="color: var(--ink-muted);">
                        Actions taken
                    </label>
                    <textarea
                        name="actions_taken"
                        id="outward_actions_taken"
                        rows="6"
                        placeholder="e.g., Administered Paracetamol 500mg, cold compress applied, vitals monitored, and hydration encouraged."
                        class="w-full flex-1 rounded-md border px-3 py-2.5 text-sm leading-relaxed focus:outline-none focus:ring-2 resize-none min-h-0"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                    ></textarea>
                    <p class="mt-1.5 text-[11px] leading-relaxed" style="color: var(--ink-subtle);">
                        Record first aid, nursing care, medicines given, and monitoring done before referral.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="outwardReferralWizardStep2" data-wizard-step="2" class="outward-referral-wizard-step hidden" aria-hidden="true">
        <div class="rounded-xl border px-3.5 py-3 mb-5 text-xs leading-relaxed" style="border-color: var(--border); background: var(--teal-soft); color: var(--primary);">
            <i class="fa-solid fa-eye mr-1.5" aria-hidden="true"></i>
            Preview the referral summary below. Tap any field to edit inline — changes sync back to Step 1 automatically.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-6">
            <div class="space-y-4">
                <div class="outward-referral-preview-field rounded-md border p-3.5 transition-colors focus-within:ring-2" style="border-color: var(--border); background: var(--bg-surface); --tw-ring-color: var(--primary);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Destination facility</span>
                        <span class="text-[10px] font-medium inline-flex items-center gap-1" style="color: var(--ink-subtle);">
                            <i class="fa-solid fa-pen text-[9px]" aria-hidden="true"></i> Tap to edit
                        </span>
                    </div>
                    <div class="relative">
                        <input
                            type="text"
                            id="outward_preview_referred_to"
                            data-preview-field="referred_to"
                            data-preview-source="outward_referred_to"
                            list="outwardReferralDestinationsPreview"
                            autocomplete="off"
                            class="w-full rounded-md border-0 bg-transparent px-0 py-1 text-sm font-medium focus:outline-none focus:ring-0 border-b border-transparent focus:border-[var(--border)]"
                            style="color: var(--ink);"
                        >
                        <datalist id="outwardReferralDestinationsPreview">
                            @foreach ($destinationFacilities as $facility)
                                <option value="{{ $facility }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="outward-referral-preview-field rounded-md border p-3.5 transition-colors focus-within:ring-2" style="border-color: var(--border); background: var(--bg-surface); --tw-ring-color: var(--primary);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Reasons for referral</span>
                        <span class="text-[10px] font-medium inline-flex items-center gap-1" style="color: var(--ink-subtle);">
                            <i class="fa-solid fa-pen text-[9px]" aria-hidden="true"></i> Tap to edit
                        </span>
                    </div>
                    <div id="outward_preview_reasons_empty" class="hidden text-sm italic py-1" style="color: var(--ink-subtle);">No reasons selected</div>
                    <div class="space-y-2" id="outward_preview_reasons_list">
                        @foreach ($referralReasonOptions as $value => $label)
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    data-preview-field="referral_reasons"
                                    data-preview-source="referral_reasons"
                                    data-reason-value="{{ $value }}"
                                    value="{{ $value }}"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border"
                                    style="border-color: var(--border); accent-color: var(--primary);"
                                >
                                <span class="text-sm leading-snug" style="color: var(--ink);">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="outward-referral-preview-field rounded-md border p-3.5 transition-colors focus-within:ring-2" style="border-color: var(--border); background: var(--bg-surface); --tw-ring-color: var(--primary);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Specific details</span>
                        <span class="text-[10px] font-medium inline-flex items-center gap-1" style="color: var(--ink-subtle);">
                            <i class="fa-solid fa-pen text-[9px]" aria-hidden="true"></i> Tap to edit
                        </span>
                    </div>
                    <textarea
                        id="outward_preview_referral_reason_details"
                        data-preview-field="referral_reason_details"
                        data-preview-source="outward_referral_reason_details"
                        rows="3"
                        placeholder="No additional details provided."
                        class="w-full rounded-md border-0 bg-transparent px-0 py-1 text-sm leading-relaxed resize-y min-h-[4rem] focus:outline-none focus:ring-0 border-b border-transparent focus:border-[var(--border)]"
                        style="color: var(--ink);"
                    ></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <div class="outward-referral-preview-field rounded-md border p-3.5 transition-colors focus-within:ring-2" style="border-color: var(--border); background: var(--bg-surface); --tw-ring-color: var(--primary);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Pertinent history of illness</span>
                        <span class="text-[10px] font-medium inline-flex items-center gap-1" style="color: var(--ink-subtle);">
                            <i class="fa-solid fa-pen text-[9px]" aria-hidden="true"></i> Tap to edit
                        </span>
                    </div>
                    <textarea
                        id="outward_preview_pertinent_history"
                        data-preview-field="pertinent_history"
                        data-preview-source="outward_pertinent_history"
                        rows="4"
                        placeholder="No history recorded."
                        class="w-full rounded-md border-0 bg-transparent px-0 py-1 text-sm leading-relaxed resize-y min-h-[5.25rem] focus:outline-none focus:ring-0 border-b border-transparent focus:border-[var(--border)]"
                        style="color: var(--ink);"
                    ></textarea>
                </div>

                <div class="outward-referral-preview-field rounded-md border p-3.5 transition-colors focus-within:ring-2" style="border-color: var(--border); background: var(--bg-surface); --tw-ring-color: var(--primary);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Actions taken</span>
                        <span class="text-[10px] font-medium inline-flex items-center gap-1" style="color: var(--ink-subtle);">
                            <i class="fa-solid fa-pen text-[9px]" aria-hidden="true"></i> Tap to edit
                        </span>
                    </div>
                    <textarea
                        id="outward_preview_actions_taken"
                        data-preview-field="actions_taken"
                        data-preview-source="outward_actions_taken"
                        rows="4"
                        placeholder="No actions recorded."
                        class="w-full rounded-md border-0 bg-transparent px-0 py-1 text-sm leading-relaxed resize-y min-h-[5.25rem] focus:outline-none focus:ring-0 border-b border-transparent focus:border-[var(--border)]"
                        style="color: var(--ink);"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>

    <div id="outwardReferralWizardStep3" data-wizard-step="3" class="outward-referral-wizard-step hidden" aria-hidden="true">
        <div class="rounded-xl border px-4 py-4 mb-5 text-sm leading-relaxed" style="border-color: var(--border); background: var(--teal-soft); color: var(--primary);">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>
            Please review the referral summary below. Once confirmed, the consultation and referral will be saved.
        </div>

        <div class="rounded-xl border overflow-hidden" style="border-color: var(--border); background: var(--bg-surface);">
            <div class="px-4 py-3 border-b" style="border-color: var(--border); background: var(--bg-surface-elevated);">
                <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Patient</p>
                <p id="outward_confirm_patient_name" class="mt-1 font-semibold text-base" style="color: var(--ink);">—</p>
                <p id="outward_confirm_patient_meta" class="text-xs mt-0.5" style="color: var(--ink-muted);">—</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 md:divide-x" style="border-color: var(--border);">
                <div class="p-4 space-y-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Destination facility</p>
                        <p id="outward_confirm_referred_to" class="mt-1 text-sm font-medium" style="color: var(--ink);">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Reasons for referral</p>
                        <ul id="outward_confirm_reasons" class="mt-2 space-y-1 text-sm list-disc list-inside" style="color: var(--ink);"></ul>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Specific details</p>
                        <p id="outward_confirm_reason_details" class="mt-1 text-sm whitespace-pre-line" style="color: var(--ink);">—</p>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Pertinent history</p>
                        <p id="outward_confirm_pertinent_history" class="mt-1 text-sm whitespace-pre-line" style="color: var(--ink);">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Actions taken</p>
                        <p id="outward_confirm_actions_taken" class="mt-1 text-sm whitespace-pre-line" style="color: var(--ink);">—</p>
                    </div>
                    <div class="rounded-lg border px-3 py-2.5 text-xs" style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                        <p class="font-semibold mb-1" style="color: var(--ink);">Vitals captured</p>
                        <p id="outward_confirm_vitals" class="leading-relaxed">—</p>
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-4 text-xs leading-relaxed" style="color: var(--ink-muted);">
            After confirmation, you will be prompted to print the referral slip for the patient.
        </p>
    </div>

    <div class="sticky bottom-0 flex flex-wrap items-center justify-between gap-2 lg:gap-3 pt-1 border-t" style="border-color: var(--border); background: white; z-index: 10;">
        <button type="button" id="outwardReferralWizardBackBtn" onclick="outwardReferralWizardGoBack()" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border font-medium text-xs lg:text-sm transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
            <i class="fa-solid fa-arrow-left mr-1.5" aria-hidden="true"></i> Back
        </button>
        <div class="flex flex-wrap items-center gap-2 lg:gap-3">
            <button type="button" onclick="closeOutwardReferralWizard()" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border font-medium text-xs lg:text-sm transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="button" id="outwardReferralWizardNextBtn" onclick="outwardReferralWizardGoNext()" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-white font-semibold text-xs lg:text-sm transition hover:opacity-95" style="background: var(--primary); box-shadow: var(--shadow-sm);">
                Next
            </button>
        </div>
    </div>
</form>

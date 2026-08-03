<div class="p-4 lg:p-6" id="consultationCreateModalRoot">
    <div class="flex items-start justify-between gap-3 mb-4 lg:mb-5">
        <div>
            <h2 id="consultationCreateModalTitle" class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">New Consultation</h2>
            <p id="consultationCreateModalSubtitle" class="text-xs lg:text-sm mt-1" style="color: var(--ink-muted);">
                Attending to <span class="font-semibold" style="color: var(--ink);">{{ $patient->last_name }}, {{ ucwords($patient->first_name) }}@if($patient->suffix) {{ $patient->suffix }}@endif</span>
                (PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }})
            </p>
            <p id="consultationCreateModalMeta" class="text-xs lg:text-sm mt-0.5" style="color: var(--ink-muted);">{{ $patient->age }} y/o · {{ $patient->residential_address }}</p>
        </div>
        <button type="button" onclick="closeConsultationCreateModal()" class="shrink-0 p-2 rounded-lg transition-colors hover:bg-black/[0.04]" style="color: var(--ink-muted);" aria-label="Close">
            <i class="fa-solid fa-times" aria-hidden="true"></i>
        </button>
    </div>

    <div id="consultationCreateIntakeView">
        @if ($errors->any())
            <div class="mb-4 rounded-xl px-3 lg:px-4 py-2 lg:py-3 text-xs lg:text-sm" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('consultations.store', $patient->id) }}" method="POST" class="space-y-4 lg:space-y-5">
            @csrf
            <input type="hidden" name="modal_patient_id" value="{{ $patient->id }}">
            <input type="hidden" name="refer_to_higher_facility" id="outward_refer_to_higher_facility" value="0">
            <input type="hidden" name="referred_to" id="outward_hidden_referred_to" value="">
            <input type="hidden" name="referral_reason_details" id="outward_hidden_referral_reason_details" value="">
            <input type="hidden" name="pertinent_history" id="outward_hidden_pertinent_history" value="">
            <input type="hidden" name="actions_taken" id="outward_hidden_actions_taken" value="">
            <div id="outward_hidden_referral_reasons"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h3 class="font-semibold mb-3 lg:mb-4 pb-2 border-b text-sm lg:text-base" style="color: var(--ink); border-color: var(--border);">1. Visit details</h3>

                    <div class="mb-3 lg:mb-4">
                        <label for="mode_of_transaction" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Mode of transaction <span style="color: #b91c1c;">*</span></label>
                        <select name="mode_of_transaction" id="mode_of_transaction" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                            <option value="">Select mode...</option>
                            <option value="Walk-in" @selected(old('mode_of_transaction') === 'Walk-in')>Walk-in</option>
                            <option value="Visited" @selected(old('mode_of_transaction') === 'Visited')>Visited</option>
                            <option value="Referral" @selected(old('mode_of_transaction') === 'Referral')>Referral</option>
                        </select>
                    </div>

                    <div id="referred_from_container" class="mb-3 lg:mb-4" style="display: none;">
                        <label for="referred_from" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Referred from</label>
                        <input type="text" name="referred_from" id="referred_from" value="{{ old('referred_from') }}" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" placeholder="e.g. Rural Health Unit, Private Clinic">
                    </div>

                    <div class="mb-3 lg:mb-4">
                        <label for="nature_of_visit" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Nature of visit <span style="color: #b91c1c;">*</span></label>
                        <select name="nature_of_visit" id="nature_of_visit" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                            <option value="">Select visit type...</option>
                            <option value="New Consultation/Case" @selected(old('nature_of_visit') === 'New Consultation/Case')>New Consultation/Case</option>
                            <option value="Follow-up Visit" @selected(old('nature_of_visit') === 'Follow-up Visit')>Follow-up Visit</option>
                        </select>
                    </div>

                    <div class="mb-3 lg:mb-4">
                        <label for="purpose_of_visit" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Purpose of visit <span style="color: #b91c1c;">*</span></label>
                        <select name="purpose_of_visit" id="purpose_of_visit" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                            <option value="">Select purpose...</option>
                            <option value="General" @selected(old('purpose_of_visit') === 'General')>General</option>
                            <option value="Family Planning" @selected(old('purpose_of_visit') === 'Family Planning')>Family Planning</option>
                            <option value="Prenatal" @selected(old('purpose_of_visit') === 'Prenatal')>Prenatal</option>
                            <option value="Postpartum" @selected(old('purpose_of_visit') === 'Postpartum')>Postpartum</option>
                            <option value="Tuberculosis" @selected(old('purpose_of_visit') === 'Tuberculosis')>Tuberculosis</option>
                            <option value="Child Immunization" @selected(old('purpose_of_visit') === 'Child Immunization')>Child Immunization</option>
                            <option value="Child Nutrition" @selected(old('purpose_of_visit') === 'Child Nutrition')>Child Nutrition</option>
                            <option value="Sick Children" @selected(old('purpose_of_visit') === 'Sick Children')>Sick Children</option>
                            <option value="Firecracker Injury" @selected(old('purpose_of_visit') === 'Firecracker Injury')>Firecracker Injury</option>
                            <option value="Adult Immunization" @selected(old('purpose_of_visit') === 'Adult Immunization')>Adult Immunization</option>
                            <option value="Dogbite" @selected(old('purpose_of_visit') === 'Dogbite')>Dogbite</option>
                            <option value="Dengue" @selected(old('purpose_of_visit') === 'Dengue')>Dengue</option>
                        </select>
                    </div>

                    <div>
                        <label for="chief_complaint" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Chief Complaints</label>
                        <textarea name="chief_complaint" id="chief_complaint" rows="3" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" placeholder="e.g. Fever 3 days, cough">{{ old('chief_complaint') }}</textarea>
                    </div>
                </div>

                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h3 class="font-semibold mb-3 lg:mb-4 pb-2 border-b text-sm lg:text-base" style="color: var(--ink); border-color: var(--border);">2. Vitals</h3>

                    <div class="grid grid-cols-2 gap-3 lg:gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">BP <span style="color: #b91c1c;">*</span></label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <input type="number" name="bp_systolic" id="bp_systolic" value="{{ old('bp_systolic', $previousVitals->bp_systolic ?? '') }}" min="0" max="300" placeholder="120" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                                    <p class="text-xs mt-1 text-center" style="color: var(--ink-muted);">Systolic</p>
                                </div>
                                <span style="color: var(--ink-subtle); font-weight: 500;">/ </span>
                                <div class="flex-1">
                                    <input type="number" name="bp_diastolic" id="bp_diastolic" value="{{ old('bp_diastolic', $previousVitals->bp_diastolic ?? '') }}" min="0" max="200" placeholder="80" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                                    <p class="text-xs mt-1 text-center" style="color: var(--ink-muted);">Diastolic</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="weight" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Weight (kg) <span style="color: #b91c1c;">*</span></label>
                            <input type="number" step="0.1" name="weight" id="weight" value="{{ old('weight', $previousVitals->weight_kg ?? '') }}" min="0" max="500" placeholder="—" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                        </div>

                        <div>
                            <label for="height" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Height (cm) <span style="color: #b91c1c;">*</span></label>
                            <input type="number" step="0.1" name="height" id="height" value="{{ old('height', $previousVitals->height_cm ?? '') }}" min="0" max="300" placeholder="—" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                        </div>

                        <div>
                            <label for="temperature" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Temperature (°C) <span style="color: #b91c1c;">*</span></label>
                            <input type="number" step="0.1" name="temperature" id="temperature" value="{{ old('temperature', $previousVitals->temperature_c ?? '') }}" min="30" max="45" placeholder="36.5" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 lg:gap-3 pt-1 border-t" style="border-color: var(--border);">
                <button type="button" onclick="openOutwardReferralWizard()"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-xs lg:text-sm font-semibold transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--primary);">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    Refer to higher facility
                </button>
                
                <div class="flex flex-wrap items-center gap-2 lg:gap-3">
                    <button type="button" onclick="closeConsultationCreateModal()" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border font-medium text-xs lg:text-sm transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                    <button type="submit" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-white font-semibold text-xs lg:text-sm transition hover:opacity-95" style="background: var(--primary); box-shadow: var(--shadow-sm);">
                        Save & submit for validation
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="consultationCreateOutwardWizardView" class="hidden" aria-hidden="true">
        @include('referrals.partials.outreferrals', [
            'destinationFacilities' => [
                'Tagoloan Rural Health Unit (RHU)',
                'Tagoloan District Hospital',
                'Provincial Hospital',
                'Tagoloan Polymedic Clinic',
                'Northern Mindanao Medical Center (NMMC)',
                'Cagayan de Oro Medical Center',
                'Other facility (specify in notes)',
            ],
            'referralReasonOptions' => [
                'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
                'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
                'lack_medicines' => 'Lack of available medicines / vaccines',
                'emergency_trauma' => 'Emergency / trauma stabilization required',
            ],
            'patient' => $patient,
        ])
    </div>
</div>

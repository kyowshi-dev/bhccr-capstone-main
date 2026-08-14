<div class="space-y-5">
    <div class="space-y-4">
        <h3 class="font-semibold pb-2 border-b border-border text-sm lg:text-base text-ink">Visit details</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field name="mode_of_transaction" :id="$fieldPrefix.'mode_of_transaction'" required>
                <x-slot:label>Mode of transaction</x-slot:label>
                <x-slot:control>
                    <select name="mode_of_transaction" id="{{ $fieldPrefix }}mode_of_transaction"
                            class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                        <option value="">Select mode...</option>
                        <option value="Walk-in" @selected(old('mode_of_transaction') === 'Walk-in')>Walk-in</option>
                        <option value="Visited" @selected(old('mode_of_transaction') === 'Visited')>Visited</option>
                        <option value="Referral" @selected(old('mode_of_transaction') === 'Referral')>Referral</option>
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="nature_of_visit" :id="$fieldPrefix.'nature_of_visit'" required>
                <x-slot:label>Nature of visit</x-slot:label>
                <x-slot:control>
                    <select name="nature_of_visit" id="{{ $fieldPrefix }}nature_of_visit"
                            class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                        <option value="">Select visit type...</option>
                        <option value="New Consultation/Case" @selected(old('nature_of_visit') === 'New Consultation/Case')>New Consultation/Case</option>
                        <option value="Follow-up Visit" @selected(old('nature_of_visit') === 'Follow-up Visit')>Follow-up Visit</option>
                    </select>
                </x-slot:control>
            </x-field>
        </div>

        <x-field name="chief_complaint" :id="$fieldPrefix.'chief_complaint'">
            <x-slot:label>Chief complaints</x-slot:label>
            <x-slot:control>
                <textarea name="chief_complaint" id="{{ $fieldPrefix }}chief_complaint" rows="2"
                          class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue"
                          placeholder="e.g. Routine prenatal checkup">{{ old('chief_complaint') }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="space-y-4">
        <h3 class="font-semibold pb-2 border-b border-border text-sm lg:text-base text-ink">Vitals</h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 lg:gap-4">
            <x-field name="bp_systolic" :id="$fieldPrefix.'bp_systolic'" required>
                <x-slot:label>Systolic (mmHg)</x-slot:label>
                <x-slot:control>
                    <input type="number" name="bp_systolic" id="{{ $fieldPrefix }}bp_systolic" value="{{ old('bp_systolic') }}"
                           min="0" max="300" placeholder="120" required
                           class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                </x-slot:control>
            </x-field>

            <x-field name="bp_diastolic" :id="$fieldPrefix.'bp_diastolic'" required>
                <x-slot:label>Diastolic (mmHg)</x-slot:label>
                <x-slot:control>
                    <input type="number" name="bp_diastolic" id="{{ $fieldPrefix }}bp_diastolic" value="{{ old('bp_diastolic') }}"
                           min="0" max="200" placeholder="80" required
                           class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                </x-slot:control>
            </x-field>

            <x-field name="weight" :id="$fieldPrefix.'weight'" required>
                <x-slot:label>Weight (kg)</x-slot:label>
                <x-slot:control>
                    <input type="number" step="0.1" name="weight" id="{{ $fieldPrefix }}weight" value="{{ old('weight') }}"
                           min="0" max="500" placeholder="-" required
                           class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                </x-slot:control>
            </x-field>

            <x-field name="height" :id="$fieldPrefix.'height'" required>
                <x-slot:label>Height (cm)</x-slot:label>
                <x-slot:control>
                    <input type="number" step="0.1" name="height" id="{{ $fieldPrefix }}height" value="{{ old('height') }}"
                           min="0" max="300" placeholder="-" required
                           class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                </x-slot:control>
            </x-field>

            <x-field name="temperature" :id="$fieldPrefix.'temperature'" required>
                <x-slot:label>Temperature (&deg;C)</x-slot:label>
                <x-slot:control>
                    <input type="number" step="0.1" name="temperature" id="{{ $fieldPrefix }}temperature" value="{{ old('temperature') }}"
                           min="30" max="45" placeholder="36.5" required
                           class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                </x-slot:control>
            </x-field>
        </div>
    </div>
</div>

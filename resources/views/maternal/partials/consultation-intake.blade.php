<div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
    <h3 class="font-semibold mb-3 lg:mb-4 pb-2 border-b text-sm lg:text-base" style="color: var(--ink); border-color: var(--border);">Visit details</h3>

    <div class="mb-3 lg:mb-4">
        <label for="{{ $fieldPrefix }}mode_of_transaction" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Mode of transaction <span style="color: #b91c1c;">*</span></label>
        <select name="mode_of_transaction" id="{{ $fieldPrefix }}mode_of_transaction" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
            <option value="">Select mode...</option>
            <option value="Walk-in" @selected(old('mode_of_transaction') === 'Walk-in')>Walk-in</option>
            <option value="Visited" @selected(old('mode_of_transaction') === 'Visited')>Visited</option>
            <option value="Referral" @selected(old('mode_of_transaction') === 'Referral')>Referral</option>
        </select>
    </div>

    <div class="mb-3 lg:mb-4">
        <label for="{{ $fieldPrefix }}nature_of_visit" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Nature of visit <span style="color: #b91c1c;">*</span></label>
        <select name="nature_of_visit" id="{{ $fieldPrefix }}nature_of_visit" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
            <option value="">Select visit type...</option>
            <option value="New Consultation/Case" @selected(old('nature_of_visit') === 'New Consultation/Case')>New Consultation/Case</option>
            <option value="Follow-up Visit" @selected(old('nature_of_visit') === 'Follow-up Visit')>Follow-up Visit</option>
        </select>
    </div>

    <div class="mb-3 lg:mb-4">
        <label for="{{ $fieldPrefix }}chief_complaint" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Chief Complaints</label>
        <textarea name="chief_complaint" id="{{ $fieldPrefix }}chief_complaint" rows="2" class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" placeholder="e.g. Routine prenatal checkup">{{ old('chief_complaint') }}</textarea>
    </div>
</div>

<div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
    <h3 class="font-semibold mb-3 lg:mb-4 pb-2 border-b text-sm lg:text-base" style="color: var(--ink); border-color: var(--border);">Vitals</h3>

    <div class="grid grid-cols-2 gap-3 lg:gap-4">
        <div class="col-span-2">
            <label class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">BP <span style="color: #b91c1c;">*</span></label>
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <input type="number" name="bp_systolic" id="{{ $fieldPrefix }}bp_systolic" value="{{ old('bp_systolic') }}" min="0" max="300" placeholder="120" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                    <p class="text-xs mt-1 text-center" style="color: var(--ink-muted);">Systolic</p>
                </div>
                <span style="color: var(--ink-subtle); font-weight: 500;">/ </span>
                <div class="flex-1">
                    <input type="number" name="bp_diastolic" id="{{ $fieldPrefix }}bp_diastolic" value="{{ old('bp_diastolic') }}" min="0" max="200" placeholder="80" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
                    <p class="text-xs mt-1 text-center" style="color: var(--ink-muted);">Diastolic</p>
                </div>
            </div>
        </div>

        <div>
            <label for="{{ $fieldPrefix }}weight" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Weight (kg) <span style="color: #b91c1c;">*</span></label>
            <input type="number" step="0.1" name="weight" id="{{ $fieldPrefix }}weight" value="{{ old('weight') }}" min="0" max="500" placeholder="—" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
        </div>

        <div>
            <label for="{{ $fieldPrefix }}height" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Height (cm) <span style="color: #b91c1c;">*</span></label>
            <input type="number" step="0.1" name="height" id="{{ $fieldPrefix }}height" value="{{ old('height') }}" min="0" max="300" placeholder="—" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
        </div>

        <div>
            <label for="{{ $fieldPrefix }}temperature" class="block text-xs lg:text-sm font-medium mb-1" style="color: var(--ink-muted);">Temperature (°C) <span style="color: #b91c1c;">*</span></label>
            <input type="number" step="0.1" name="temperature" id="{{ $fieldPrefix }}temperature" value="{{ old('temperature') }}" min="30" max="45" placeholder="36.5" class="w-full px-3 lg:px-4 py-2 rounded-lg border text-center text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" required>
        </div>
    </div>
</div>

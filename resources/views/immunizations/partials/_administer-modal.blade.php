@php
    $adminErrors = $errors->hasAny(['vaccine_id', 'dose_number', 'date_given', 'temp_recorded', 'child_weight_kg', 'child_height_cm', 'override_reason', 'notes']);
    $reopenVaccine = $adminErrors ? $vaccines->firstWhere('id', (int) old('vaccine_id')) : null;
    $reopenRequiresTemp = $adminErrors && ($errors->has('temp_recorded') || old('temp_recorded') !== null);
    $reopenOutOfWindow = $adminErrors && ($errors->has('override_reason') || old('override_reason') !== null);
@endphp

<div
    x-data='administerVaccine()'
    x-init="initReopen({{ $adminErrors ? 'true' : 'false' }})"
    @keydown.escape.window="adminOpen = false"
    x-on:open-administer.window="openAdminister($event.detail)"
>
    <template x-teleport="body">
        <div x-show="adminOpen"
             x-cloak
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;"
             role="dialog"
             aria-modal="true"
             aria-labelledby="administerModalTitle">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="adminOpen = false"></div>
            <div x-show="adminOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border shadow-lg"
                 style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <form action="{{ route('immunizations.administer', $patient->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="vaccine_id" :value="vaccineId">

                    <div class="flex items-center justify-between border-b px-6 py-4" style="border-color: var(--border);">
                        <div>
                            <h3 id="administerModalTitle" class="font-display font-semibold text-lg" style="color: var(--ink);">Record dose</h3>
                            <p class="text-sm mt-0.5" style="color: var(--ink-muted);">
                                <span x-show="vaccineName" x-text="vaccineName + (doseNumber ? ` ${doseNumber}` : '')" class="font-medium" style="color: var(--ink);"></span>
                            </p>
                        </div>
                        <button type="button" @click="adminOpen = false" aria-label="Close" class="rounded-lg p-1.5 transition-colors hover:bg-black/5" style="color: var(--ink-muted);">
                            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div x-show="outOfWindow" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: var(--amber); color: var(--amber);">
                            <i class="fa-solid fa-clock mr-1.5" aria-hidden="true"></i>
                            This vaccine is outside its recommended age window. An override reason is required.
                        </div>

                        <div>
                            <label for="administer_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                Date given
                            </label>
                            <input id="administer_date" name="date_given" type="date" max="{{ now()->toDateString() }}"
                                   value="{{ old('date_given', now()->toDateString()) }}"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            @error('date_given')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="administer_temp" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                Body temperature (°C)
                            </label>
                            <input id="administer_temp" name="temp_recorded" type="number" step="0.1" min="30" max="45"
                                   value="{{ old('temp_recorded') }}" placeholder="e.g. 36.5"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <p class="mt-1 text-xs" style="color: var(--ink-subtle);">Optional. Record before administering injectable vaccines.</p>
                            @error('temp_recorded')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="administer_weight" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Weight (kg) <span style="color: var(--danger);">*</span>
                                </label>
                                <input id="administer_weight" name="child_weight_kg" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('child_weight_kg') }}" placeholder="e.g. 6.5" required
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('child_weight_kg')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="administer_height" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Height (cm) <span style="color: var(--danger);">*</span>
                                </label>
                                <input id="administer_height" name="child_height_cm" type="number" step="0.1" min="20" max="200"
                                       value="{{ old('child_height_cm') }}" placeholder="e.g. 65" required
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('child_height_cm')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div x-show="outOfWindow">
                            <label for="administer_override" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                Override reason <span style="color: var(--danger);">*</span>
                            </label>
                            <textarea id="administer_override" name="override_reason" rows="2" maxlength="500"
                                      :required="outOfWindow"
                                      class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                                      placeholder="e.g. Catch-up per physician advice">{{ old('override_reason') }}</textarea>
                            @error('override_reason')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="administer_notes" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Notes</label>
                            <textarea id="administer_notes" name="notes" rows="2" maxlength="500"
                                      class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                                      placeholder="Batch number, site, remarks (optional)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t px-6 py-4" style="border-color: var(--border);">
                        <button type="button" @click="adminOpen = false" class="rounded-lg border px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                            <i class="fa-solid fa-syringe" aria-hidden="true"></i> Record dose
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function administerVaccine() {
        return {
            adminOpen: false,
            vaccineId: null,
            vaccineName: '',
            doseNumber: null,
            outOfWindow: false,
            init() {
                this.$watch('adminOpen', (open) => {
                    document.body.classList.toggle('overflow-hidden', open);
                });
            },
            initReopen(hasErrors) {
                if (! hasErrors) return;
                this.vaccineId = {{ (int) old('vaccine_id', 0) }} || null;
                this.vaccineName = @js($reopenVaccine?->vaccine_name ?? '');
                this.outOfWindow = {{ $reopenOutOfWindow ? 'true' : 'false' }};
                this.adminOpen = true;
            },
            openAdminister(detail) {
                this.vaccineId = detail.vaccineId ?? null;
                this.vaccineName = detail.vaccineName ?? '';
                this.doseNumber = detail.doseNumber ?? null;
                this.outOfWindow = Boolean(detail.outOfWindow);
                this.adminOpen = true;
                this.$nextTick(() => document.getElementById('administer_date')?.focus());
            },
        };
    }
</script>
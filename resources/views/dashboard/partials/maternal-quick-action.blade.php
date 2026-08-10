<template x-teleport="body">
<div x-show="modalOpen" x-cloak
     x-effect="document.body.classList.toggle('modal-open', modalOpen)"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-2 sm:p-4"
     role="dialog" aria-modal="true" :aria-hidden="!modalOpen"
     @keydown.escape.window="modalOpen = false">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="modalOpen = false"></div>
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border shadow-lg mt-2 sm:mt-0"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-md);">
        <div class="flex items-center justify-between border-b px-5 py-3.5" style="border-color: var(--border);">
            <div>
                <h3 class="font-display font-semibold text-base sm:text-lg" style="color: var(--ink);" x-text="modalTitle"></h3>
                <p class="text-xs mt-0.5" style="color: var(--ink-muted);" x-text="modalSubtitle"></p>
            </div>
            <button type="button" @click="modalOpen = false" aria-label="Close" class="rounded-lg p-1.5 transition-colors hover:bg-black/5" style="color: var(--ink-muted);">
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="p-4 sm:p-5">
            <form @submit.prevent="submitQuickAction" id="maternal-quick-form" class="space-y-4">
                @csrf
                <input type="hidden" name="action" :value="modalAction">

                <template x-if="modalAction === 'register'">
                    <div class="space-y-3">
                        <div>
                            <label for="ql_lmp" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">LMP <span style="color: #b91c1c;">*</span></label>
                            <input id="ql_lmp" type="date" name="lmp" required x-model="formData.lmp" :max="todayStr"
                                   :class="{ 'input-error': errors.lmp }"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <p class="text-xs mt-1" style="color: var(--ink-subtle);">EDC auto-computed (LMP + 280 days)</p>
                            <p x-show="errors.lmp" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.lmp"></p>
                        </div>

                        <fieldset>
                            <legend class="block text-xs font-medium mb-2" style="color: var(--ink-muted);">Risk flags <span class="font-normal" style="color: var(--ink-subtle);">(optional)</span></legend>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                <template x-for="flag in riskFlagOptions" :key="flag.value">
                                    <label class="inline-flex items-center gap-2 text-xs cursor-pointer" style="color: var(--ink);">
                                        <input type="checkbox" :value="flag.value" name="risk_flags[]" class="rounded border focus:ring-2" style="border-color: var(--border); color: var(--primary); --tw-ring-color: var(--accent-blue);">
                                        <span x-text="flag.label"></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>
                    </div>
                </template>

                <template x-if="modalAction !== 'register'">
                    <div class="space-y-4">
                        <div>
                            <label for="ql_visit_date" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Visit date <span style="color: #b91c1c;">*</span></label>
                            <input id="ql_visit_date" type="date" name="visit_date" required x-model="formData.visit_date" :max="todayStr"
                                   :class="{ 'input-error': errors.visit_date }"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <p x-show="errors.visit_date" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.visit_date"></p>
                        </div>

                        <div>
                            <label for="ql_mode" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Mode of transaction <span style="color: #b91c1c;">*</span></label>
                            <select id="ql_mode" name="mode_of_transaction" required x-model="formData.mode_of_transaction"
                                    :class="{ 'input-error': errors.mode_of_transaction }"
                                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                <option value="">Select mode...</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Visited">Visited</option>
                                <option value="Referral">Referral</option>
                            </select>
                            <p x-show="errors.mode_of_transaction" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.mode_of_transaction"></p>
                        </div>

                        <div>
                            <label for="ql_nature" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Nature of visit <span style="color: #b91c1c;">*</span></label>
                            <select id="ql_nature" name="nature_of_visit" required x-model="formData.nature_of_visit"
                                    :class="{ 'input-error': errors.nature_of_visit }"
                                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                <option value="">Select visit type...</option>
                                <option value="New Consultation/Case">New Consultation/Case</option>
                                <option value="Follow-up Visit">Follow-up Visit</option>
                            </select>
                            <p x-show="errors.nature_of_visit" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.nature_of_visit"></p>
                        </div>

                        <div>
                            <label for="ql_complaint" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Chief Complaints</label>
                            <input id="ql_complaint" type="text" name="chief_complaint" x-model="formData.chief_complaint" placeholder="e.g. Routine prenatal checkup"
                                   :class="{ 'input-error': errors.chief_complaint }"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <p x-show="errors.chief_complaint" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.chief_complaint"></p>
                        </div>

                        <div class="rounded-xl border p-3 sm:p-4" style="background: var(--bg-surface); border-color: var(--border);">
                            <h4 class="font-semibold mb-3 pb-1.5 border-b text-xs sm:text-sm" style="color: var(--ink); border-color: var(--border);">Vitals <span style="color: #b91c1c;">*</span></h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="ql_bp_s" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">BP Systolic <span style="color: #b91c1c;">*</span></label>
                                    <input id="ql_bp_s" type="number" name="bp_systolic" required x-model="formData.bp_systolic" min="0" max="300" placeholder="120"
                                           :class="{ 'input-error': errors.bp_systolic }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm text-center focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.bp_systolic" class="mt-1 text-xs font-medium text-center" style="color: #b91c1c;" x-text="errors.bp_systolic"></p>
                                </div>
                                <div>
                                    <label for="ql_bp_d" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">BP Diastolic <span style="color: #b91c1c;">*</span></label>
                                    <input id="ql_bp_d" type="number" name="bp_diastolic" required x-model="formData.bp_diastolic" min="0" max="200" placeholder="80"
                                           :class="{ 'input-error': errors.bp_diastolic }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm text-center focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.bp_diastolic" class="mt-1 text-xs font-medium text-center" style="color: #b91c1c;" x-text="errors.bp_diastolic"></p>
                                </div>
                                <div>
                                    <label for="ql_weight" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Weight (kg) <span style="color: #b91c1c;">*</span></label>
                                    <input id="ql_weight" type="number" step="0.1" name="weight" required x-model="formData.weight" min="0" max="500" placeholder="—"
                                           :class="{ 'input-error': errors.weight }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm text-center focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.weight" class="mt-1 text-xs font-medium text-center" style="color: #b91c1c;" x-text="errors.weight"></p>
                                </div>
                                <div>
                                    <label for="ql_temp" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Temp (&deg;C) <span style="color: #b91c1c;">*</span></label>
                                    <input id="ql_temp" type="number" step="0.1" name="temperature" required x-model="formData.temperature" min="30" max="45" placeholder="36.5"
                                           :class="{ 'input-error': errors.temperature }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm text-center focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.temperature" class="mt-1 text-xs font-medium text-center" style="color: #b91c1c;" x-text="errors.temperature"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="ql_height" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Height (cm) <span style="color: #b91c1c;">*</span></label>
                            <input id="ql_height" type="number" step="0.1" name="height" required x-model="formData.height" min="0" max="300" placeholder="—"
                                   :class="{ 'input-error': errors.height }"
                                   class="w-full rounded-lg border px-3 py-2 text-sm text-center focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <p x-show="errors.height" class="mt-1 text-xs font-medium text-center" style="color: #b91c1c;" x-text="errors.height"></p>
                        </div>

                        <template x-if="modalAction === 'log_prenatal_visit'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="ql_fh" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Fundic height (cm)</label>
                                    <input id="ql_fh" type="number" step="0.1" name="fundic_height_cm" x-model="formData.fundic_height_cm" min="0" max="99.9"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                </div>
                                <div>
                                    <label for="ql_fhr" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Fetal heart tone (bpm)</label>
                                    <input id="ql_fhr" type="number" name="fetal_heart_tone_bpm" x-model="formData.fetal_heart_tone_bpm" min="60" max="220"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="ql_nvd" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Next visit date</label>
                                    <input id="ql_nvd" type="date" name="next_visit_date" x-model="formData.next_visit_date" :min="todayStr"
                                           :class="{ 'input-error': errors.next_visit_date }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.next_visit_date" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.next_visit_date"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="modalAction === 'log_postpartum'">
                            <p class="text-xs" style="color: var(--ink-muted);">
                                <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                                Fills the earliest open postpartum follow-up window.
                            </p>
                        </template>

                        <template x-if="modalAction === 'log_fp_visit'">
                            <div class="space-y-3">
                                <div>
                                    <label for="ql_fp_method" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Method <span style="color: #b91c1c;">*</span></label>
                                    <select id="ql_fp_method" name="method" required x-model="formData.method"
                                            :class="{ 'input-error': errors.method }"
                                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                        <option value="">Select method...</option>
                                        @foreach (\App\Models\FamilyPlanningClient::METHODS as $method)
                                            <option value="{{ $method }}">{{ $method }}</option>
                                        @endforeach
                                    </select>
                                    <p x-show="errors.method" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.method"></p>
                                </div>
                                <div>
                                    <label for="ql_fp_next_visit" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Next visit date</label>
                                    <input id="ql_fp_next_visit" type="date" name="next_visit_date" x-model="formData.next_visit_date" :min="todayStr"
                                           :class="{ 'input-error': errors.next_visit_date }"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <p x-show="errors.next_visit_date" class="mt-1 text-xs font-medium" style="color: #b91c1c;" x-text="errors.next_visit_date"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </form>
        </div>

        <div class="flex items-center justify-end gap-2 border-t px-5 py-3.5" style="border-color: var(--border);">
            <button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-xs sm:text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" form="maternal-quick-form" :disabled="submitting"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs sm:text-sm font-semibold text-white transition disabled:opacity-60"
                    style="background: var(--primary); box-shadow: var(--shadow-sm);">
                <i class="fa-solid fa-spinner fa-spin" x-show="submitting" aria-hidden="true"></i>
                <span x-text="modalAction === 'register' ? 'Register pregnancy' : 'Log visit'"></span>
            </button>
        </div>
    </div>
</div>
</template>

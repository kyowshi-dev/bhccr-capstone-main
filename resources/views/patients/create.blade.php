@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-2 lg:space-y-3">
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 px-3 lg:px-4 py-2 lg:py-3 text-xs lg:text-sm text-red-800">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('patients.store') }}" method="POST" class="mt-0 bg-white rounded-xl lg:rounded-lg shadow-sm border-gray-200 space-y-3 lg:space-y-4" x-data="{ isPhilhealthMember: '{{ old('is_philhealth_member', 'n') }}' }">
        @csrf

        <div class="pb-3 lg:pb-4 border-b border-gray-100">
            <h3 class="text-sm lg:text-base font-extrabold text-sky-700 mb-2 lg:mb-3 flex items-center">
                <span class="mr-2"><i class="fas fa-user"></i></span>
                Personal Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" 
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('first_name') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('middle_name') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                    @error('middle_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('last_name') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Mother's Name <span class="text-red-500">*</span></label>
                    <input type="text" name="mother_name" value="{{ old('mother_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('mother_name') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm"
                           placeholder="e.g. Maria Santos">
                    @error('mother_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Partner / Spouse's Name <span class="text-red-500">*</span></label>
                    <input type="text" name="spouse_name" value="{{ old('spouse_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('spouse_name') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm"
                           placeholder="">
                    @error('spouse_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Family Member (Posisyon sa Pamilya) <span class="text-red-500">*</span></label>
                    <select name="family_relationship" class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('family_relationship') border-red-500 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="">Select relationship</option>
                        @foreach(\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                            <option value="{{ $relationship }}" {{ old('family_relationship') === $relationship ? 'selected' : '' }}>{{ $relationship }}</option>
                        @endforeach
                    </select>
                    @error('family_relationship') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Suffix</label>
                    <input type="text" name="suffix" placeholder="Jr, III" value="{{ old('suffix') }}" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Sex <span class="text-red-500">*</span></label>
                    <select name="sex" class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('sex') border-red-500 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Birthdate <span class="text-red-500">*</span></label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('date_of_birth') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                    @error('date_of_birth') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Blood Type (optional)</label>
                    <select name="blood_type" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="" {{ old('blood_type') === '' || old('blood_type') === null ? 'selected' : '' }}></option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                            <option value="{{ $type }}" {{ old('blood_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs uppercase -500 font-bold mb-1">Birth Place</label>
                <input type="text" name="birth_place" value="{{ old('birth_place') }}" 
                       placeholder="City/Municipality, Province" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm">
            </div>
        </div>

        <div class="pb-3 lg:pb-4 border-b border-gray-100">
            <h3 class="text-sm lg:text-base font-extrabold text-sky-700 mb-2 lg:mb-3 flex items-center">
                <span class="mr-2"><i class="fas fa-peso-sign"></i></span>
                Socio-Economic Status
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Civil Status <span class="text-red-500">*</span></label>
                    <select name="civil_status" class="w-full px-3 lg:px-4 py-2 rounded-xl border focus:ring-sky-500 focus:border-sky-500 text-sm">
                        @foreach(['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'] as $status)
                            <option value="{{ $status }}" {{ old('civil_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Educational Attainment</label>
                    <select name="educational_attainment" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="">Select Level</option>
                        @foreach(['No Formal Education', 'Elementary', 'High School', 'College', 'Postgraduate', 'Vocational'] as $edu)
                            <option value="{{ $edu }}" {{ old('educational_attainment') == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase font-bold mb-1">Employment Status</label>
                    <select name="employment_status" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="">Select employment status</option>
                        <option value="Student" {{ old('employment_status') === 'Student' ? 'selected' : '' }}>Student (Estudyante)</option>
                        <option value="Employed" {{ old('employment_status') === 'Employed' ? 'selected' : '' }}>Employed (May trabaho)</option>
                        <option value="Unknown" {{ old('employment_status') === 'Unknown' ? 'selected' : '' }}>Unknown (Hindi malaman)</option>
                        <option value="Student" {{ old('employment_status') === 'Student' ? 'selected' : '' }}>Retired (Retirado)</option>
                        <option value="Retired" {{ old('employment_status') === 'Retired' ? 'selected' : '' }}>None/Unemployed (Walang Trabaho)</option>
                        <option value="Others" {{ old('employment_status') === 'Others' ? 'selected' : '' }}>Others</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-4">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">PhilHealth Member?</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_philhealth_member" value="y" x-model="isPhilhealthMember" {{ old('is_philhealth_member') === 'y' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_philhealth_member" value="n" x-model="isPhilhealthMember" {{ old('is_philhealth_member', 'n') === 'n' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                            <span>No</span>
                        </label>
                    </div>
                    @error('is_philhealth_member') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">PhilHealth Number <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                    <input type="text" name="philhealth_no" value="{{ old('philhealth_no') }}"
                           placeholder="12-123456789-0"
                           :disabled="isPhilhealthMember !== 'y'"
                           x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 bg-gray-50 cursor-not-allowed' : ''"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('philhealth_no') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                    @error('philhealth_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-3">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">Membership Category <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                    <select name="membership_category"
                            :disabled="isPhilhealthMember !== 'y'"
                            x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 cursor-not-allowed' : ''"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('membership_category') border-red-500 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="">Select category</option>
                        @foreach(\App\Models\Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES as $category)
                            <option value="{{ $category }}" {{ old('membership_category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('membership_category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">PhilHealth Status <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                    <select name="status_type"
                            :disabled="isPhilhealthMember !== 'y'"
                            x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 cursor-not-allowed' : ''"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('status_type') border-red-500 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                        <option value="">Select status</option>
                        @foreach(\App\Models\Patient::PHILHEALTH_STATUS_TYPES as $status)
                            <option value="{{ $status }}" {{ old('status_type') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-4">
                <div>
                    <label class="block text-xs uppercase -500 font-bold mb-1">PCB Member</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_pcb_member" value="y" {{ old('is_pcb_member') === 'y' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_pcb_member" value="n" {{ old('is_pcb_member', 'n') === 'n' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                            <span>No</span>
                        </label>
                    </div>
                    @error('is_pcb_member') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-4 lg:gap-6 p-2 lg:p-3 bg-gray-50 rounded-xl border border-gray-100">
                <div class="flex items-center">
                    <input type="checkbox" name="has_4ps" id="4ps" value="1" 
                           class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded"
                           {{ old('has_4ps') ? 'checked' : '' }}>
                    <label for="4ps" class="ml-2 block text-xs lg:text-sm -900 font-medium">4Ps Member</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="has_nhts" id="nhts" value="1"
                           class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded"
                           {{ old('has_nhts') ? 'checked' : '' }}>
                    <label for="nhts" class="ml-2 block text-xs lg:text-sm -900 font-medium">NHTS / Indigent</label>
                </div>
            </div>
        </div>

        <div class="pb-2 lg:pb-3 border-b border-gray-100">
            <h3 class="text-sm lg:text-base font-extrabold text-sky-700 mb-2 lg:mb-3 flex items-center">
                <span class="mr-2"><i class="fas fa-home"></i></span>
                Household Information
            </h3>

            <div x-data="{ creating: {{ old('create_new_household') ? 'true' : 'false' }} }" class="space-y-4">
                <input type="hidden" name="create_new_household" :value="creating ? 1 : 0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button type="button"
                            @click="creating = false"
                            :class="creating ? 'border-gray-200 bg-slate-50 text-slate-700' : 'border-sky-600 bg-white text-sky-700 shadow-sm'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-sky-600">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Select Existing Household</p>
                                <p class="text-xs text-gray-500 mt-1">Search by family name, zone, or contact.</p>
                            </div>
                            <span x-show="!creating" x-cloak class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">Active</span>
                        </div>
                    </button>

                    <button type="button"
                            @click="creating = true"
                            :class="creating ? 'border-sky-600 bg-white text-sky-700 shadow-sm' : 'border-gray-200 bg-slate-50 text-slate-700'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-sky-600">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Create New Household</p>
                                <p class="text-xs text-gray-500 mt-1">Add a new household record and attach this patient.</p>
                            </div>
                            <span x-show="creating" x-cloak class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">Active</span>
                        </div>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div x-show="!creating" x-cloak class="bg-slate-50 border border-gray-200 rounded-2xl p-4">
                        <div x-data='householdAutocomplete({
                                initialId: @json($selectedHouseholdId ? (int) $selectedHouseholdId : null),
                                initialText: @json($selectedHousehold?->family_name_head ?? ""),
                                transientId: @json($transientHouseholdId ?? null),
                                transientLabel: @json($transientHouseholdLabel ?? "Transient/Unmapped")
                            })'
                             x-init="init()" class="space-y-4">
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                                    Select Household <span class="text-red-500">*</span>
                                </label>
                                <p class="text-xs text-gray-500">Search by family name, zone, or contact.</p>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap mb-3">
                                <input type="checkbox"
                                       id="transient_unmapped"
                                       x-model="isTransient"
                                       @change="onTransientToggle()"
                                       class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded"
                                       :disabled="transientHouseholdId === null">
                                <label for="transient_unmapped" class="text-xs lg:text-sm font-medium text-gray-700">
                                    Transient/Unmapped
                                </label>

                                <template x-if="transientHouseholdId === null">
                                    <span class="text-xs text-red-600">Transient household not found.</span>
                                </template>
                            </div>

                            <input type="hidden" name="household_id" x-model="householdId">

                            <div class="relative">
                                <input type="text"
                                       x-ref="householdSearch"
                                       x-model="query"
                                       @input.debounce.250ms="search()"
                                       @focus="dropdownOpen = true"
                                       @keydown.escape="dropdownOpen = false"
                                       @click="dropdownOpen = true"
                                       :disabled="isTransient"
                                       placeholder="Search household (family name / zone / contact)..."
                                       class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border @error('household_id') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm"
                                       autocomplete="off">

                                <div x-show="dropdownOpen && !isTransient && results.length > 0" class="absolute z-20 w-full bg-white mt-1 border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                    <ul>
                                        <template x-for="item in results" :key="item.id">
                                            <li class="px-3 lg:px-4 py-2 lg:py-2.5 hover:bg-sky-50 cursor-pointer border-b last:border-0 text-xs lg:text-sm"
                                                @click.prevent="select(item)">
                                                <div class="font-medium text-gray-800" x-text="item.text"></div>
                                                <div class="text-xs text-gray-500" x-text="item.subtext"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-xs text-gray-500" x-show="!isTransient && query.length > 0 && results.length === 0" x-cloak>
                                No household found.
                            </div>

                            @error('household_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-show="creating" x-cloak class="bg-slate-50 border border-gray-200 rounded-2xl p-4">
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-800">New household details</h4>
                            <p class="text-xs text-gray-500">Fill in the household information that will be created with this patient.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                            <div>
                                <label class="block text-xs uppercase font-bold text-gray-700 mb-1">
                                    Zone <span class="text-red-500">*</span>
                                </label>
                                <select name="new_household_zone_id"
                                        class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_zone_id') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm">
                                    <option value="">Select zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('new_household_zone_id') == $zone->id ? 'selected' : '' }}>
                                            {{ $zone->zone_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('new_household_zone_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs uppercase font-bold text-gray-700 mb-1">
                                    Family Head (Apelyido) <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="new_household_family_name_head"
                                       value="{{ old('new_household_family_name_head') }}"
                                       class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_family_name_head') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm"
                                       placeholder="Head Surname">
                                @error('new_household_family_name_head')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-700 mb-1">
                                Contact Number
                            </label>
                            <input type="text"
                                   name="new_household_contact_number"
                                   value="{{ old('new_household_contact_number') }}"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_contact_number') border-red-500 bg-red-50 @else border-gray-300 @enderror focus:ring-sky-500 focus:border-sky-500 text-sm"
                                   placeholder="e.g. 09XXXXXXXXX">
                            @error('new_household_contact_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-xs text-gray-500 bg-sky-50 border border-sky-200 rounded-lg p-3">
                            A new household will be created and the patient will be added to it.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 lg:gap-3">
            <a href="{{ route('patients.index') }}" class="px-4 lg:px-6 py-2 rounded-xl border border-gray-300 -600 hover:bg-[var(--primary-light)] text-xs lg:text-sm font-medium">Cancel</a>
            <button type="submit" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl bg-[var(--primary)] text-white font-semibold text-xs lg:text-sm shadow-md hover:bg-[var(--primary-light)] transition">
                Save Patient Record
            </button>
        </div>
    </form>

    <script>
        function householdAutocomplete({ initialId, initialText, transientId, transientLabel }) {
            return {
                query: initialText || '',
                householdId: initialId || null,
                transientHouseholdId: transientId ?? null,
                transientHouseholdLabel: transientLabel || 'Transient/Unmapped',
                isTransient: transientId !== null && initialId !== null && String(initialId) === String(transientId),
                previousHouseholdId: null,
                previousQuery: null,
                dropdownOpen: false,
                results: [],
                loading: false,

                init() {
                    // If the old household is the transient household, lock it in.
                    if (this.isTransient && this.transientHouseholdId !== null) {
                        this.householdId = this.transientHouseholdId;
                        this.query = this.transientHouseholdLabel;
                    }
                },

                async search() {
                    if (this.isTransient) return;
                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.results = [];
                        this.dropdownOpen = false;
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await fetch(`/search/households?query=${encodeURIComponent(q)}`);
                        this.results = await response.json();
                        this.dropdownOpen = this.results.length > 0;
                    } catch (e) {
                        console.error('Household search failed:', e);
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                select(item) {
                    this.householdId = item.id;
                    this.query = item.text;
                    this.results = [];
                    this.dropdownOpen = false;
                },

                onTransientToggle() {
                    this.results = [];
                    this.dropdownOpen = false;

                    if (this.isTransient) {
                        this.previousHouseholdId = this.householdId;
                        this.previousQuery = this.query;

                        if (this.transientHouseholdId === null) {
                            this.isTransient = false;
                            return;
                        }

                        this.householdId = this.transientHouseholdId;
                        this.query = this.transientHouseholdLabel;
                        return;
                    }

                    if (this.previousHouseholdId !== null) {
                        this.householdId = this.previousHouseholdId;
                        this.query = this.previousQuery ?? this.query;
                    }
                },
            };
        }
    </script>
</div>
@endsection

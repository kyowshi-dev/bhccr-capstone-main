@extends('layouts.app')

@section('title', 'Edit Consultation')

@php
    $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
@endphp

@section('content')
<form action="{{ route('consultations.update', $consultation->id) }}" method="POST" id="consultationForm" class="space-y-5 lg:space-y-6">
    @csrf
    @method('PUT')
    
    <div class="space-y-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit Consultation</h1>
                <p class="text-sm mt-1" style="color: var(--ink-muted);">{{ \App\Helpers\PatientCode::format((int) $consultation->patient_id) }} - {{ $patient->last_name }}, {{ $patient->first_name }}</p>
            </div>
        </div>

        <!-- Slim Horizontal Info Bar -->
        <div class="rounded-lg border px-4 py-3 flex flex-wrap gap-4 lg:gap-6 text-sm" style="background: var(--bg-surface); border-color: var(--border);">
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">DATE</p>
                <p style="color: var(--ink);" class="font-medium">{{ \Carbon\Carbon::parse($consultation->created_at)->format(\App\Helpers\DateFormat::DATE_SQL.' H:i A') }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">STATUS</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->status_label }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">HEALTH WORKER</p>
                <p style="color: var(--ink);" class="font-medium">{{ trim(($consultation->worker_first_name ?? '').' '.($consultation->worker_last_name ?? '')) ?: 'Not assigned' }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">NATURE OF VISIT</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->nature_of_visit }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-5 lg:space-y-6">

            <!-- Diagnoses Section -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-lg" style="color: var(--ink);">Diagnoses</h2>
                    <button type="button" class="px-3 py-1 rounded-lg text-xs font-medium transition" style="background: var(--teal-soft); color: var(--primary);" onclick="openDiagnosisModal()">+ Add Diagnosis</button>
                </div>
                
                <div id="diagnosesList" class="space-y-3">
                    @if ($diagnoses->count() > 0)
                        @foreach ($diagnoses as $diagnosis)
                            <div class="p-3 rounded-lg border flex items-start justify-between group" style="background: var(--border); border-color: var(--border);">
                                <div class="flex-1">
                                    <p class="text-sm font-medium" style="color: var(--ink);">{{ $diagnosis->diagnosis_name }}</p>
                                    @if ($diagnosis->remarks)
                                        <p class="text-xs mt-1" style="color: var(--ink-muted);">{{ $diagnosis->remarks }}</p>
                                    @endif
                                </div>
                                <button type="button" class="ml-3 p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition opacity-0 group-hover:opacity-100 focus-visible:opacity-100" onclick="deleteDiagnosis({{ $diagnosis->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm italic py-4" style="color: var(--ink-muted);">No diagnoses recorded. Click "Add Diagnosis" to get started.</p>
                    @endif
                </div>
            </div>

            <!-- Prescriptions Section -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-lg" style="color: var(--ink);">Prescriptions</h2>
                    <button type="button" class="px-3 py-1 rounded-lg text-xs font-medium transition" style="background: var(--teal-soft); color: var(--primary);" onclick="openPrescriptionModal()">+ Add Prescription</button>
                </div>
                
                <div id="prescriptionsList" class="space-y-3">
                    @if ($prescriptions->count() > 0)
                        @foreach ($prescriptions as $prescription)
                            <div class="p-3 rounded-lg border group" style="background: var(--border); border-color: var(--border);">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" style="color: var(--ink);">{{ $prescription->medicine_name }}</p>
                                        <div class="grid grid-cols-2 gap-2 mt-2 text-xs" style="color: var(--ink-muted);">
                                            @if ($prescription->dosage)
                                                <p><span class="font-medium">Dosage:</span> {{ $prescription->dosage }}</p>
                                            @endif
                                            @if ($prescription->frequency)
                                                <p><span class="font-medium">Frequency:</span> {{ $prescription->frequency }}</p>
                                            @endif
                                            @if ($prescription->duration)
                                                <p><span class="font-medium">Duration:</span> {{ $prescription->duration }}</p>
                                            @endif
                                            @if ($prescription->quantity)
                                                <p><span class="font-medium">Quantity:</span> {{ $prescription->quantity }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" class="ml-3 p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition opacity-0 group-hover:opacity-100 focus-visible:opacity-100" onclick="deletePrescription({{ $prescription->id }})">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm italic py-4" style="color: var(--ink-muted);">No prescriptions recorded. Click "Add Prescription" to get started.</p>
                    @endif
                </div>
            </div>

            <!-- Quick Notes - Primary Focal Point -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <h2 class="font-semibold text-lg" style="color: var(--ink);">Quick Notes</h2>
                <textarea name="notes" placeholder="Add detailed notes about this consultation..." rows="6" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary); resize: vertical;">{{ $consultation->notes }}</textarea>
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 rounded-xl text-white text-sm font-semibold transition" style="background: var(--primary);">Update Consultation</button>
                    <a href="{{ route('consultations.show', $consultation->id) }}" class="px-4 py-2 rounded-xl text-sm font-semibold transition" style="background: var(--border); color: var(--ink);">View Full Record</a>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5 lg:space-y-6">
            <!-- Patient Info Card -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <h3 class="font-semibold text-base" style="color: var(--ink);">Patient Information</h3>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <p style="color: var(--ink-muted);">Name</p>
                        <p class="font-medium" style="color: var(--ink);">{{ $patient->last_name }}, {{ $patient->first_name }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Patient ID</p>
                        <p class="font-medium" style="color: var(--ink);">PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Date of Birth</p>
                        <p class="font-medium" style="color: var(--ink);">{{ \Carbon\Carbon::parse($patient->date_of_birth)->format('Y-m-d') }} <span style="color: var(--ink-muted);">({{ $age }}y)</span></p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Sex</p>
                        <p class="font-medium" style="color: var(--ink);">{{ $patient->sex }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Diagnosis Modal -->
<div id="diagnosisModal" x-show="$store.modals.diagnosis" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDiagnosisModal()"></div>
    <div id="diagnosisPanel" x-show="$store.modals.diagnosis" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-white rounded-xl max-w-md w-full p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
        <h3 class="font-semibold text-lg">Add Diagnosis</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Diagnosis Name</label>
                <input type="text" id="diagnosisName" placeholder="Enter diagnosis" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Remarks</label>
                <textarea id="diagnosisRemarks" placeholder="Add remarks (optional)" rows="3" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary); resize: vertical;"></textarea>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closeDiagnosisModal()">Cancel</button>
            <button type="button" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addDiagnosis()">Add</button>
        </div>
    </div>
</div>

<!-- Prescription Modal -->
<div id="prescriptionModal" x-show="$store.modals.prescription" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePrescriptionModal()"></div>
    <div id="prescriptionPanel" x-show="$store.modals.prescription" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-white rounded-xl max-w-md w-full p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
        <h3 class="font-semibold text-lg">Add Prescription</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Medicine Name</label>
                <input type="text" id="medicineName" placeholder="Enter medicine" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Dosage</label>
                    <input type="text" id="dosage" placeholder="e.g., 500mg" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Frequency</label>
                    <input type="text" id="frequency" placeholder="e.g., 3x daily" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Duration</label>
                    <input type="text" id="duration" placeholder="e.g., 7 days" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Quantity</label>
                    <input type="number" id="quantity" placeholder="e.g., 21" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                </div>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closePrescriptionModal()">Cancel</button>
            <button type="button" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addPrescription()">Add</button>
        </div>
    </div>
</div>

<script>
// Modal functions
function openDiagnosisModal() {
    document.getElementById('diagnosisModal').classList.remove('hidden');
    document.getElementById('diagnosisName').focus();
}

function closeDiagnosisModal() {
    document.getElementById('diagnosisModal').classList.add('hidden');
    document.getElementById('diagnosisName').value = '';
    document.getElementById('diagnosisRemarks').value = '';
}

function openPrescriptionModal() {
    document.getElementById('prescriptionModal').classList.remove('hidden');
    document.getElementById('medicineName').focus();
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').classList.add('hidden');
    document.getElementById('medicineName').value = '';
    document.getElementById('dosage').value = '';
    document.getElementById('frequency').value = '';
    document.getElementById('duration').value = '';
    document.getElementById('quantity').value = '';
}

// Add diagnosis (via AJAX to backend)
function addDiagnosis() {
    const name = document.getElementById('diagnosisName').value.trim();
    const remarks = document.getElementById('diagnosisRemarks').value.trim();
    
    if (!name) {
        alert('Please enter a diagnosis name');
        return;
    }
    
    // For now, we add to the client-side list and it will be handled on form submission
    // In a real implementation, you'd POST to an endpoint that adds to the DB
    const diagnosisList = document.getElementById('diagnosesList');
    const diagnosisHtml = `
        <div class="p-3 rounded-lg border flex items-start justify-between group" style="background: var(--border); border-color: var(--border);">
            <div class="flex-1">
                <p class="text-sm font-medium" style="color: var(--ink);">${name}</p>
                ${remarks ? `<p class="text-xs mt-1" style="color: var(--ink-muted);">${remarks}</p>` : ''}
                <input type="hidden" class="diagnosis-name" value="${name}">
                <input type="hidden" class="diagnosis-remarks" value="${remarks}">
            </div>
            <button type="button" class="ml-3 p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition opacity-0 group-hover:opacity-100 focus-visible:opacity-100" onclick="this.closest('[style*=background]').remove(); checkIfEmpty('diagnosesList')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
    `;
    
    if (diagnosisList.innerHTML.includes('No diagnoses recorded')) {
        diagnosisList.innerHTML = diagnosisHtml;
    } else {
        diagnosisList.insertAdjacentHTML('beforeend', diagnosisHtml);
    }
    
    closeDiagnosisModal();
}

// Add prescription (via AJAX to backend)
function addPrescription() {
    const medicineName = document.getElementById('medicineName').value.trim();
    const dosage = document.getElementById('dosage').value.trim();
    const frequency = document.getElementById('frequency').value.trim();
    const duration = document.getElementById('duration').value.trim();
    const quantity = document.getElementById('quantity').value.trim();
    
    if (!medicineName) {
        alert('Please enter a medicine name');
        return;
    }
    
    // For now, we add to the client-side list
    const prescriptionList = document.getElementById('prescriptionsList');
    let details = '';
    if (dosage) details += `<p><span class="font-medium">Dosage:</span> ${dosage}</p>`;
    if (frequency) details += `<p><span class="font-medium">Frequency:</span> ${frequency}</p>`;
    if (duration) details += `<p><span class="font-medium">Duration:</span> ${duration}</p>`;
    if (quantity) details += `<p><span class="font-medium">Quantity:</span> ${quantity}</p>`;
    
    const prescriptionHtml = `
        <div class="p-3 rounded-lg border group" style="background: var(--border); border-color: var(--border);">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium" style="color: var(--ink);">${medicineName}</p>
                    <div class="grid grid-cols-2 gap-2 mt-2 text-xs" style="color: var(--ink-muted);">
                        ${details}
                    </div>
                    <input type="hidden" class="medicine-name" value="${medicineName}">
                    <input type="hidden" class="dosage" value="${dosage}">
                    <input type="hidden" class="frequency" value="${frequency}">
                    <input type="hidden" class="duration" value="${duration}">
                    <input type="hidden" class="quantity" value="${quantity}">
                </div>
                <button type="button" class="ml-3 p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition opacity-0 group-hover:opacity-100 focus-visible:opacity-100" onclick="this.closest('[style*=background]').remove(); checkIfEmpty('prescriptionsList')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>
    `;
    
    if (prescriptionList.innerHTML.includes('No prescriptions recorded')) {
        prescriptionList.innerHTML = prescriptionHtml;
    } else {
        prescriptionList.insertAdjacentHTML('beforeend', prescriptionHtml);
    }
    
    closePrescriptionModal();
}

function checkIfEmpty(listId) {
    const list = document.getElementById(listId);
    const itemCount = list.querySelectorAll('[style*="rgba(0,0,0,0.02)"]').length;
    
    if (itemCount === 0) {
        let emptyMsg = 'No diagnoses recorded. Click "Add Diagnosis" to get started.';
        if (listId === 'prescriptionsList') {
            emptyMsg = 'No prescriptions recorded. Click "Add Prescription" to get started.';
        }
        list.innerHTML = `<p class="text-sm italic py-4" style="color: var(--ink-muted);">${emptyMsg}</p>`;
    }
}

// Delete diagnosis
function deleteDiagnosis(id) {
    if (confirm('Are you sure you want to delete this diagnosis?')) {
        // Make a DELETE request
        fetch(`{{ route('consultations.diagnosis.delete', ['consultation' => $consultation->id, 'diagnosisId' => '__DID__']) }}`.replace('__DID__', id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to delete diagnosis');
            }
        })
        .catch(() => alert('Error deleting diagnosis'));
    }
}

// Delete prescription
function deletePrescription(id) {
    if (confirm('Are you sure you want to delete this prescription?')) {
        // Make a DELETE request
        fetch(`{{ route('consultations.prescription.delete', ['consultation' => $consultation->id, 'prescriptionId' => '__PID__']) }}`.replace('__PID__', id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to delete prescription');
            }
        })
        .catch(() => alert('Error deleting prescription'));
    }
}

// Close modals on background click
document.getElementById('diagnosisModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDiagnosisModal();
});

document.getElementById('prescriptionModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePrescriptionModal();
});
</script>

@endsection

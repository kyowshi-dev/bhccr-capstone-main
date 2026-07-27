@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
    <div class="md:col-span-1 space-y-4 lg:space-y-6">
        <div class="bg-white p-4 lg:p-6 rounded-xl lg:rounded-lg shadow-sm border border-gray-200">
            <div class="text-center mb-3 lg:mb-4">
                <div class="w-16 h-16 lg:w-20 lg:h-20 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-2xl lg:text-3xl font-bold mx-auto">
                    {{ substr($patient->first_name, 0, 1) }}{{ substr($patient->last_name, 0, 1) }}
                </div>
                <h2 class="text-lg lg:text-xl font-bold mt-2">{{ ucwords($patient->last_name) }}, {{ ucwords($patient->first_name) }}</h2>
                <p class="text-gray-500 text-xs lg:text-sm">ID: PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</p>
            </div>

            <hr class="my-3 lg:my-4">

            <div class="space-y-2 lg:space-y-3 text-xs lg:text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Age / Sex:</span>
                    <span class="font-medium">{{ $patient->age }} y/o / {{ $patient->sex }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Birthdate:</span>
                    <span class="font-medium">{{ $patient->date_of_birth }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Blood Type:</span>
                    <span class="font-medium">{{ $patient->blood_type ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-start">
                    <span class="text-gray-500">Mother's Name:</span>
                    <span class="font-medium text-right">{{ ucwords($patient->mother_name) }}</span>
                </div>
                <div class="flex justify-between items-start">
                    <span class="text-gray-500">Spouse's Name:</span>
                    <span class="font-medium text-right">{{ ucwords ($patient->spouse_name) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Relationship:</span>
                    <span class="font-medium">{{ $patient->family_relationship }}</span>
                </div>
                <div class="flex justify-between items-start">
                    <span class="text-gray-500">Residential Address:</span>
                    <span class="font-medium text-right">{{ $patient->residential_address }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Zone:</span>
                    <span class="font-medium">Zone {{ $patient->zone_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Civil Status:</span>
                    <span class="font-medium">{{ $patient->civil_status }}</span>
                </div>
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Programs</h4>
                <div class="flex flex-wrap gap-2">
                    @if($patient->has_4ps)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">4Ps Member</span>
                    @endif
                    @if($patient->has_nhts)
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">NHTS</span>
                    @endif
                    @if(!$patient->has_4ps && !$patient->has_nhts)
                        <span class="text-gray-400 text-xs italic">No active programs</span>
                    @endif
                </div>
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Membership</h4>
                <div class="space-y-2 text-xs lg:text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>PhilHealth Member</span>
                        <span class="font-medium">{{ $patient->is_philhealth_member === 'y' ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>PhilHealth No.</span>
                        <span class="font-medium">{{ $patient->philhealth_no ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Category</span>
                        <span class="font-medium">{{ $patient->membership_category ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Status Type</span>
                        <span class="font-medium">{{ $patient->status_type ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>PCB Member</span>
                        <span class="font-medium">{{ $patient->is_pcb_member === 'y' ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Immunization</h4>
                <p class="text-xs lg:text-sm text-gray-600 mb-2">{{ $immunizationCount }} dose(s) recorded.</p>
                <a href="{{ route('immunizations.patient', $patient->id) }}" class="inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs lg:text-sm font-medium bg-teal-50 text-teal-700 hover:bg-teal-100 transition">
                    View / Add Immunization
                </a>
            </div>
        </div>
    </div>

    <div class="md:col-span-2">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3 lg:mb-4">
            <h2 class="text-lg lg:text-xl font-bold text-gray-800">Consultation History</h2>
            <button type="button" onclick="openConsultationCreateModal({{ $patient->id }})" class="inline-flex items-center justify-center px-4 py-2 rounded-xl shadow-sm text-xs lg:text-sm font-medium text-white bg-[var(--primary)] hover:bg-[var(--primary-light)] transition">
                + New Consultation
            </button>
        </div>

        @if($history->isEmpty())
            <div class="bg-white p-6 lg:p-8 rounded-xl lg:rounded-lg shadow-sm border border-dashed border-gray-300 text-center">
                <p class="text-gray-400 mb-2 text-sm">No medical records found for this patient.</p>
                <p class="text-xs lg:text-sm text-gray-500">Click "New Consultation" to create the first record.</p>
            </div>
        @else
            <div class="bg-white rounded-xl lg:rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs lg:text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Date</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Complaint</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap hidden sm:table-cell">Attended By</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Status</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($history as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($record->created_at)->format('M d, Y') }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 font-medium text-gray-800">
                                    <div>{{ $record->complaint_name ?? 'General Checkup' }}</div>
                                    <div class="text-xs text-gray-500 sm:hidden">{{ $record->worker_name ?? 'Staff' }}</div>
                                </td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 hidden sm:table-cell">{{ $record->worker_name ?? 'Staff' }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3">
                                    <span class="px-2 py-0.5 lg:py-1 rounded-full text-xs 
                                        {{ $record->status == 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('consultations.show', $record->id) }}" class="text-sky-600 hover:text-sky-800 font-semibold text-xs lg:text-sm">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

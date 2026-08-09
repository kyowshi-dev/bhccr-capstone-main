<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

final class PatientService
{
    public static function ensureTransientHousehold(): Household
    {
        $transientHousehold = Household::query()
            ->where(function ($qb) {
                $qb->whereRaw('LOWER(family_name_head) LIKE ?', ['%transient%'])
                    ->orWhereRaw('LOWER(family_name_head) LIKE ?', ['%unmapped%']);
            })
            ->select(['id', 'family_name_head'])
            ->first();

        if (! $transientHousehold) {
            $transientHousehold = Household::create([
                'zone_id' => 1,
                'family_name_head' => 'Transient/Unmapped',
            ]);
        }

        return $transientHousehold;
    }

    /**
     * Register a new patient, optionally creating the household first.
     * Rolls back the created household and throws a ValidationException on duplicates.
     */
    public static function register(array $validated): Patient
    {
        $user = auth()->user();

        // Zone-scoped workers may only attach patients to households within
        // their assigned coverage, but they may create households in any zone.
        if ($user !== null && $user->isZoneScoped()) {
            $creatingNew = (int) ($validated['create_new_household'] ?? 0) === 1;

            if (! $creatingNew && ! $user->canAccessHousehold((int) $validated['household_id'])) {
                throw ValidationException::withMessages(['household_id' => 'This household is outside your assigned zones.']);
            }
        }

        // --- 1. HANDLE HOUSEHOLD CREATION OR SELECTION ---
        $householdId = $validated['household_id'];
        $createdHousehold = null;

        if ((int) $validated['create_new_household'] === 1) {
            $createdHousehold = Household::create([
                'zone_id' => $validated['new_household_zone_id'],
                'family_name_head' => trim($validated['new_household_family_name_head']),
                'contact_number' => $validated['new_household_contact_number'] !== null ? trim($validated['new_household_contact_number']) : null,
            ]);
            $householdId = $createdHousehold->id;
        }

        $zone = Household::with('zone')->find($householdId)?->zone;
        $zoneNumber = $zone ? $zone->zone_number : '';

        $residentialAddress = trim($zoneNumber).' Sta. Ana, Tagoloan';

        // --- 2. DUPLICATE CHECK ---
        // Prevents double-entry of the same person
        $exists = Patient::query()
            ->where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->where('date_of_birth', $validated['date_of_birth'])
            ->exists();

        if ($exists) {
            // In case of duplicate, rollback household creation if we just created it
            if ($createdHousehold) {
                $createdHousehold->delete();
            }

            throw ValidationException::withMessages(['first_name' => 'This patient is already registered in the system!']);
        }

        // --- 3. INSERT PATIENT DATA (Sanitized) ---
        return Patient::create([
            'household_id' => $householdId,
            // Auto-Capitalize Names
            'first_name' => ucwords(strtolower($validated['first_name'])),
            'last_name' => ucwords(strtolower($validated['last_name'])),
            'middle_name' => $validated['middle_name'] ? ucfirst(strtolower($validated['middle_name'])) : null,

            'suffix' => $validated['suffix'],
            'sex' => $validated['sex'],
            'date_of_birth' => $validated['date_of_birth'],
            'birth_place' => $validated['birth_place'],
            'blood_type' => $validated['blood_type'],
            'civil_status' => $validated['civil_status'],
            'educational_attainment' => $validated['educational_attainment'],
            'employment_status' => $validated['employment_status'],

            'mother_name' => $validated['mother_name'],
            'spouse_name' => $validated['spouse_name'],
            'family_relationship' => $validated['family_relationship'],
            'residential_address' => $residentialAddress,
            'is_philhealth_member' => $validated['is_philhealth_member'],
            'status_type' => $validated['status_type'] ?? null,
            'philhealth_no' => $validated['philhealth_no'] ?? null,
            'membership_category' => $validated['membership_category'] ?? null,
            'is_pcb_member' => $validated['is_pcb_member'],

            'has_4ps' => $validated['has_4ps'],
            'has_nhts' => $validated['has_nhts'],
        ]);
    }
}

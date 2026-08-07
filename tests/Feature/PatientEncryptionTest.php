<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function createPatient(?string $philhealthNo): Patient
    {
        $zone = Zone::query()->create(['zone_number' => '1']);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'family_name_head' => 'Dela Cruz',
        ]);

        return Patient::query()->create([
            'household_id' => $householdId,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => null,
            'suffix' => null,
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'educational_attainment' => 'College',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria',
            'spouse_name' => 'Mercedes',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1',
            'is_philhealth_member' => $philhealthNo === null ? 'n' : 'y',
            'philhealth_no' => $philhealthNo,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
        ]);
    }

    public function test_philhealth_number_is_encrypted_at_rest(): void
    {
        $patient = $this->createPatient('123456789012');

        $raw = DB::table('patients')->where('id', $patient->id)->value('philhealth_no');

        // The stored value must not be the plaintext national ID.
        $this->assertNotNull($raw);
        $this->assertNotSame('123456789012', $raw);
        $this->assertStringNotContainsString('123456789012', (string) $raw);
    }

    public function test_philhealth_number_round_trips_through_eloquent(): void
    {
        $patient = $this->createPatient('123456789012');

        $fresh = Patient::query()->findOrFail($patient->id);

        $this->assertSame('123456789012', $fresh->philhealth_no);
    }

    public function test_null_philhealth_number_is_preserved(): void
    {
        $patient = $this->createPatient(null);

        $raw = DB::table('patients')->where('id', $patient->id)->value('philhealth_no');

        $this->assertNull($raw);
        $this->assertNull(Patient::query()->findOrFail($patient->id)->philhealth_no);
    }

    public function test_updating_philhealth_number_re_encrypts(): void
    {
        $patient = $this->createPatient('111111111111');

        $patient->update(['philhealth_no' => '222222222222']);

        $raw = DB::table('patients')->where('id', $patient->id)->value('philhealth_no');

        $this->assertStringNotContainsString('222222222222', (string) $raw);
        $this->assertSame('222222222222', Patient::query()->findOrFail($patient->id)->philhealth_no);
    }
}

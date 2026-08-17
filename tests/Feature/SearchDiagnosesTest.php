<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SearchDiagnosesTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function userWithConsultations(): User
    {
        return $this->createUserWithPermissions(['patients', 'consultations']);
    }

    private function enableIcdApi(): void
    {
        config()->set('bhcis.icd_api.enabled', true);
        config()->set('bhcis.icd_api.base_url', 'https://icd.example.com');
        config()->set('bhcis.icd_api.token_url', 'https://icd.example.com/oauth/token');
        config()->set('bhcis.icd_api.search_path', '/search');
    }

    public function test_api_results_are_upserted_and_returned_with_local_ids(): void
    {
        $this->enableIcdApi();

        Http::fake([
            'https://icd.example.com/oauth/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'https://icd.example.com/*' => Http::response([
                'results' => [
                    ['code' => 'I10', 'title' => 'Essential hypertension'],
                    ['code' => 'E11', 'title' => 'Type 2 diabetes mellitus'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=hyperten');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'text']]);

        $i10 = DB::table('diagnosis_lookup')->where('diagnosis_code', 'I10')->first();
        $e11 = DB::table('diagnosis_lookup')->where('diagnosis_code', 'E11')->first();

        $this->assertNotNull($i10);
        $this->assertSame('Essential hypertension', $i10->diagnosis_name);
        $this->assertNotNull($e11);
        $this->assertSame('Type 2 diabetes mellitus', $e11->diagnosis_name);

        $response->assertJson([
            ['id' => (int) $i10->id, 'text' => 'I10 - Essential hypertension'],
            ['id' => (int) $e11->id, 'text' => 'E11 - Type 2 diabetes mellitus'],
        ]);
    }

    public function test_api_results_dedupe_with_existing_local_rows(): void
    {
        $this->enableIcdApi();

        $existingId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10',
            'diagnosis_name' => 'Essential hypertension',
            'category' => 'Cardiovascular',
        ]);

        Http::fake([
            'https://icd.example.com/oauth/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'https://icd.example.com/*' => Http::response([
                'results' => [
                    ['code' => 'I10', 'title' => 'Essential hypertension'],
                    ['code' => 'E11', 'title' => 'Type 2 diabetes mellitus'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=hyperten');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $existingId, 'text' => 'I10 - Essential hypertension']);

        $this->assertSame(
            $existingId,
            DB::table('diagnosis_lookup')->where('diagnosis_code', 'I10')->value('id')
        );
        $this->assertSame(
            1,
            DB::table('diagnosis_lookup')->where('diagnosis_code', 'I10')->count()
        );
    }

    public function test_local_fallback_when_api_disabled(): void
    {
        $localId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=upper');

        $response->assertOk()
            ->assertJson([['id' => $localId, 'text' => 'J06.9 - Acute upper respiratory infection']]);
    }

    public function test_local_fallback_when_api_returns_empty_results(): void
    {
        $this->enableIcdApi();

        $localId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        Http::fake([
            'https://icd.example.com/oauth/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'https://icd.example.com/*' => Http::response(['results' => []]),
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=upper');

        $response->assertOk()
            ->assertJson([['id' => $localId, 'text' => 'J06.9 - Acute upper respiratory infection']]);
    }

    public function test_local_fallback_when_api_errors(): void
    {
        $this->enableIcdApi();

        $localId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        Http::fake([
            'https://icd.example.com/oauth/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'https://icd.example.com/*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=upper');

        $response->assertOk()
            ->assertJson([['id' => $localId, 'text' => 'J06.9 - Acute upper respiratory infection']]);
    }

    public function test_api_sourced_diagnosis_can_be_saved(): void
    {
        $this->enableIcdApi();

        Http::fake([
            'https://icd.example.com/oauth/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'https://icd.example.com/*' => Http::response([
                'results' => [
                    ['code' => 'E11', 'title' => 'Type 2 diabetes mellitus'],
                ],
            ]),
        ]);

        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fatigue',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.8,
            'weight' => 65,
            'height' => 170,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/acknowledge-intake");

        $search = $this->actingAs($doctor)->getJson('/search/diagnoses?query=diabetes');
        $search->assertOk();
        $diagnosisId = $search->json()[0]['id'];
        $this->assertIsInt($diagnosisId);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
        ])->assertRedirect();

        $this->assertSame(
            $diagnosisId,
            (int) DB::table('diagnosis_records')->where('consultation_id', $consultationId)->value('diagnosis_id')
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createClinicalFixture(string $role): array
    {
        $user = $this->createWorkerUser($role);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Jane Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $patientId];
    }

    private function createWorkerUser(string $role): User
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $role,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}

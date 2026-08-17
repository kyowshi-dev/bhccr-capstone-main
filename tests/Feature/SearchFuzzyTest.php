<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SearchFuzzyTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'household', 'description' => 'Access to Households module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        return $this->createUserWithPermissions($permissions);
    }

    private function insertPatient(array $attributes): int
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => '1']);

        $householdId = DB::table('households')->where('id', 1)->value('id')
            ?? DB::table('households')->insertGetId([
                'id' => 1,
                'zone_id' => 1,
                'family_name_head' => 'Test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('patients')->insertGetId(array_merge([
            'household_id' => $householdId,
            'first_name' => 'Juan',
            'last_name' => 'Doe',
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'N/A',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Others',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    public function test_patient_search_returns_prefix_matches_without_fuzzy(): void
    {
        $this->insertPatient(['first_name' => 'Maria', 'last_name' => 'Garcia']);
        $this->insertPatient(['first_name' => 'Pedro', 'last_name' => 'Gonzales']);

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/patients?query=Gar');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['text' => 'Garcia, Maria']);
    }

    public function test_patient_search_tolerates_typos_via_fuzzy_rerank(): void
    {
        $garciaId = $this->insertPatient(['first_name' => 'Maria', 'last_name' => 'Garcia']);
        $this->insertPatient(['first_name' => 'Pedro', 'last_name' => 'Gonzales']);

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/patients?query=garsia');

        $response->assertOk()
            ->assertJsonFragment(['id' => $garciaId, 'text' => 'Garcia, Maria']);
    }

    public function test_patient_search_tolerates_missing_diacritics(): void
    {
        $penaId = $this->insertPatient(['first_name' => 'Ana', 'last_name' => 'Peña']);

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/patients?query=Pena');

        $response->assertOk()
            ->assertJsonFragment(['id' => $penaId, 'text' => 'Peña, Ana']);
    }

    public function test_patient_search_returns_empty_for_irrelevant_queries(): void
    {
        $this->insertPatient(['first_name' => 'Maria', 'last_name' => 'Garcia']);

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/patients?query=zzzzz');

        $response->assertOk()
            ->assertJson([]);
    }

    public function test_household_search_tolerates_typos_via_fuzzy_rerank(): void
    {
        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'id' => 1,
            'zone_id' => 1,
            'family_name_head' => 'Del Rosario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createUserWithPermissions(['household']);
        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Zone',
            'last_name' => 'Worker',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $response = $this->actingAs($user)
            ->getJson('/search/households?query=delrssario');

        $response->assertOk()
            ->assertJsonFragment(['id' => $householdId, 'text' => 'Del Rosario']);
    }

    public function test_household_fuzzy_search_respects_zone_scoping(): void
    {
        DB::table('zones')->insert([
            ['id' => 1, 'zone_number' => '1'],
            ['id' => 2, 'zone_number' => '2'],
        ]);
        DB::table('households')->insertGetId([
            'id' => 1,
            'zone_id' => 1,
            'family_name_head' => 'Del Rosario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('households')->insertGetId([
            'id' => 2,
            'zone_id' => 2,
            'family_name_head' => 'Dela Rosa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createUserWithPermissions(['household']);
        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Zone',
            'last_name' => 'Worker',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $response = $this->actingAs($user)
            ->getJson('/search/households?query=delrssario');

        $response->assertOk()
            ->assertJsonFragment(['id' => 1, 'text' => 'Del Rosario'])
            ->assertJsonMissing(['id' => 2]);
    }

    public function test_diagnosis_name_search_tolerates_typos_via_fuzzy_rerank(): void
    {
        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10',
            'diagnosis_name' => 'Essential hypertension',
        ]);

        $response = $this->actingAs($this->userWithPermissions(['consultations']))
            ->getJson('/search/diagnoses?query=hypertensio');

        $response->assertOk()
            ->assertJsonFragment(['id' => $diagnosisId, 'text' => 'I10 - Essential hypertension']);
    }

    public function test_diagnosis_code_search_is_never_fuzzed(): void
    {
        DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10',
            'diagnosis_name' => 'Essential hypertension',
        ]);

        $response = $this->actingAs($this->userWithPermissions(['consultations']))
            ->getJson('/search/diagnoses?query=I20');

        $response->assertOk()
            ->assertJson([]);
    }
}

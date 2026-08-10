<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\FamilyPlanningVisit;
use App\Models\HealthWorker;
use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalFamilyPlanningTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'maternal',
            'description' => 'Maternal care module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorizedUser(): User
    {
        $user = $this->createUserWithPermissions(['maternal']);

        HealthWorker::create([
            'user_id' => $user->id,
            'first_name' => 'Midwife',
            'last_name' => 'Test',
            'role' => 'Midwife',
        ]);

        return $user;
    }

    private function patient(): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Reyes'])->id,
            'first_name' => 'Liza',
            'last_name' => 'Reyes',
            'sex' => 'Female',
            'date_of_birth' => '1995-08-15',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Marco Reyes',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    public function test_registers_new_acceptor_client_as_active(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.family-planning.store', $patient->id), [
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Injectable',
            'schedule_next_visit' => now()->addMonths(3)->toDateString(),
        ])->assertRedirect(route('maternal.family-planning.patient', $patient->id));

        $client = FamilyPlanningClient::where('patient_id', $patient->id)->firstOrFail();

        $this->assertTrue($client->is_active);
        $this->assertSame('Injectable', $client->method);
        $this->assertNotNull($client->schedule_next_visit);
    }

    public function test_drop_out_requires_reason(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.family-planning.store', $patient->id), [
            'type_of_client' => FamilyPlanningClient::TYPE_DROP_OUT,
            'method' => 'Pills',
        ])->assertSessionHasErrors('drop_out_reason');

        $this->assertDatabaseCount('family_planning_clients', 0);
    }

    public function test_drop_out_client_is_inactive(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.family-planning.store', $patient->id), [
            'type_of_client' => FamilyPlanningClient::TYPE_DROP_OUT,
            'method' => 'Pills',
            'drop_out_reason' => 'Side effects',
        ])->assertRedirect();

        $this->assertFalse(FamilyPlanningClient::where('patient_id', $patient->id)->firstOrFail()->is_active);
    }

    public function test_visit_rolls_method_and_schedule_forward(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Pills',
            'is_active' => true,
        ]);

        $this->post(route('maternal.family-planning.visits.store', $client->id), [
            'visit_date' => now()->toDateString(),
            'method' => 'IUD',
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
            'schedule_next_visit' => now()->addYear()->toDateString(),
        ])->assertRedirect(route('maternal.family-planning.patient', $patient->id));

        $this->assertDatabaseCount('family_planning_visits', 1);

        $client->refresh();

        $this->assertSame('IUD', $client->method);
        $this->assertTrue($client->is_active);
        $this->assertNotNull($client->schedule_next_visit);
    }

    public function test_visit_reactivates_drop_out_as_continuing_user(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_DROP_OUT,
            'method' => 'Pills',
            'drop_out_reason' => 'Side effects',
            'is_active' => false,
        ]);

        $this->post(route('maternal.family-planning.visits.store', $client->id), [
            'visit_date' => now()->toDateString(),
            'method' => 'Condom',
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
        ])->assertRedirect();

        $client->refresh();

        $this->assertSame(FamilyPlanningClient::TYPE_CONTINUING_USER, $client->type_of_client);
        $this->assertTrue($client->is_active);
        $this->assertSame('Condom', $client->method);
    }

    public function test_visit_log_is_kept_on_client(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_CONTINUING_USER,
            'method' => 'Injectable',
            'is_active' => true,
        ]);

        FamilyPlanningVisit::create([
            'client_id' => $client->id,
            'visit_date' => now()->subMonth()->toDateString(),
            'method' => 'Injectable',
        ]);

        $this->assertSame(1, $client->visits()->count());
    }

    public function test_index_loads_clients_for_authorized_user(): void
    {
        $this->actingAs($this->authorizedUser());

        $this->get(route('maternal.family-planning.index'))
            ->assertOk()
            ->assertViewHas('clients');
    }
}

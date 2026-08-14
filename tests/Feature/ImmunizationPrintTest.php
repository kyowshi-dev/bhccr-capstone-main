<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationPrintTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'immunizations',
            'description' => 'Immunizations module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function userWithPermission(): User
    {
        return $this->createUserWithPermissions(['immunizations']);
    }

    private function infant(array $overrides = []): Patient
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        return Patient::create(array_merge([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'sex' => 'Male',
            'date_of_birth' => now()->subDays(100)->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria Santos',
            'spouse_name' => 'Juan Dela Cruz',
            'guardian_name' => 'Lola Nena',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana',
            'birth_place' => 'Sta. Ana',
            'birth_weight' => 3.20,
            'is_immunization_enrolled' => true,
        ], $overrides));
    }

    private function seedChildVaccines(): void
    {
        DB::table('vaccines_lookup')->insertOrIgnore([
            [
                'id' => 1,
                'vaccine_code' => 'BCG',
                'vaccine_name' => 'BCG',
                'description' => 'At birth',
                'category' => 'Child',
                'sort_order' => 1,
                'start_after_days' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'vaccine_code' => 'PENTA',
                'vaccine_name' => 'PENTA',
                'description' => '6, 10 and 14 weeks',
                'category' => 'Child',
                'sort_order' => 2,
                'start_after_days' => 42,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('vaccine_schedules')->insertOrIgnore([
            ['vaccine_id' => 1, 'dose_number' => 1, 'min_age_days' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['vaccine_id' => 2, 'dose_number' => 1, 'min_age_days' => 42, 'created_at' => now(), 'updated_at' => now()],
            ['vaccine_id' => 2, 'dose_number' => 2, 'min_age_days' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['vaccine_id' => 2, 'dose_number' => 3, 'min_age_days' => 98, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_print_card_guest_is_redirected_to_login(): void
    {
        $infant = $this->infant();

        $this->get(route('immunizations.print-card', $infant->id))->assertRedirect(route('login'));
    }

    public function test_print_card_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));
        $infant = $this->infant();

        $this->get(route('immunizations.print-card', $infant->id))->assertForbidden();
    }

    public function test_print_card_pdf_guest_is_redirected_to_login(): void
    {
        $infant = $this->infant();

        $this->get(route('immunizations.print-card.pdf', $infant->id))->assertRedirect(route('login'));
    }

    public function test_print_card_pdf_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));
        $infant = $this->infant();

        $this->get(route('immunizations.print-card.pdf', $infant->id))->assertForbidden();
    }

    public function test_print_card_renders_child_information(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant();

        $this->get(route('immunizations.print-card', $infant->id))
            ->assertOk()
            ->assertSee('CHILD IMMUNIZATION RECORD', false)
            ->assertSee('REKORD NG BAKUNA NG BATA', false)
            ->assertSee('Dela Cruz, Baby S.', false)
            ->assertSee('Maria Santos', false)
            ->assertSee('Juan Dela Cruz', false)
            ->assertSee('Sta. Ana Health Center', false)
            ->assertSee('Zone 1', false)
            ->assertSee('3.20 kg', false)
            ->assertSee('Sta. Ana', false);
    }

    public function test_print_card_falls_back_to_guardian_when_no_spouse(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant(['spouse_name' => '']);

        $this->get(route('immunizations.print-card', $infant->id))
            ->assertOk()
            ->assertSee('Lola Nena', false)
            ->assertDontSee('Juan Dela Cruz', false);
    }

    public function test_print_card_renders_vaccine_table_with_dose_schedules(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant();
        $this->seedChildVaccines();

        DB::table('immunization_records')->insert([
            'patient_id' => $infant->id,
            'vaccine_id' => 1,
            'dose_number' => 1,
            'date_given' => now()->subDays(90)->toDateString(),
            'notes' => 'Well tolerated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('immunizations.print-card', $infant->id))
            ->assertOk()
            ->assertSee('1 (pagkapanganak)', false)
            ->assertSee('3 (1½ buwan, 2½ buwan, 3½ buwan)', false)
            ->assertSee('BCG', false)
            ->assertSee('PENTA', false)
            ->assertSee('D1:', false)
            ->assertSee('Well tolerated', false);
    }

    public function test_print_card_renders_for_adult_patients(): void
    {
        $this->actingAs($this->userWithPermission());
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        $adult = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Adult',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);

        $this->get(route('immunizations.print-card', $adult->id))
            ->assertOk()
            ->assertSee('IMMUNIZATION RECORD', false)
            ->assertDontSee('CHILD IMMUNIZATION RECORD', false);
    }

    public function test_patient_page_shows_print_record_button(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant();

        $this->get(route('immunizations.patient', $infant->id))
            ->assertOk()
            ->assertSee('Print record', false)
            ->assertSee(route('immunizations.print-card', $infant->id), false);
    }

    public function test_checkin_fragment_shows_print_shortcut(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant();

        $this->get(route('immunizations.checkin', $infant->id))
            ->assertOk()
            ->assertSee('Print record', false)
            ->assertSee(route('immunizations.print-card', $infant->id), false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalPostnatalTest extends TestCase
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
        return $this->createUserWithPermissions(['maternal']);
    }

    private function mother(): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Garcia'])->id,
            'first_name' => 'Rosa',
            'last_name' => 'Garcia',
            'sex' => 'Female',
            'date_of_birth' => '1991-11-02',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Jose Garcia',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_LIVE_BIRTH,
            'place_delivered' => 'hospital',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->toDateString(),
            'delivery_time' => '10:30',
            'breastfeeding_date' => now()->toDateString(),
            'breastfeeding_time' => '11:00',
            'child_last_name' => 'Garcia',
            'child_first_name' => 'Baby Rosa',
            'child_sex' => 'F',
        ], $overrides);
    }

    public function test_standalone_postnatal_entry_without_pregnancy(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())
            ->assertRedirect(route('maternal.postnatal.patient', $mother->id));

        $record = PostnatalRecord::where('patient_id', $mother->id)->firstOrFail();

        $this->assertNull($record->pregnancy_id);
        $this->assertSame(PostnatalRecord::OUTCOME_LIVE_BIRTH, $record->pregnancy_outcome);
        $this->assertNotNull($record->childPatient);
    }

    public function test_postnatal_patient_page_renders_successfully(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())
            ->assertRedirect();

        $this->get(route('maternal.postnatal.patient', $mother->id))
            ->assertOk()
            ->assertSee('Postpartum schedule');
    }

    public function test_linked_pregnancy_is_marked_delivered(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $pregnancy = Pregnancy::create([
            'patient_id' => $mother->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2025-11-10',
            'edc' => '2026-08-17',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload([
            'pregnancy_id' => $pregnancy->id,
        ]))->assertRedirect();

        $this->assertSame(Pregnancy::STATUS_DELIVERED, $pregnancy->fresh()->status);
        $this->assertSame($pregnancy->id, PostnatalRecord::where('patient_id', $mother->id)->value('pregnancy_id'));
    }

    public function test_pregnancy_must_belong_to_the_mother(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();
        $other = $this->mother();
        $otherPregnancy = Pregnancy::create([
            'patient_id' => $other->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2025-11-10',
            'edc' => '2026-08-17',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload([
            'pregnancy_id' => $otherPregnancy->id,
        ]))->assertSessionHasErrors('pregnancy_id');

        $this->assertDatabaseCount('postnatal_records', 0);
    }

    public function test_child_is_enrolled_with_mapped_sex_and_duplicate_guard(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())->assertRedirect();

        $child = Patient::where('household_id', $mother->household_id)->where('first_name', 'Baby Rosa')->firstOrFail();

        $this->assertSame('Female', $child->sex);
        $this->assertSame('Daughter', $child->family_relationship);
        $this->assertSame($mother->household_id, $child->household_id);
        $this->assertSame($child->id, PostnatalRecord::where('patient_id', $mother->id)->value('child_patient_id'));

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())
            ->assertSessionHasErrors('child_first_name');

        $this->assertSame(2, Patient::where('household_id', $mother->household_id)->count());
    }

    public function test_danger_signs_persist_as_json_and_flag_high_risk(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload([
            'danger_signs_mother' => ['Fever', 'Vaginal bleeding'],
            'danger_signs_baby' => ['Poor feeding'],
        ]))->assertRedirect();

        $record = PostnatalRecord::where('patient_id', $mother->id)->firstOrFail();

        $this->assertSame(['Fever', 'Vaginal bleeding'], $record->danger_signs_mother);
        $this->assertSame(['Poor feeding'], $record->danger_signs_baby);
    }

    public function test_stillbirth_stores_without_newborn_fields_and_skips_child_enrollment(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload([
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_STILLBIRTH,
            'child_last_name' => null,
            'child_first_name' => null,
            'child_sex' => null,
        ]))->assertRedirect();

        $record = PostnatalRecord::where('patient_id', $mother->id)->firstOrFail();

        $this->assertSame(PostnatalRecord::OUTCOME_STILLBIRTH, $record->pregnancy_outcome);
        $this->assertNull($record->child_last_name);
        $this->assertNull($record->child_first_name);
        $this->assertNull($record->child_sex);
        $this->assertNull($record->child_patient_id);
        $this->assertSame(1, Patient::where('household_id', $mother->household_id)->count());
    }

    public function test_non_live_birth_outcome_rejects_newborn_fields(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload([
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_ABORTION,
        ]))->assertSessionHasErrors(['child_first_name', 'child_last_name', 'child_sex']);

        $this->assertDatabaseCount('postnatal_records', 0);
    }

    public function test_changing_outcome_to_stillbirth_clears_newborn_data(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())->assertRedirect();

        $record = PostnatalRecord::where('patient_id', $mother->id)->firstOrFail();

        $this->assertNotNull($record->child_patient_id);

        $this->put(route('maternal.postnatal.update', $record->id), $this->validPayload([
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_STILLBIRTH,
            'child_last_name' => null,
            'child_first_name' => null,
            'child_sex' => null,
        ]))->assertRedirect();

        $record->refresh();

        $this->assertSame(PostnatalRecord::OUTCOME_STILLBIRTH, $record->pregnancy_outcome);
        $this->assertNull($record->child_last_name);
        $this->assertNull($record->child_first_name);
        $this->assertNull($record->child_sex);
        $this->assertNull($record->child_patient_id);
    }

    public function test_postnatal_index_loads_for_authorized_user(): void
    {
        $this->actingAs($this->authorizedUser());

        $this->get(route('maternal.postnatal.index'))
            ->assertOk()
            ->assertViewHas('records');
    }

    public function test_stillbirth_saves_when_newborn_fields_not_submitted(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $payload = $this->validPayload([
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_STILLBIRTH,
        ]);
        unset(
            $payload['child_last_name'],
            $payload['child_first_name'],
            $payload['child_sex'],
        );

        $this->post(route('maternal.postnatal.store', $mother->id), $payload)
            ->assertRedirect(route('maternal.postnatal.patient', $mother->id));

        $record = PostnatalRecord::where('patient_id', $mother->id)->firstOrFail();

        $this->assertSame(PostnatalRecord::OUTCOME_STILLBIRTH, $record->pregnancy_outcome);
        $this->assertNull($record->child_last_name);
        $this->assertNull($record->child_sex);
        $this->assertNull($record->child_patient_id);
    }

    public function test_duplicate_child_rejects_before_creating_orphan_record(): void
    {
        $this->actingAs($this->authorizedUser());
        $mother = $this->mother();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())->assertRedirect();

        $this->post(route('maternal.postnatal.store', $mother->id), $this->validPayload())
            ->assertSessionHasErrors('child_first_name');

        $this->assertSame(1, PostnatalRecord::where('patient_id', $mother->id)->count());
        $this->assertSame(2, Patient::where('household_id', $mother->household_id)->count());
    }
}

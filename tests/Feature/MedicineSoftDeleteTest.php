<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MedicineSoftDeleteTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function createMedicine(string $name): Medicine
    {
        return Medicine::query()->create([
            'name' => $name,
            'form' => 'Tablet',
        ]);
    }

    public function test_delete_moves_medicine_to_trash_instead_of_removing_it(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $medicine = $this->createMedicine('Paracetamol 500mg');

        $this->delete("/medicines/{$medicine->id}")->assertStatus(302);

        $this->assertSoftDeleted('medicines_lookup', ['id' => $medicine->id]);
        $this->assertNotNull(DB::table('medicines_lookup')->where('id', $medicine->id)->value('deleted_at'));
    }

    public function test_trashed_medicine_can_be_restored(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $medicine = $this->createMedicine('Paracetamol 500mg');
        $medicine->delete();

        $this->post("/medicines/{$medicine->id}/restore")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('medicines_lookup', ['id' => $medicine->id]);
        $this->assertNull(DB::table('medicines_lookup')->where('id', $medicine->id)->value('deleted_at'));
    }

    public function test_trashed_medicines_are_hidden_from_index(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $active = $this->createMedicine('ActiveUniquelyNamedMedicine');
        $trashed = $this->createMedicine('TrashedUniquelyNamedMedicine');
        $trashed->delete();

        $response = $this->get('/medicines');

        $response->assertStatus(200);
        $response->assertSee('ActiveUniquelyNamedMedicine');
        $response->assertDontSee('TrashedUniquelyNamedMedicine');
        $response->assertViewHas('medicines', function ($medicines) use ($active) {
            return $medicines->total() === 1 && $medicines->first()->id === $active->id;
        });
    }

    public function test_archived_filter_lists_only_archived_medicines(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $active = $this->createMedicine('ActiveUniquelyNamedMedicine');
        $trashed = $this->createMedicine('TrashedUniquelyNamedMedicine');
        $trashed->delete();

        $response = $this->get('/medicines?status=archived');

        $response->assertStatus(200);
        $response->assertSee('TrashedUniquelyNamedMedicine');
        $response->assertDontSee('ActiveUniquelyNamedMedicine');
        $response->assertViewHas('medicines', function ($medicines) use ($trashed) {
            return $medicines->total() === 1 && $medicines->first()->id === $trashed->id;
        });
        $response->assertViewHas('status', 'archived');
    }

    public function test_all_filter_lists_active_and_archived_medicines(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $active = $this->createMedicine('ActiveUniquelyNamedMedicine');
        $trashed = $this->createMedicine('TrashedUniquelyNamedMedicine');
        $trashed->delete();

        $response = $this->get('/medicines?status=all');

        $response->assertStatus(200);
        $response->assertSee('ActiveUniquelyNamedMedicine');
        $response->assertSee('TrashedUniquelyNamedMedicine');
        $response->assertViewHas('medicines', fn ($medicines) => $medicines->total() === 2);
        $response->assertViewHas('status', 'all');
    }

    public function test_invalid_status_falls_back_to_active(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $this->get('/medicines?status=bogus')
            ->assertStatus(200)
            ->assertViewHas('status', 'active');
    }

    public function test_trashed_medicines_are_excluded_from_search(): void
    {
        $user = $this->createUserWithPermissions(['consultations']);
        $this->actingAs($user);

        $medicine = $this->createMedicine('UniqueSearchMedicine');
        $medicine->delete();

        $response = $this->getJson('/search/medicines?query=UniqueSearch');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_bulk_delete_soft_deletes_selected_medicines(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $first = $this->createMedicine('Medicine One');
        $second = $this->createMedicine('Medicine Two');

        $this->post('/medicines/bulk-delete', [
            'ids' => [$first->id, $second->id],
        ])->assertStatus(302)->assertSessionHas('success');

        $this->assertSoftDeleted('medicines_lookup', ['id' => $first->id]);
        $this->assertSoftDeleted('medicines_lookup', ['id' => $second->id]);
    }

    public function test_bulk_restore_restores_selected_medicines(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $first = $this->createMedicine('Medicine One');
        $second = $this->createMedicine('Medicine Two');
        $first->delete();
        $second->delete();

        $this->post('/medicines/bulk-restore', [
            'ids' => [$first->id, $second->id],
        ])->assertStatus(302)->assertSessionHas('success');

        $this->assertNotSoftDeleted('medicines_lookup', ['id' => $first->id]);
        $this->assertNotSoftDeleted('medicines_lookup', ['id' => $second->id]);
    }

    public function test_bulk_restore_skips_active_medicines(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $active = $this->createMedicine('Medicine One');
        $trashed = $this->createMedicine('Medicine Two');
        $trashed->delete();

        $this->post('/medicines/bulk-restore', [
            'ids' => [$active->id, $trashed->id],
        ])->assertStatus(302)->assertSessionHas('success');

        $this->assertNotSoftDeleted('medicines_lookup', ['id' => $trashed->id]);
        $this->assertNull(DB::table('medicines_lookup')->where('id', $active->id)->value('deleted_at'));
    }

    public function test_medicine_with_same_name_as_trashed_one_can_be_recreated(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        $medicine = $this->createMedicine('Reusable Name');
        $medicine->delete();

        $this->post('/medicines', [
            'name' => 'Reusable Name',
            'form' => 'Capsule',
        ])->assertStatus(302)->assertSessionHas('success');

        $this->assertSame(2, Medicine::withTrashed()->where('name', 'Reusable Name')->count());
        $this->assertSame(1, Medicine::query()->where('name', 'Reusable Name')->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->actingAs($user)
            ->put(route('profile.update'), ['profile_photo' => $photo])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('success');

        $path = $user->fresh()->profile_photo_path;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('profile-photos/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_profile_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $first = UploadedFile::fake()->image('one.jpg', 100, 100);
        $second = UploadedFile::fake()->image('two.jpg', 100, 100);

        $this->actingAs($user)->put(route('profile.update'), ['profile_photo' => $first]);

        $firstPath = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->put(route('profile.update'), ['profile_photo' => $second]);

        $secondPath = $user->fresh()->profile_photo_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_profile_photo_is_optional_and_bio_is_saved(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), ['bio' => 'Community health worker'])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('Community health worker', $user->fresh()->bio);
        $this->assertNull($user->fresh()->profile_photo_path);
    }
}

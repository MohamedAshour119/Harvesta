<?php

namespace Tests\Services\Unit\Profile;

use App\Http\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class ProfileServiceAccountTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ProfileService::class;
    }

    public function test_update_profile_persists_the_new_full_name(): void
    {
        $user = User::factory()->create(['full_name' => 'Original Name']);
        $this->actingAs($user);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->updateProfile(['full_name' => 'Updated Name']);

        $this->assertSame('Updated Name', $result['full_name']);
        $this->assertSame('Updated Name', $user->fresh()->full_name);
    }

    public function test_delete_account_revokes_tokens_and_soft_deletes_the_user(): void
    {
        $user = User::factory()->create();
        $user->createToken('User Access Token');
        $user->createToken('User Refresh Token');
        $this->actingAs($user);

        $service = $this->instantiateService($this->serviceClass());
        $service->deleteAccount();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}

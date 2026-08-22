<?php

namespace Tests\Repositories\Auth;

use App\Http\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryLogoutTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }

    public function test_logout_user_deletes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('User Access Token', [], now()->addMinutes(15));
        $user->createToken('User Refresh Token', [], now()->addDays(30));

        $this->repository->logoutUser($user);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_logout_using_refresh_token_deletes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('User Access Token', [], now()->addMinutes(15));
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $this->repository->logoutUsingRefreshToken($refreshToken);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_logout_using_refresh_token_rejects_bad_inputs(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid refresh token format');

        $this->repository->logoutUsingRefreshToken('not-a-sanctum-token');
    }

    public function test_logout_using_refresh_token_rejects_access_tokens(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token type');

        $this->repository->logoutUsingRefreshToken($accessToken);
    }

    public function test_logout_using_refresh_token_rejects_invalid_token_hash(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->repository->logoutUsingRefreshToken($this->tokenIdFromPlainToken($refreshToken) . '|wrong-token');
    }

    private function tokenIdFromPlainToken(string $plainToken): int
    {
        return (int) explode('|', $plainToken, 2)[0];
    }
}

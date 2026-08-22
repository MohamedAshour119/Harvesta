<?php

namespace Tests\Services\Unit\Auth;

use App\Http\Services\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthService::class);
    }

    public function test_refresh_token_rotates_refresh_token_and_replaces_access_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'service-refresh@example.com',
            'password' => Hash::make('password123'),
        ]);
        $loginResult = $this->service->loginUser([
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $oldAccessTokenId = $this->tokenIdFromPlainToken($loginResult['access_token']['plain_token']);
        $oldRefreshToken = $loginResult['refresh_token']['plain_token'];
        $oldRefreshTokenId = $this->tokenIdFromPlainToken($oldRefreshToken);

        $refreshResult = $this->service->refreshToken($oldRefreshToken);

        $this->assertNotSame($oldRefreshToken, $refreshResult['refresh_token']['plain_token']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldAccessTokenId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldRefreshTokenId]);
        $this->assertSame(1, $user->tokens()->where('name', 'User Access Token')->count());
        $this->assertSame(1, $user->tokens()->where('name', 'User Refresh Token')->count());
    }

    public function test_logout_user_deletes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('User Access Token', [], now()->addMinutes(15));
        $user->createToken('User Refresh Token', [], now()->addDays(30));

        $this->service->logoutUser($user);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_logout_using_refresh_token_deletes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('User Access Token', [], now()->addMinutes(15));
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $this->service->logoutUsingRefreshToken($refreshToken);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_verify_token_returns_repository_auth_payload(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;
        $user->createToken('User Refresh Token', [], now()->addDays(30));

        $result = $this->service->verifyToken($accessToken);

        $this->assertTrue($result['user']->is($user));
        $this->assertSame($accessToken, $result['access_token']['plain_token']);
        $this->assertNull($result['refresh_token']['plain_token']);
        $this->assertNotNull($result['refresh_token']['expires_at']);
    }

    private function tokenIdFromPlainToken(string $plainToken): int
    {
        return (int) explode('|', $plainToken, 2)[0];
    }
}

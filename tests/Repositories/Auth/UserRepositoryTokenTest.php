<?php

namespace Tests\Repositories\Auth;

use App\Http\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTokenTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }

    public function test_refresh_token_returns_user_and_deletes_used_refresh_token(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;
        $refreshTokenId = $this->tokenIdFromPlainToken($refreshToken);

        $result = $this->repository->refreshToken($refreshToken);

        $this->assertTrue($result->is($user));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $refreshTokenId]);
    }

    public function test_refresh_token_rejects_expired_tokens(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('User Refresh Token', [], now()->subMinute())->plainTextToken;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Refresh token has expired');

        $this->repository->refreshToken($refreshToken);
    }

    public function test_refresh_token_rejects_wrong_token_type_and_invalid_hash(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;

        try {
            $this->repository->refreshToken($accessToken);
            $this->fail('Access tokens must not be accepted as refresh tokens.');
        } catch (\Exception $exception) {
            $this->assertSame('Invalid token type', $exception->getMessage());
        }

        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->repository->refreshToken($this->tokenIdFromPlainToken($refreshToken) . '|wrong-token');
    }

    public function test_verify_token_returns_user_access_token_and_refresh_expiry(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30));

        $result = $this->repository->verifyToken($accessToken);

        $this->assertTrue($result['user']->is($user));
        $this->assertSame($accessToken, $result['access_token']['plain_token']);
        $this->assertNull($result['refresh_token']['plain_token']);
        $this->assertTrue($refreshToken->accessToken->expires_at->equalTo($result['refresh_token']['expires_at']));
    }

    public function test_verify_token_rejects_refresh_expired_and_tampered_tokens(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        try {
            $this->repository->verifyToken($refreshToken);
            $this->fail('Refresh tokens must not be accepted as access tokens.');
        } catch (\Exception $exception) {
            $this->assertSame('Invalid token type', $exception->getMessage());
        }

        $expiredAccessToken = $user->createToken('User Access Token', [], now()->subMinute())->plainTextToken;

        try {
            $this->repository->verifyToken($expiredAccessToken);
            $this->fail('Expired access tokens must not be accepted.');
        } catch (\Exception $exception) {
            $this->assertSame('Token has expired', $exception->getMessage());
        }

        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token');

        $this->repository->verifyToken($this->tokenIdFromPlainToken($accessToken) . '|wrong-token');
    }

    private function tokenIdFromPlainToken(string $plainToken): int
    {
        return (int) explode('|', $plainToken, 2)[0];
    }
}

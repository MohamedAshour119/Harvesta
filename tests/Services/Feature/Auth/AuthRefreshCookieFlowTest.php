<?php

namespace Tests\Services\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithAuthCookies;
use Tests\TestCase;

class AuthRefreshCookieFlowTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuthCookies;

    public function test_login_sets_http_only_refresh_cookie_without_exposing_refresh_plaintext(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_agent' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.refresh_token.plain_token', null)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'access_token' => ['plain_token', 'expires_at'],
                    'refresh_token' => ['plain_token', 'expires_at'],
                ],
            ]);

        $this->assertRefreshCookieIsHttpOnly($response);
    }

    public function test_refresh_uses_cookie_and_rotates_refresh_token(): void
    {
        $loginResponse = $this->postJson('/api/auth/register', [
            'full_name' => 'Cookie Customer',
            'email' => 'cookie-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $oldRefreshToken = $this->refreshCookieValueFrom($loginResponse);

        $refreshResponse = $this
            ->withCredentials()
            ->withUnencryptedCookie('refresh_token', $oldRefreshToken)
            ->postJson('/api/auth/refresh-token');

        $refreshResponse
            ->assertOk()
            ->assertJsonPath('data.refresh_token.plain_token', null);

        $newRefreshToken = $this->refreshCookieValueFrom($refreshResponse);

        $this->assertNotSame($oldRefreshToken, $newRefreshToken);

        $this
            ->withCredentials()
            ->withUnencryptedCookie('refresh_token', $oldRefreshToken)
            ->postJson('/api/auth/refresh-token')
            ->assertStatus(401);
    }

    public function test_logout_deletes_tokens_and_expires_refresh_cookie(): void
    {
        $loginResponse = $this->postJson('/api/auth/register', [
            'full_name' => 'Logout Customer',
            'email' => 'logout-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $accessToken = $loginResponse->json('data.access_token.plain_token');
        $refreshToken = $this->refreshCookieValueFrom($loginResponse);

        $logoutResponse = $this
            ->withCredentials()
            ->withHeader('Authorization', 'Bearer '.$accessToken)
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/auth/logout');

        $logoutResponse->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertRefreshCookieIsCleared($logoutResponse);
    }
}

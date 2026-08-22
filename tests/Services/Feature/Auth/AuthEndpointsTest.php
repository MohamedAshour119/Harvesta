<?php

namespace Tests\Services\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\InteractsWithAuthCookies;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuthCookies;

    public function test_register_returns_access_token_and_sets_refresh_cookie(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Registered Customer',
            'email' => 'registered@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.email', 'registered@example.com')
            ->assertJsonPath('data.refresh_token.plain_token', null)
            ->assertJsonStructure([
                'data' => [
                    'access_token' => ['plain_token', 'expires_at'],
                    'refresh_token' => ['plain_token', 'expires_at'],
                ],
            ]);

        $this->assertRefreshCookieIsHttpOnly($response);
        $this->assertDatabaseHas('users', ['email' => 'registered@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_login_rejects_invalid_credentials_without_setting_refresh_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'login-fail@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('status', 401);

        $this->assertFalse($this->responseHasRefreshCookie($response));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_refresh_without_token_returns_401_and_clears_refresh_cookie(): void
    {
        $response = $this->postJson('/api/auth/refresh-token');

        $response
            ->assertStatus(401)
            ->assertJsonPath('message', 'No refresh token provided');

        $this->assertRefreshCookieIsCleared($response);
    }

    public function test_refresh_accepts_body_refresh_token_for_legacy_clients(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $response = $this->postJson('/api/auth/refresh-token', [
            'refresh_token' => $refreshToken,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.refresh_token.plain_token', null);

        $this->assertRefreshCookieIsHttpOnly($response);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $this->tokenIdFromPlainToken($refreshToken),
        ]);
        $this->assertSame(2, $user->tokens()->count());
    }

    public function test_logout_with_refresh_cookie_only_deletes_tokens_and_clears_cookie(): void
    {
        $loginResponse = $this->postJson('/api/auth/register', [
            'full_name' => 'Cookie Logout',
            'email' => 'cookie-logout@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $refreshToken = $this->refreshCookieValueFrom($loginResponse);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/auth/logout');

        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertRefreshCookieIsCleared($response);
    }

    public function test_verify_token_accepts_access_tokens_and_rejects_refresh_tokens(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30))->plainTextToken;

        $this->postJson('/api/auth/verify-token', ['token' => $accessToken])
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.refresh_token.plain_token', null);

        $this->postJson('/api/auth/verify-token', ['token' => $refreshToken])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid token type');
    }

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset-link@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-password@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    private function responseHasRefreshCookie($response): bool
    {
        foreach ($response->baseResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                return true;
            }
        }

        return false;
    }

    private function tokenIdFromPlainToken(string $plainToken): int
    {
        return (int) explode('|', $plainToken, 2)[0];
    }
}

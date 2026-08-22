<?php

namespace Tests\Services\Unit\Auth;

use App\Http\Resources\UserResource;
use App\Http\Services\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthService::class);
    }

    public function test_register_user_creates_user_and_issues_access_and_refresh_tokens(): void
    {
        $result = $this->service->registerUser([
            'full_name' => 'Service Register',
            'email' => 'service-register@example.com',
            'password' => 'password123',
        ]);

        $user = User::query()->where('email', 'service-register@example.com')->firstOrFail();

        $this->assertInstanceOf(UserResource::class, $result['user']);
        $this->assertNotEmpty($result['access_token']['plain_token']);
        $this->assertNotEmpty($result['refresh_token']['plain_token']);
        $this->assertTrue($result['access_token']['expires_at']->isFuture());
        $this->assertTrue($result['refresh_token']['expires_at']->greaterThan($result['access_token']['expires_at']));
        $this->assertSame(1, $user->tokens()->where('name', 'User Access Token')->count());
        $this->assertSame(1, $user->tokens()->where('name', 'User Refresh Token')->count());
    }

    public function test_login_user_returns_token_pair_and_updates_last_login(): void
    {
        $user = User::factory()->create([
            'email' => 'service-login@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        $result = $this->service->loginUser([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertInstanceOf(UserResource::class, $result['user']);
        $this->assertNotEmpty($result['access_token']['plain_token']);
        $this->assertNotEmpty($result['refresh_token']['plain_token']);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertSame(2, $user->tokens()->count());
    }
}

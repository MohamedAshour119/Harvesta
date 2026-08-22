<?php

namespace Tests\Services\Unit\Auth;

use App\Http\Services\AuthService;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthServicePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthService::class);
    }

    public function test_send_password_reset_link_sends_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'service-reset-link@example.com']);

        $status = $this->service->sendPasswordResetLink($user->email);

        $this->assertSame(Password::RESET_LINK_SENT, $status);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_updates_user_password(): void
    {
        $user = User::factory()->create([
            'email' => 'service-reset-password@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $status = $this->service->resetPassword([
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertSame(Password::PASSWORD_RESET, $status);
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}

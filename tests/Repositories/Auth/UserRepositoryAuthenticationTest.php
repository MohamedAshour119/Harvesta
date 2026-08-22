<?php

namespace Tests\Repositories\Auth;

use App\Http\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRepositoryAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }

    public function test_create_persists_user_with_hashed_password(): void
    {
        $user = $this->repository->create([
            'full_name' => 'Repository Customer',
            'email' => 'repository@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame('Repository Customer', $user->full_name);
        $this->assertSame('repository@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('users', ['email' => 'repository@example.com']);
    }

    public function test_login_user_returns_user_and_updates_last_login_at(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        $result = $this->repository->loginUser([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertTrue($result->is($user));
        $this->assertNotNull($result->last_login_at);
    }

    public function test_login_user_rejects_unknown_email(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->repository->loginUser([
            'email' => 'missing@example.com',
            'password' => 'password123',
        ]);
    }

    public function test_login_user_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'wrong-password@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid password');

        $this->repository->loginUser([
            'email' => 'wrong-password@example.com',
            'password' => 'bad-password',
        ]);
    }
}

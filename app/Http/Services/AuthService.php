<?php

namespace App\Http\Services;

use App\Http\Repositories\UserRepository;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(protected UserRepository $userRepository) {}

    public function registerUser(array $data)
    {
        $user = $this->userRepository->create($data);

        return $this->issueTokenPair($user);
    }

    public function loginUser(array $data)
    {
        $user = $this->userRepository->loginUser($data);

        return $this->issueTokenPair($user);
    }
    public function logoutUser(User $user)
    {
        $this->userRepository->logoutUser($user);
    }

    public function logoutUsingRefreshToken(string $refreshToken): void
    {
        $this->userRepository->logoutUsingRefreshToken($refreshToken);
    }

    public function refreshToken($refresh_token): array
    {
        // Get the user from the refresh token instead of from auth
        $user = $this->userRepository->refreshToken($refresh_token);
        $user->tokens()->where('name', 'User Access Token')->delete();

        return $this->issueTokenPair($user);
    }

    public function verifyToken($token): array
    {
        return $this->userRepository->verifyToken($token);
    }

    public function sendPasswordResetLink(string $email)
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(__($status));
        }

        return $status;
    }

    public function resetPassword(array $data)
    {
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }

        return $status;
    }

    private function issueTokenPair(User $user): array
    {
        $user->loadMissing('wishlistItems');

        $accessToken = $user->createToken('User Access Token', expiresAt: now()->addMinutes(15));
        $refreshToken = $user->createToken('User Refresh Token', expiresAt: now()->addDays(30));

        return [
            'user' => new UserResource($user),
            'access_token' => [
                'plain_token' => $accessToken->plainTextToken,
                'expires_at' => $accessToken->accessToken->expires_at,
            ],
            'refresh_token' => [
                'plain_token' => $refreshToken->plainTextToken,
                'expires_at' => $refreshToken->accessToken->expires_at,
            ],
        ];
    }
}

<?php

namespace App\Http\Repositories;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function loginUser(array $data): User
    {
        $user = User::where('email', $data['email'])->with('wishlistItems')->first();
        if (!$user) {
            throw new \Exception('User not found');
        }
        if (!Hash::check($data['password'], $user->password)) {
            throw new \Exception('Invalid password');
        }
        $user->update(['last_login_at' => now()]);
        return $user;
    }

    public function logoutUser(User $user): void
    {
        $user->tokens()->delete();
    }

    public function logoutUsingRefreshToken(string $refresh_token): void
    {
        if (strpos($refresh_token, '|') === false) {
            throw new \Exception('Invalid refresh token format');
        }

        [$id, $token] = explode('|', $refresh_token, 2);

        $tokenModel = PersonalAccessToken::find($id);

        if (!$tokenModel) {
            throw new \Exception('Refresh token not found');
        }

        if ($tokenModel->name !== 'User Refresh Token') {
            throw new \Exception('Invalid token type');
        }

        if (!hash_equals($tokenModel->token, hash('sha256', $token))) {
            throw new \Exception('Invalid refresh token');
        }

        $user = $tokenModel->tokenable;

        if (!$user) {
            throw new \Exception('User not found');
        }

        $user->tokens()->delete();
    }

    public function refreshToken($refresh_token): User
    {
        if (strpos($refresh_token, '|') === false) {
            throw new \Exception('Invalid refresh token format');
        }

        [$id, $token] = explode('|', $refresh_token, 2);

        $tokenModel = PersonalAccessToken::find($id);

        if (!$tokenModel) {
            throw new \Exception('Refresh token not found');
        }

        if ($tokenModel->name !== 'User Refresh Token') {
            throw new \Exception('Invalid token type');
        }

        if ($tokenModel->expires_at->isPast()) {
            throw new \Exception('Refresh token has expired');
        }

        // Verify the provided refresh token matches the stored one
        if (!hash_equals($tokenModel->token, hash('sha256', $token))) {
            throw new \Exception('Invalid refresh token');
        }

        // Get the user from the token
        $user = $tokenModel->tokenable;

        if (!$user) {
            throw new \Exception('User not found');
        }

        $tokenModel->delete();
        
        return $user;
    }

    public function verifyToken($token): array
    {
        if (strpos($token, '|') === false) {
            throw new \Exception('Invalid token format');
        }

        [$id, $tokenValue] = explode('|', $token, 2);

        $tokenModel = PersonalAccessToken::find($id);

        if (!$tokenModel) {
            throw new \Exception('Token not found');
        }

        if ($tokenModel->name !== 'User Access Token') {
            throw new \Exception('Invalid token type');
        }

        if ($tokenModel->expires_at->isPast()) {
            throw new \Exception('Token has expired');
        }

        // Verify the provided token matches the stored one
        if (!hash_equals($tokenModel->token, hash('sha256', $tokenValue))) {
            throw new \Exception('Invalid token');
        }

        // Get the user from the token
        $user = $tokenModel->tokenable;

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Get the user's refresh token
        $refreshToken = $user->tokens()->where('name', 'User Refresh Token')->first();
        
        return [
            'user' => $user,
            'access_token' => [
                'plain_token' => $token, // Return the token they provided
                'expires_at' => $tokenModel->expires_at,
            ],
            'refresh_token' => [
                'plain_token' => null, // Can't retrieve hashed refresh token
                'expires_at' => $refreshToken ? $refreshToken->expires_at : null,
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\AuthService; // Don't forget to import this
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use HttpResponses;

    private const REFRESH_TOKEN_COOKIE = 'refresh_token';

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        $result = $this->authService->registerUser($validated);

        return $this->response_success($this->publicAuthPayload($result), 'User registered successfully')
            ->withCookie($this->makeRefreshCookie($request, $result));
    }

    public function login(LoginUserRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->authService->loginUser($validated);
    
            return $this->response_success($this->publicAuthPayload($result), 'User logged in successfully')
                ->withCookie($this->makeRefreshCookie($request, $result));
        } catch (\Exception $e) {
            // return $this->response_error('Invalid email or password', [], 401);
            return $this->response_error($e->getMessage(), [], 401);
        }
    }
    public function logout(Request $request)
    {
        try {
            $user = auth('sanctum')->user();

            if ($user) {
                $this->authService->logoutUser($user);
                return $this->response_success(null, 'User logged out successfully')
                    ->withCookie($this->forgetRefreshCookie($request));
            }

            $refreshToken = $this->refreshTokenFromRequest($request);

            if ($refreshToken) {
                $this->authService->logoutUsingRefreshToken($refreshToken);
                return $this->response_success(null, 'User logged out successfully')
                    ->withCookie($this->forgetRefreshCookie($request));
            }

            return $this->response_error('Not authenticated', [], 401)
                ->withCookie($this->forgetRefreshCookie($request));
        } catch (\Exception $e) {
            return $this->response_error('Logout failed: ' . $e->getMessage(), [], 500)
                ->withCookie($this->forgetRefreshCookie($request));
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $refreshToken = $this->refreshTokenFromRequest($request);

            if (! $refreshToken) {
                return $this->response_error('No refresh token provided', [], 401)
                    ->withCookie($this->forgetRefreshCookie($request));
            }

            $result = $this->authService->refreshToken($refreshToken);
    
            return $this->response_success($this->publicAuthPayload($result), 'Token refreshed successfully')
                ->withCookie($this->makeRefreshCookie($request, $result));
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 401)
                ->withCookie($this->forgetRefreshCookie($request));
        }
    }

    public function verifyToken(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
            ]);
            $result = $this->authService->verifyToken($validated['token']);
    
            return $this->response_success($result, 'Token verified successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 401);
        }
    }
    
    /**
     * Consume encrypted payload and return user + tokens
     */
    public function consumePayload(Request $request)
    {
        $validated = $request->validate([
            'payload' => 'required|string',
        ]);
        try {
            $raw = base64_decode($validated['payload']);
            $privateKey = file_get_contents(storage_path('keys/private.pem'));
            if (!openssl_private_decrypt($raw, $decrypted, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
                throw new \Exception('Invalid or tampered payload');
            }
            $data = json_decode($decrypted, true);
            if (!isset($data['access_token'], $data['refresh_token'])) {
                throw new \Exception('Invalid payload');
            }
            $result = $this->authService->verifyToken($data['access_token']);
            // attach refresh token plaintext
            $result['refresh_token']['plain_token'] = $data['refresh_token'];
            // Track last login
            if (isset($result['user']) && $result['user'] instanceof User) {
                $result['user']->update(['last_login_at' => now()]);
                $result['user'] = new UserResource($result['user']->loadMissing('wishlistItems'));
            }
            return $this->response_success($this->publicAuthPayload($result), 'Payload consumed successfully')
                ->withCookie($this->makeRefreshCookie($request, $result));
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 401);
        }
    }

    public function consumeImpersonationPayload(Request $request)
    {
        $payload = $request->input('payload');
        if (empty($payload)) {
            return $this->response_error('Payload is required.', [], 400);
        }

        try {
            $privateKeyPath = storage_path('keys/private.pem');
            if (!file_exists($privateKeyPath)) {
                return $this->response_error('Private key not found.', [], 500);
            }
            $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));

            // 1. Decode the outer payload
            $payloadData = json_decode(base64_decode($payload), true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($payloadData['key'], $payloadData['iv'], $payloadData['data'])) {
                return $this->response_error('Invalid payload structure.', [], 400);
            }

            // 2. Decrypt the symmetric key
            $encryptedSymmetricKey = base64_decode($payloadData['key']);
            $symmetricKey = '';
            if (!openssl_private_decrypt($encryptedSymmetricKey, $symmetricKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
                return $this->response_error('Failed to decrypt symmetric key.', [], 500);
            }

            // 3. Decrypt the main data
            $iv = base64_decode($payloadData['iv']);
            $encryptedData = $payloadData['data']; // This is already base64 encoded
            $decryptedJson = openssl_decrypt($encryptedData, 'aes-256-cbc', $symmetricKey, 0, $iv);

            if ($decryptedJson === false) {
                return $this->response_error('Failed to decrypt data.', [], 500);
            }

            $decryptedData = json_decode($decryptedJson, true);

            // 4. Validate and process the final data
            if (!isset($decryptedData['exp']) || $decryptedData['exp'] < now()->timestamp) {
                return $this->response_error('Payload has expired.', [], 401);
            }

            $user = User::find($decryptedData['user_id']);
            if (!$user) {
                return $this->response_error('User not found.', [], 404);
            }

            $user->update(['last_login_at' => now()]);

            $accessTokenData = $decryptedData['access_token'];
            $refreshTokenData = $decryptedData['refresh_token'];

            $data = [
                'user' => new UserResource($user->loadMissing('wishlistItems')),
                'access_token' => [
                    'plain_token' => $accessTokenData['plain_token'],
                    'expires_at' => $accessTokenData['expires_at'],
                ],
                'refresh_token' => [
                    'plain_token' => $refreshTokenData['plain_token'],
                    'expires_at' => $refreshTokenData['expires_at'],
                ],
            ];

            return $this->response_success($this->publicAuthPayload($data), 'Payload consumed successfully.')
                ->withCookie($this->makeRefreshCookie($request, $data));

        } catch (\Exception $e) {
            Log::error('Payload consumption failed: ' . $e->getMessage());
            return $this->response_error('Payload consumption failed.', [], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        try {
            $status = $this->authService->sendPasswordResetLink($request->email);
            return $this->response_success(null, __($status));
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 400);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            $status = $this->authService->resetPassword($request->all());
            return $this->response_success(null, __($status));
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 400);
        }
    }

    private function refreshTokenFromRequest(Request $request): ?string
    {
        return $request->cookie(self::REFRESH_TOKEN_COOKIE) ?: $request->input('refresh_token');
    }

    private function publicAuthPayload(array $result): array
    {
        if (isset($result['refresh_token'])) {
            $result['refresh_token']['plain_token'] = null;
        }

        return $result;
    }

    private function makeRefreshCookie(Request $request, array $result)
    {
        $refreshToken = $result['refresh_token']['plain_token'] ?? null;

        if (! $refreshToken) {
            return $this->forgetRefreshCookie($request);
        }

        $expiresAt = isset($result['refresh_token']['expires_at'])
            ? \Carbon\Carbon::parse($result['refresh_token']['expires_at'])
            : now()->addDays(30);
        $minutes = max(1, (int) now()->diffInMinutes($expiresAt, false));

        return cookie(
            self::REFRESH_TOKEN_COOKIE,
            $refreshToken,
            $minutes,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'Strict'
        );
    }

    private function forgetRefreshCookie(Request $request)
    {
        return cookie(
            self::REFRESH_TOKEN_COOKIE,
            '',
            -2628000,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'Strict'
        );
    }
}

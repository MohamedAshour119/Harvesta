<?php

namespace App\Http\Controllers;

use App\Http\Services\AgentDashboardService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    use HttpResponses;

    protected $agentDashboardService;

    public function __construct(AgentDashboardService $agentDashboardService)
    {
        $this->agentDashboardService = $agentDashboardService;
    }

    public function dashboardInfo()
    {
        try {
            $data = $this->agentDashboardService->dashboardInfo();
            return $this->response_success($data, 'Dashboard info');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function salesPerformance($year)
    {
        try {
            $data = $this->agentDashboardService->salesPerformance($year);
            return $this->response_success($data, 'Sales performance');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function recentOrders(Request $request)
    {
        try {
            $data = $this->agentDashboardService->recentOrders($request);
            return $this->response_success($data, 'Recent orders');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function commissionAndBonus()
    {
        try {
            $data = $this->agentDashboardService->commissionAndBonus();
            return $this->response_success($data, 'Commission and bonus');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function commissionsTimeline()
    {
        try {
            $data = $this->agentDashboardService->commissionsTimeline();
            return $this->response_success($data, 'Commissions timeline');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function impersonate(Request $request, User $user)
    {
        // 1. Ensure the authenticated user is a Super Admin
        if (!Gate::allows('superAdminAccess')) {
            abort(403, 'Unauthorized action.');
        }

        // 2. Ensure the target user is an agent
        if (!$user->is_agent) {
            abort(404, 'User is not an agent.');
        }

        // 3. Generate new tokens for the agent
        $accessToken = $user->createToken('User Access Token', [], now()->addMinutes(15));
        $refreshToken = $user->createToken('User Refresh Token', [], now()->addDays(30));

        // 4. Prepare data for encryption
        $dataToEncrypt = [
            'user_id' => $user->id, // Send only the user ID
            'access_token' => [
                'plain_token' => $accessToken->plainTextToken,
                'expires_at' => $accessToken->accessToken->expires_at->toDateTimeString(),
            ],
            'refresh_token' => [
                'plain_token' => $refreshToken->plainTextToken,
                'expires_at' => $refreshToken->accessToken->expires_at->toDateTimeString(),
            ],
            // Add a short expiration to make the payload one-time use
            'exp' => now()->addMinutes(1)->timestamp,
        ];

        // 5. Encrypt the payload using Hybrid Encryption
        $publicKeyPath = storage_path('keys/public.pem');
        if (!file_exists($publicKeyPath)) {
            Log::error('Public key not found.');
            abort(500, 'Public key not found.');
        }
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));

        // Generate a random symmetric key and initialization vector (IV)
        $symmetricKey = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);

        // Encrypt the main data with the symmetric key
        $jsonData = json_encode($dataToEncrypt);
        $encryptedData = openssl_encrypt($jsonData, 'aes-256-cbc', $symmetricKey, 0, $iv);

        // Encrypt the symmetric key with the RSA public key
        $encryptedSymmetricKey = '';
        if (!openssl_public_encrypt($symmetricKey, $encryptedSymmetricKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            while ($msg = openssl_error_string()) {
                Log::error('OpenSSL Key Encryption Error: ' . $msg);
            }
            abort(500, 'Failed to encrypt symmetric key.');
        }

        // Prepare the final payload
        $finalPayload = base64_encode(json_encode([
            'key' => base64_encode($encryptedSymmetricKey),
            'iv' => base64_encode($iv),
            'data' => $encryptedData, // Already base64 encoded by openssl_encrypt
        ]));

        return response()->json(['payload' => $finalPayload]);
    }
}

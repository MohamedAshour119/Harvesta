<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapPaymentService
{
    protected $secretKey;
    protected $baseUrl;
    protected InventoryAllocationService $inventoryAllocationService;

    public function __construct(InventoryAllocationService $inventoryAllocationService)
    {
        $this->secretKey = config('services.tap.secret_key');
        $this->baseUrl = 'https://api.tap.company/v2/';
        $this->inventoryAllocationService = $inventoryAllocationService;
    }

    protected function sendRequest(string $method, string $endpoint, array $data = [])
    {
        try {
            // $response = Http::withToken($this->secretKey)
            $response = Http::withToken("sk_test_XKokBfNWv6FIYuTMg5sLPjhJ")
                ->acceptJson()
                ->asJson()
                ->{$method}($this->baseUrl . $endpoint, $data);

            if ($response->failed()) {
                Log::error("Tap API Error ({$response->status()}): " . "Response Body: " . $response->body());
                throw new \Exception("Tap API error: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Tap Payment Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function createCharge(array $data)
    {
        return $this->sendRequest('post', 'charges', $data);
    }

    public function retrieveCharge($chargeId)
    {
        return $this->sendRequest('get', 'charges/' . $chargeId);
    }

    public function refundCharge($chargeId, $amount = null)
    {
        $data = ['charge' => $chargeId];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        return $this->sendRequest('post', 'refunds', $data);
    }

    public function updateItemsQuantityInInventories($productsVariants)
    {
        $this->inventoryAllocationService->allocate($productsVariants);
    }
    
}

<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Stripe;
use Exception;

class StripePaymentService
{
    protected $stripe;
    protected InventoryAllocationService $inventoryAllocationService;

    public function __construct(InventoryAllocationService $inventoryAllocationService)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->inventoryAllocationService = $inventoryAllocationService;
    }

    public function createCheckoutSession(array $data)
    {
        try {
            $session = $this->stripe->checkout->sessions->create($data);
            return $session;
        } catch (Exception $e) {
            Log::error("Stripe Create Session Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function retrieveSession($sessionId)
    {
        try {
            return $this->stripe->checkout->sessions->retrieve($sessionId);
        } catch (Exception $e) {
            Log::error("Stripe Retrieve Session Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateItemsQuantityInInventories($productsVariants)
    {
        $this->inventoryAllocationService->allocate($productsVariants);
    }
}

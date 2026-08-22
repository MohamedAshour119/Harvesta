<?php

namespace App\Http\Controllers;

use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Coupon;
use App\Models\Order;
use App\Helpers\Helper;
use App\Http\Requests\ShippingCostRequest;
use App\Http\Services\PaymentService;
use App\Http\Services\TapPaymentService;
use App\Http\Services\StripePaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryProduct;
use Exception;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Jobs\UpdateAgentMonthlyAchievedAmount;
use App\Services\Order\SmartBundleService;
use App\Models\ShippingDestination;

class PaymentController extends Controller
{
    use HttpResponses;

    protected $tapPayment;
    protected $stripePayment;
    protected $paymentService;
    protected $smartBundleService;

    public function __construct(TapPaymentService $tapPayment, StripePaymentService $stripePayment, PaymentService $paymentService, SmartBundleService $smartBundleService)
    {
        $this->tapPayment = $tapPayment;
        $this->stripePayment = $stripePayment;
        $this->paymentService = $paymentService;
        $this->smartBundleService = $smartBundleService;
    }

    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon' => ['required', 'string', 'max:60'],
        ]);

        try {
            $coupon = $this->paymentService->applyCoupon($validated['coupon']);

            return $this->response_success(['coupon' => $coupon], 'Coupon applied successfully');
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 400;

            return $this->response_error($e->getMessage(), ['code' => [$e->getMessage()]], $statusCode);
        }
    }

    public function getShippingCost(ShippingCostRequest $request)
    {
        $validated = $request->validated();

        try {
            $shippingCost = $this->paymentService->getShippingCost($validated);

            return $this->response_success(['shippingCost' => $shippingCost], 'Shipping cost retrieved successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), ['code' => [$e->getMessage()]], $e->getCode() ?: 400);
        }
    }

    public function getShippingDestinations(): JsonResponse
    {
        $locale = app()->getLocale();

        $destinations = ShippingDestination::query()
            ->orderBy('city->en')
            ->get()
            ->map(function (ShippingDestination $destination) use ($locale) {
                $englishCity = $destination->getTranslation('city', 'en');

                return [
                    'label' => $destination->getTranslation('city', $locale, false) ?: $englishCity,
                    'value' => $englishCity,
                ];
            })
            ->values();

        return $this->response_success($destinations, 'Shipping destinations retrieved successfully');
    }

    public function tapCheckout(CreateOrderRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        DB::beginTransaction();

        $productsVariants = [];

        try {
            $total_amount = 0;
            $subtotal = 0;
            $products = collect($validated['products']);
            
            // Calculate smart bundle discounts
            $bundleCTAs = collect($this->smartBundleService->calculateCartCTAs($validated['products']))->keyBy('product_id');

            // Calculate total from product prices
            foreach ($products as $product) {
                $productVariantModel = ProductVariant::findOrFail(data_get($product, 'productVariantId'));
                $quantity = data_get($product, 'quantity', 1);
                $productVariantModel['quantity'] = $quantity;
                
                $unitPrice = $productVariantModel->price_after_discount;
                
                // Apply smart bundle discount if eligible
                $productId = data_get($product, 'productId') ?? $productVariantModel->product_id;
                $cta = $bundleCTAs->get($productId);
                if ($cta && data_get($cta, 'is_applied')) {
                    $discountFac = (100 - data_get($cta, 'discount_percentage')) / 100;
                    $unitPrice = $unitPrice * $discountFac;
                }

                $itemTotal = $unitPrice * $quantity;
                $total_amount += $itemTotal;
                $subtotal += $itemTotal;

                $productVariantModel->unit_price_for_order = $unitPrice; // Store for order creation
                $productsVariants[] = $productVariantModel;
            }

            $ensureOrderMinimumValue = $this->paymentService->ensureOrderMinimumValue($productsVariants);
            if (!$ensureOrderMinimumValue) {
                return $this->response_error('Minimum order value not met', ['minimum_order_value' => __('translations.minimum-order-value')], 422);
            }

            $coupon = null;
            $discountAmount = 0;

            if (!empty($validated['coupon'])) {
                $couponResult = $this->paymentService->applyCoupon($validated['coupon'], true);
                $coupon = $couponResult;
                $discountAmount = ($total_amount * (float) $couponResult['value']) / 100;

                $total_amount -= $discountAmount;
                $total_amount = max($total_amount, 0);
            }

            // Calculate shipping cost
            $shippingCost = 0;
            if (isset($validated['address']['city'])) {
                try {
                    $shippingData = [
                        'city' => $validated['address']['city'],
                        'items' => collect($products)->map(function($p) {
                             return [
                                 'productVariantId' => $p['productVariantId'],
                                 'quantity' => $p['quantity']
                             ];
                        })->toArray()
                    ];
                    $shippingCost = $this->paymentService->getShippingCost($shippingData);

                    if ($shippingCost > 0) {
                        $total_amount += $shippingCost;
                    }
                } catch (\Exception $e) {
                    Log::error('Tap shipping cost calculation failed: ' . $e->getMessage());
                }
            }

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'first_name' => $validated['address']['firstName'],
                'last_name' => $validated['address']['lastName'],
                'email' => $validated['address']['email'],
                'address' => $validated['address']['streetAddress'],
                'phone_number' => $validated['address']['phone'],
                'company_name' => $validated['address']['companyName'],
                'status' => 'pending',
                'city' => $validated['address']['city'],
                'province' => $validated['address']['province'],
                'postal_code' => $validated['address']['postalCode'],
                // 'country' => $validated['address']['country']['value'],
                'subtotal' => $subtotal,
                'total' => (double)($total_amount),
                'discount_amount' => $discountAmount,
                'coupon_id' => $coupon?->id,
                'payment_status' => 'pending',
                'agent_code' => ($validated['agent_code'] ?? null) === $user->code ? null : ($validated['agent_code'] ?? null),
            ]);


            // Attach coupon if exists
            if ($coupon) {
                $already_attached = $user->coupons()
                    ->where('coupon_id', $coupon->id)
                    ->where('used_status', 'pending')
                    ->exists();

                if (!$already_attached) {
                    $user->coupons()->attach($coupon->id, ['used_status' => 'pending']);
                }
            }

            // Attach products and variants
            collect($products)->each(function ($product) use ($order) {
                $variantId = $product['productVariantId'] ?? null;

                if (!$variantId) {
                    throw new \Exception('Product variant ID missing!');
                }

                $productVariant = ProductVariant::find($variantId);

                if (!$productVariant) {
                    throw new \Exception("Product variant with ID {$variantId} not found!");
                }

                $unitPrice = $productVariant->unit_price_for_order ?? $productVariant->price_after_discount;

                $order->items()->create([
                    'order_id' => $order->id,
                    'product_id' => $product['productId'] ?? null,
                    'product_variant_id' => $variantId,
                    'supplier_id' => $productVariant->supplier_id,
                    'quantity' => $product['quantity'] ?? 1,
                    'unit_price' => $unitPrice,
                    'cost_plus_logistics' => $productVariant->cost_plus_logistics,
                    'total' => $unitPrice * ($product['quantity'] ?? 1),
                ]);
            });

            // Prepare Tap payment data
            $chargeData = [
                'amount' => $total_amount,
                "description" => "Test Description",
                'currency' => 'KWD',
                "customer_initiated" => true,
                "threeDSecure" => true,
                "save_card" => false,
                "reference" => [
                    "transaction" => "txn_0001",
                    // "order" => $order->id,
                    "order" => "ord_0001",
                ],
                "receipt" => [
                    "email" => true,
                    "sms" => false
                ],
                'customer' => [
                    'email' => $user->email,
                    'first_name' => $validated['address']['firstName'],
                    'last_name' => $validated['address']['lastName'],
                    'phone' => [
                        'country_code' => $validated['address']['phoneCountryCode'],
                        'number' => $validated['address']['phone']
                    ]
                ],
                'source' => ['id' => 'src_all'],
                'redirect' => [
                    'url' => route('payment.callback', ['order_id' => $order->id])
                ],
                // 'post' => [
                //     'url' => route('payment.webhook')
                // ],
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    // Removed productsVariants to avoid Tap metadata 1000-char limit
                ]
            ];


            info('chargeData: ', [$chargeData]);

            // Create Tap charge
            $charge = $this->tapPayment->createCharge($chargeData);
            
            // Update order with Tap charge ID
            $order->update([
                'tap_charge_id' => $charge['id'],
            ]);

            DB::commit();

            return $this->response_success(['session_url' => $charge['transaction']['url']], 'Payment session created');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Tap checkout failed: ' . $e->getMessage());
            return $this->response_error('Checkout failed.', ['error' => $e->getMessage()], 500);
        }
    }

    public function stripeCheckout(CreateOrderRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        DB::beginTransaction();

        $productsVariants = [];

        try {
            $total_amount = 0;
            $subtotal = 0;
            $products = collect($validated['products']);
            
            // Calculate smart bundle discounts
            $bundleCTAs = collect($this->smartBundleService->calculateCartCTAs($validated['products']))->keyBy('product_id');

            // Calculate total from product prices
            $lineItems = [];

            foreach ($products as $product) {
                $productVariantModel = ProductVariant::findOrFail(data_get($product, 'productVariantId'));
                $quantity = data_get($product, 'quantity', 1);
                $productVariantModel['quantity'] = $quantity;
                
                $unitPrice = $productVariantModel->price_after_discount;
                
                // Apply smart bundle discount if eligible
                $productId = data_get($product, 'productId') ?? $productVariantModel->product_id;
                $cta = $bundleCTAs->get($productId);
                if ($cta && data_get($cta, 'is_applied')) {
                    $discountFac = (100 - data_get($cta, 'discount_percentage')) / 100;
                    $unitPrice = $unitPrice * $discountFac;
                }

                $itemTotal = $unitPrice * $quantity;
                $total_amount += $itemTotal;
                $subtotal += $itemTotal;

                $productVariantModel->unit_price_for_order = $unitPrice;
                $productsVariants[] = $productVariantModel;

                // Add to Stripe line items
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'EGP',
                        'product_data' => [
                            'name' => $productVariantModel->product->name ?? 'Product',
                        ],
                        'unit_amount' => (int) round($unitPrice * 100), // Updated to use unitPrice
                    ],
                    'quantity' => $quantity,
                ];
            }

            $ensureOrderMinimumValue = $this->paymentService->ensureOrderMinimumValue($productsVariants);
            if (!$ensureOrderMinimumValue) {
                return $this->response_error('Minimum order value not met', ['minimum_order_value' => __('translations.minimum-order-value')], 422);
            }

            $coupon = null;
            $discountAmount = 0;

            if (!empty($validated['coupon'])) {
                $couponResult = $this->paymentService->applyCoupon($validated['coupon'], true);
                $coupon = $couponResult;
                $discountAmount = ($total_amount * (float) $couponResult['value']) / 100;

                $total_amount -= $discountAmount;
                $total_amount = max($total_amount, 0);

                // For Stripe, apply the discount to the session using a Stripe coupon/discount or a negative line item
                // For simplicity, we create a discount line item if possible or just use proportioned prices, 
                // but since Stripe supports discounts natively we would normally create a Stripe coupon. 
                // Alternatively, we can adjust unit prices proportionally.
                // Or simply apply a generic discount coupon if you sync it with Stripe.
            }

            // Calculate shipping cost
            $shippingCost = 0;
            if (isset($validated['address']['city'])) {
                try {
                    $shippingData = [
                        'city' => $validated['address']['city'],
                        'items' => collect($products)->map(function($p) {
                             return [
                                 'productVariantId' => $p['productVariantId'],
                                 'quantity' => $p['quantity']
                             ];
                        })->toArray()
                    ];
                    $shippingCost = $this->paymentService->getShippingCost($shippingData);

                    if ($shippingCost > 0) {
                        $total_amount += $shippingCost;

                        // Add Stripe line item for shipping
                        $lineItems[] = [
                            'price_data' => [
                                'currency' => 'EGP',
                                'product_data' => [
                                    'name' => 'Shipping Cost',
                                ],
                                'unit_amount' => (int) round($shippingCost * 100),
                            ],
                            'quantity' => 1,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Shipping cost calculation failed: ' . $e->getMessage());
                }
            }

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'first_name' => $validated['address']['firstName'],
                'last_name' => $validated['address']['lastName'],
                'email' => $validated['address']['email'],
                'address' => $validated['address']['streetAddress'],
                'phone_number' => $validated['address']['phone'],
                'company_name' => $validated['address']['companyName'],
                'status' => 'pending',
                'city' => $validated['address']['city'],
                'province' => $validated['address']['province'],
                'postal_code' => $validated['address']['postalCode'],
                'subtotal' => $subtotal,
                'total' => (double)($total_amount),
                'discount_amount' => $discountAmount,
                'coupon_id' => $coupon?->id,
                'payment_status' => 'pending',
                'agent_code' => ($validated['agent_code'] ?? null) === $user->code ? null : ($validated['agent_code'] ?? null),
            ]);

            // Attach coupon if exists
            if ($coupon) {
                $already_attached = $user->coupons()
                    ->where('coupon_id', $coupon->id)
                    ->where('used_status', 'pending')
                    ->exists();

                if (!$already_attached) {
                    $user->coupons()->attach($coupon->id, ['used_status' => 'pending']);
                }
            }

            // Attach products and variants
            collect($products)->each(function ($product) use ($order) {
                $variantId = $product['productVariantId'] ?? null;
                if (!$variantId) throw new \Exception('Product variant ID missing!');
                $productVariant = ProductVariant::find($variantId);
                if (!$productVariant) throw new \Exception("Product variant with ID {$variantId} not found!");

                $unitPrice = $productVariant->unit_price_for_order ?? $productVariant->price_after_discount;

                $order->items()->create([
                    'order_id' => $order->id,
                    'product_id' => $product['productId'] ?? null,
                    'product_variant_id' => $variantId,
                    'supplier_id' => $productVariant->supplier_id,
                    'quantity' => $product['quantity'] ?? 1,
                    'unit_price' => $unitPrice,
                    'cost_plus_logistics' => $productVariant->cost_plus_logistics,
                    'total' => $unitPrice * ($product['quantity'] ?? 1),
                ]);
            });

            // Prepare Stripe Checkout Session data
            $checkoutData = [
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => url('/?status=cancel'),
                'customer_email' => $user->email,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]
            ];

            $session = $this->stripePayment->createCheckoutSession($checkoutData);

            // Update order with Stripe Session ID
            $order->update([
                'tap_charge_id' => $session->id, // Reusing this column or use a new one for Stripe
            ]);

            DB::commit();

            return $this->response_success(['session_url' => $session->url], 'Payment session created');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Stripe checkout failed: ' . $e->getMessage());
            return $this->response_error('Checkout failed.', ['error' => $e->getMessage()], 500);
        }
    }

    public function paymentCallback(Request $request)
    {

        $orderId = $request->query('order_id');
        $tapId = $request->query('tap_id');
        // $coupon = 
        info('orderId: ' . $orderId);
        info('tapId: ' . $tapId);
        
        if (!$orderId || !$tapId) {
            return redirect('/?status=cancel');
        }

        try {
            $charge = $this->tapPayment->retrieveCharge($tapId);
            $order = Order::find($orderId);

            // Security: Validate that the Tap charge actually belongs to this order
            if (!$order || $order->tap_charge_id !== $tapId) {
                Log::warning('Payment callback: Order/Charge mismatch', [
                    'order_id' => $orderId,
                    'tap_id' => $tapId,
                    'order_tap_id' => $order->tap_charge_id ?? 'null'
                ]);
                return redirect('/?status=cancel');
            }

            if ($charge['status'] === 'CAPTURED') {
                // Prevent double-processing
                if ($order->payment_status === 'completed') {
                    return redirect('/?status=success');
                }

                // Build productsVariants from order items and update inventories
                $productVariants = $order->items()
                    ->get(['product_variant_id', 'quantity'])
                    ->map(function ($item) {
                        return [
                            'productVariantId' => data_get($item, 'product_variant_id'),
                            'quantity' => data_get($item, 'quantity'),
                        ];
                    })
                    ->toArray();

                try {
                    $this->tapPayment->updateItemsQuantityInInventories($productVariants);
                } catch (\Exception $e) {
                    Log::error('Payment callback inventory update failed: ' . $e->getMessage());
                    return redirect('/?status=cancel');
                }
                $order->update([
                    'payment_status' => 'completed',
                    'status' => 'approved'
                ]);

                // Update coupon status to 'used' if order has a coupon
                if ($order->coupon_id) {
                    $order->user->coupons()
                        ->where('coupon_id', '=', $order->coupon_id)
                        ->where('used_status', '=', 'pending')
                        ->updateExistingPivot($order->coupon_id, ['used_status' => 'used']);
                }

                foreach ($order->items as $item) {
                    \App\Jobs\SendRecombeeInteractionJob::dispatch('purchase', [
                        'user_id' => $order->user_id,
                        'product_id' => $item->product_id,
                        'price' => (float)$item->unit_price,
                        'quantity' => (int)$item->quantity
                    ]);
                }

                return redirect('/?status=success');
            } else {
                $order->update(['payment_status' => 'failed']);
                return redirect('/?status=cancel');
            }
        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            return redirect('/?status=cancel');
        }
    }

    public function checkIfCouponApplied()
    {
        try {
            $coupon = $this->paymentService->checkIfCouponApplied();

            return $this->response_success(['coupon' => $coupon], 'Coupon retrieved successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), ['code' => [$e->getMessage()]], $e->getCode() ?: 400);
        }
    }

    public function stripeCallback(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect('/?status=cancel');
        }

        try {
            $session = $this->stripePayment->retrieveSession($sessionId);
            $orderId = $session->metadata->order_id ?? null;
            
            if (!$orderId) {
                return redirect('/?status=cancel');
            }

            $order = Order::find($orderId);

            if (!$order || $order->tap_charge_id !== $sessionId) {
                Log::warning('Payment callback: Order/Session mismatch', [
                    'order_id' => $orderId,
                    'session_id' => $sessionId,
                ]);
                return redirect('/?status=cancel');
            }

            if ($session->payment_status === 'paid') {
                if ($order->payment_status === 'completed') {
                    return redirect('/?status=success');
                }

                $productVariants = $order->items()
                    ->get(['product_variant_id', 'quantity'])
                    ->map(function ($item) {
                        return [
                            'productVariantId' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                        ];
                    })
                    ->toArray();

                try {
                    $this->stripePayment->updateItemsQuantityInInventories($productVariants);
                } catch (\Exception $e) {
                    Log::error('Payment callback inventory update failed: ' . $e->getMessage());
                    return redirect('/?status=cancel');
                }

                $order->update([
                    'payment_status' => 'completed',
                    'status' => 'approved'
                ]);

                if ($order->coupon_id) {
                    $order->user->coupons()
                        ->where('coupon_id', $order->coupon_id)
                        ->where('used_status', 'pending')
                        ->updateExistingPivot($order->coupon_id, ['used_status' => 'used']);
                }

                foreach ($order->items as $item) {
                    \App\Jobs\SendRecombeeInteractionJob::dispatch('purchase', [
                        'user_id' => $order->user_id,
                        'product_id' => $item->product_id,
                        'price' => (float)$item->unit_price,
                        'quantity' => (int)$item->quantity
                    ]);
                }

                return redirect('/?status=success');
            } else {
                $order->update(['payment_status' => 'failed']);
                return redirect('/?status=cancel');
            }
        } catch (\Exception $e) {
            Log::error('Stripe Payment callback error: ' . $e->getMessage());
            return redirect('/?status=cancel');
        }
    }

    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');
        $tolerance = config('services.stripe.webhook_tolerance', 300);

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret, $tolerance
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook: Invalid payload.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe Webhook: Invalid signature.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                
                $orderId = $session->metadata->order_id ?? null;
                if ($orderId) {
                    $order = Order::find($orderId);
                    if ($order && $order->payment_status !== 'completed' && $order->tap_charge_id === $session->id) {
                        
                        // Update inventory
                        $productVariants = $order->items()
                            ->get(['product_variant_id', 'quantity'])
                            ->map(function ($item) {
                                return [
                                    'productVariantId' => $item->product_variant_id,
                                    'quantity' => $item->quantity,
                                ];
                            })
                            ->toArray();

                        try {
                            $this->stripePayment->updateItemsQuantityInInventories($productVariants);
                        } catch (\Exception $e) {
                            Log::error('Stripe Webhook: Inventory update failed: ' . $e->getMessage());
                        }

                        // Update order status
                        $order->update([
                            'payment_status' => 'completed',
                            'status' => 'approved'
                        ]);

                        if ($order->coupon_id) {
                            $order->user->coupons()
                                ->where('coupon_id', $order->coupon_id)
                                ->where('used_status', 'pending')
                                ->updateExistingPivot($order->coupon_id, ['used_status' => 'used']);
                        }

                        foreach ($order->items as $item) {
                            \App\Jobs\SendRecombeeInteractionJob::dispatch('purchase', [
                                'user_id' => $order->user_id,
                                'product_id' => $item->product_id,
                                'price' => (float)$item->unit_price,
                                'quantity' => (int)$item->quantity
                            ]);
                        }
                    }
                }
                break;
            
            // ... handle other event types if needed

            default:
                Log::info('Stripe Webhook: Received unhandled event type.', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

}

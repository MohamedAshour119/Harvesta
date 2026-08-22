<?php

namespace App\Http\Controllers;

use App\Http\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use App\Traits\HttpResponses;
use App\Services\Order\SmartBundleService;

class CartController extends Controller
{
    use HttpResponses;
    protected $cartService;
    protected $smartBundleService;

    public function __construct(CartService $cartService, SmartBundleService $smartBundleService)
    {
        $this->cartService = $cartService;
        $this->smartBundleService = $smartBundleService;
    }


    public function getProductsByIds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => ['required', 'array'],
            'ids.*.productId' => ['required', 'integer', 'exists:products,id'],
            'ids.*.productVariantId' => ['required', 'integer', 'exists:product_variants,id'],
            'ids.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->response_error('Validation failed', $validator->errors(), 422);
        }

        $productIds = $request->input('ids');

        $serviceResponse = $this->cartService->getProductsByIds($productIds);
        
        if (!$serviceResponse['success']) {
            return $this->response_error(
                $serviceResponse['message'],
                $serviceResponse['data'],
                $serviceResponse['status_code']
            );
        }

        $ctas = $this->smartBundleService->calculateCartCTAs($productIds);

        return $this->response_success(
            [
                'products' => $serviceResponse['data'],
                'smart_bundle_ctas' => $ctas
            ],
            $serviceResponse['message']
        );
    }
}

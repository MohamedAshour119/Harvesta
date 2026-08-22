<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCustomProduct;
use App\Http\Services\HomePageService;
use App\Http\Services\ProductService;
use App\Traits\HttpResponses;
use App\Jobs\SendRecombeeInteractionJob;
use Illuminate\Support\Facades\Auth;


class ProductController extends Controller
{
    use HttpResponses;
    protected $homePageService;
    protected $productService;
    public function __construct(HomePageService $homePageService, ProductService $productService)
    {
        $this->homePageService = $homePageService;
        $this->productService = $productService;
    }
    public function index()
    {
        $results = $this->homePageService->index();
        return $this->response_success($results, "Home page data retreived successfully.");
    }
    public function getAllProducts()
    {
        $results = $this->productService->getAllProducts();
        return $this->response_success($results, "All products retreived successfully.");
    }
    public function getTopProducts()
    {
        $results = $this->productService->getTopProducts();
        return $this->response_success($results, "Top products retreived successfully.");
    }

    public function getProductBySlug($slug)
    {
        $result = $this->productService->getProductBySlug($slug);
        
        if (!$result) {
            return $this->response_error("Product not found", [], 404);
        }
        
        // Track detail view if user is logged in
        if (Auth::guard('sanctum')->check() && isset($result['product']->id)) {
            SendRecombeeInteractionJob::dispatch('detail_view', [
                'user_id' => Auth::guard('sanctum')->id(),
                'product_id' => $result['product']->id
            ]);
        }
        
        return $this->response_success($result, "Product retrieved successfully.");
    }

    public function requestCustomProduct(RequestCustomProduct $request)
    {
        $validated = $request->validated();
        $result = $this->productService->requestCustomProduct($validated);
        return $this->response_success($result, "Custom product requested successfully.");
    }
}

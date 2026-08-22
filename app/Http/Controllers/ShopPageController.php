<?php

namespace App\Http\Controllers;

use App\Http\Services\ShopService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class ShopPageController extends Controller
{
    use HttpResponses;
    protected ShopService $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    // public function shop ()
    // {
    //     $results = $this->shopService->index();
    //     return $this->response_success($results, "Shop page data retreived successfully.");
    // }

    public function filterProducts (Request $request)
    {
        $filter = $request->query('categories'); 
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $searchQuery = $request->query('search_query');

        $results = $this->shopService->filterProducts($filter, $minPrice, $maxPrice, $searchQuery);
        return $this->response_success($results, "Filtered products retreived successfully.");
    }

    public function getProductsByCategoryName ($categoryName)
    {
        $results = $this->shopService->getProductsByCategoryName($categoryName);
        return $this->response_success($results, "Products by category name retreived successfully.");
    }
}

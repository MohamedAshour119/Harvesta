<?php

namespace App\Http\Controllers;

use App\Http\Services\IndustrialCategoryService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class IndustrialCategories extends Controller
{
    use HttpResponses;
    protected $industrialCategoryService;
    public function __construct(IndustrialCategoryService $industrialCategoryService)
    {
        $this->industrialCategoryService = $industrialCategoryService;
    }
    public function index()
    {
        try {
            $results = $this->industrialCategoryService->index();
            return $this->response_success($results, "Industrial categories");
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 404);
        }
    }
}

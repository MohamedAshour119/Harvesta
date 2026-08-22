<?php

namespace App\Http\Services;

use App\Http\Repositories\IndustrialCategoryRepository;
use App\Http\Resources\IndustrialCategoryResource;

class IndustrialCategoryService
{
    public function __construct(protected IndustrialCategoryRepository $industrialCategoryRepository) {}

    public function index()
    {
        $results = $this->industrialCategoryRepository->index();
        return IndustrialCategoryResource::collection($results);
    }
}

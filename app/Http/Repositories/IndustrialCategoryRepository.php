<?php

namespace App\Http\Repositories;

use App\Http\Resources\UserResource;
use App\Models\IndustryCategory;
use App\Models\User;

class IndustrialCategoryRepository
{
    public function index()
    {
        return IndustryCategory::withCount(['products' => function ($q) {
            $q->has('variants');
        }])->get();
    }
}
<?php

namespace App\Http\Services;

use App\Helper\Helper;
use App\Http\Repositories\SearchRepository;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SearchResultResource;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(protected SearchRepository $searchRepository) {}

    public function search(string $keyword)
    {
        $results = $this->searchRepository->search($keyword);
        $results = SearchResultResource::collection($results);
        return $results;
    }

    public function searchKeyword(string $keyword)
    {
        // Fetch paginated product results
        $paginated = $this->searchRepository->searchKeyword($keyword);
        $pagination = Helper::preparePagination($paginated);
        $data = ProductResource::collection($paginated);
        // Compute categories counts based on result product IDs
        $productIds = $paginated->pluck('id')->toArray();
        $categories = DB::table('industry_categories')
            ->join('products', 'industry_categories.id', '=', 'products.industry_category_id')
            ->whereIn('products.id', $productIds)
            ->select(
                'industry_categories.id',
                'industry_categories.name',
                'industry_categories.slug',
                DB::raw('count(products.id) as products_count')
            )
            ->groupBy(
                'industry_categories.id',
                'industry_categories.name',
                'industry_categories.slug'
            )
            ->get();
        return [
            'data' => $data,
            'pagination' => $pagination,
            'categories' => $categories
        ];
    }
}

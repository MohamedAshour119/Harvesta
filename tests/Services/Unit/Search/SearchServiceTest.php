<?php

namespace Tests\Services\Unit\Search;

use App\Http\Repositories\SearchRepository;
use App\Http\Services\SearchService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\Support\ServiceSmokeTestCase;

class SearchServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return SearchService::class;
    }

    public function test_search_wraps_repository_results_in_search_resources(): void
    {
        $repository = $this->mockDependency(SearchRepository::class);
        $repository->shouldReceive('search')
            ->once()
            ->with('tea')
            ->andReturn(collect([
                new Product(['id' => 1, 'slug' => 'premium-tea', 'name' => ['en' => 'Premium Tea']]),
            ]));

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->search('tea');

        $this->assertSame('premium-tea', $result->resolve()[0]['slug']);
    }

    public function test_search_keyword_returns_category_counts_for_paginated_results(): void
    {
        $categoryId = DB::table('industry_categories')->insertGetId([
            'name' => json_encode(['en' => 'Beverages']),
            'slug' => 'beverages',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productA = Product::query()->create([
            'industry_category_id' => $categoryId,
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Tea', 'ar' => 'Tea'],
            'status' => 'approved',
        ]);

        $productB = Product::query()->create([
            'industry_category_id' => $categoryId,
            'name' => ['en' => 'Green Tea', 'ar' => 'Green Tea'],
            'description' => ['en' => 'Tea', 'ar' => 'Tea'],
            'status' => 'approved',
        ]);

        $paginator = new LengthAwarePaginator(
            collect([$productA, $productB]),
            2,
            10,
            1,
            ['path' => 'http://localhost/search']
        );

        $repository = $this->mockDependency(SearchRepository::class);
        $repository->shouldReceive('searchKeyword')
            ->once()
            ->with('tea')
            ->andReturn($paginator);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->searchKeyword('tea');

        $this->assertSame(1, $result['pagination']['current_page']);
        $this->assertCount(1, $result['categories']);
        $this->assertSame(2, $result['categories'][0]->products_count);
    }
}

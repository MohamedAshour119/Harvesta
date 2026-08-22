<?php

namespace Tests\Services\Unit\Products;

use App\Http\Repositories\IndustrialCategoryRepository;
use App\Http\Services\IndustrialCategoryService;
use App\Models\IndustryCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\Support\ServiceSmokeTestCase;

class IndustrialCategoryServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return IndustrialCategoryService::class;
    }

    public function test_index_returns_a_resource_collection_from_repository_results(): void
    {
        $categories = collect([
            new IndustryCategory(['id' => 1, 'name' => ['en' => 'Beverages'], 'slug' => 'beverages']),
        ]);

        $repository = $this->mockDependency(IndustrialCategoryRepository::class);
        $repository->shouldReceive('index')
            ->once()
            ->andReturn($categories);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->index();

        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertCount(1, $result->collection);
    }
}

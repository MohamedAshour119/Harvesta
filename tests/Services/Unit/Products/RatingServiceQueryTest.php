<?php

namespace Tests\Services\Unit\Products;

use App\Http\Repositories\RatingRepository;
use App\Http\Services\RatingService;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Support\ServiceSmokeTestCase;

class RatingServiceQueryTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return RatingService::class;
    }

    public function test_get_product_ratings_formats_entries_and_includes_stats(): void
    {
        $user = User::factory()->create(['full_name' => 'Lina Ahmed']);
        $product = Product::factory()->create(['status' => 'approved']);
        $rating = ProductRating::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4.0,
            'comment' => 'Great quality',
        ]);

        $repository = $this->mockDependency(RatingRepository::class);
        $repository->shouldReceive('findProduct')
            ->once()
            ->with($product->id)
            ->andReturn($rating);
        $repository->shouldReceive('getProductRatings')
            ->once()
            ->with($product->id)
            ->andReturn(new EloquentCollection([$rating->fresh('user')]));
        $repository->shouldReceive('getAverageRating')
            ->once()
            ->with($product->id)
            ->andReturn(4.0);
        $repository->shouldReceive('getTotalRatings')
            ->once()
            ->with($product->id)
            ->andReturn(1);
        $repository->shouldReceive('getRatingBreakdown')
            ->once()
            ->with($product->id)
            ->andReturn([
                ['stars' => 4, 'count' => 1, 'percentage' => 100],
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getProductRatings($product->id);

        $this->assertSame('Lina Ahmed', $result['ratings'][0]['userName']);
        $this->assertSame('L', $result['ratings'][0]['userInitial']);
        $this->assertSame('Great quality', $result['ratings'][0]['comment']);
        $this->assertSame(4.0, $result['stats']['average_rating']);
    }

    public function test_get_product_stats_returns_default_values_when_repository_fails(): void
    {
        $repository = $this->mockDependency(RatingRepository::class);
        $repository->shouldReceive('getAverageRating')
            ->once()
            ->with(99)
            ->andThrow(new \Exception('Database error'));

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame([
            'average_rating' => 0,
            'total_ratings' => 0,
            'rating_breakdown' => [],
        ], $service->getProductStats(99));
    }

    public function test_get_user_rating_requires_an_authenticated_user(): void
    {
        $service = $this->instantiateService($this->serviceClass());

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(401);

        $service->getUserRating(15);
    }

    public function test_get_paginated_ratings_formats_items_and_pagination_metadata(): void
    {
        $user = User::factory()->create(['full_name' => 'Omar Adel']);
        $product = Product::factory()->create(['status' => 'approved']);
        $this->actingAs($user);

        $rating = ProductRating::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4.2,
            'comment' => 'Works well',
        ])->fresh('user');

        $paginator = new LengthAwarePaginator(
            collect([$rating]),
            1,
            5,
            1,
            ['path' => '/ratings']
        );

        $repository = $this->mockDependency(RatingRepository::class);
        $repository->shouldReceive('getPaginatedRatings')
            ->once()
            ->with($product->id, 5, 1)
            ->andReturn($paginator);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getPaginatedRatings($product->id, 5, 1);

        $this->assertSame('Omar Adel', $result['ratings'][0]['user']['full_name']);
        $this->assertSame(1, $result['pagination']['current_page']);
        $this->assertSame(1, $result['pagination']['total']);
    }
}

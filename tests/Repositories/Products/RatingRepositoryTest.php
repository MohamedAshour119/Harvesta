<?php

namespace Tests\Repositories\Products;

use App\Http\Repositories\RatingRepository;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Carbon\Carbon;
use Tests\Support\RepositoryTestCase;

class RatingRepositoryTest extends RepositoryTestCase
{
    private RatingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(RatingRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_find_existing_create_update_and_get_user_rating_work_together(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);

        $created = $this->repository->createRating([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4.0,
            'comment' => 'Strong first draft',
        ]);

        $this->assertTrue($created->exists);
        $this->assertSame($created->id, $this->repository->findExistingRating($user->id, $product->id)?->id);

        $updated = $this->repository->updateRating($created, [
            'rating' => 5.0,
            'comment' => 'Updated comment',
        ]);

        $this->assertSame('Updated comment', $updated->comment);
        $this->assertSame($updated->id, $this->repository->getUserRating($user->id, $product->id)?->id);
    }

    public function test_find_product_and_get_product_ratings_return_expected_records_in_descending_order(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $olderUser = User::factory()->create(['full_name' => 'Rania Ali']);
        $newerUser = User::factory()->create(['full_name' => 'Nour Hassan']);
        $product = Product::factory()->create(['status' => 'approved']);
        $older = ProductRating::query()->create([
            'user_id' => $olderUser->id,
            'product_id' => $product->id,
            'rating' => 3.5,
            'comment' => 'Older',
        ]);
        $older->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();
        $newer = ProductRating::query()->create([
            'user_id' => $newerUser->id,
            'product_id' => $product->id,
            'rating' => 4.5,
            'comment' => 'Newer',
        ]);
        $newer->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->saveQuietly();

        $ratings = $this->repository->getProductRatings($product->id);

        $this->assertSame($older->id, $this->repository->findProduct($older->id)?->id);
        $this->assertSame([$newer->id, $older->id], $ratings->pluck('id')->all());
        $this->assertTrue($ratings->every(fn (ProductRating $rating): bool => $rating->relationLoaded('user')));
    }

    public function test_average_total_breakdown_and_paginated_ratings_are_computed_from_stored_records(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);

        ProductRating::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5.0,
            'comment' => 'Excellent',
        ]);
        ProductRating::query()->create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
            'rating' => 4.0,
            'comment' => 'Good',
        ]);
        ProductRating::query()->create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
            'rating' => 3.0,
            'comment' => null,
        ]);

        $breakdown = $this->repository->getRatingBreakdown($product->id);
        $paginator = $this->repository->getPaginatedRatings($product->id, 2, 1);

        $this->assertSame(4.0, $this->repository->getAverageRating($product->id));
        $this->assertSame(3, $this->repository->getTotalRatings($product->id));
        $this->assertSame(1, collect($breakdown)->firstWhere('stars', 5)['count']);
        $this->assertSame(2, $paginator->total());
        $this->assertTrue($paginator->getCollection()->every(fn (ProductRating $rating): bool => $rating->comment !== null));
    }
}

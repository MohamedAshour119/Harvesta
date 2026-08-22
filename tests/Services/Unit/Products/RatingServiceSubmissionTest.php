<?php

namespace Tests\Services\Unit\Products;

use App\Http\Repositories\RatingRepository;
use App\Http\Services\RatingService;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class RatingServiceSubmissionTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return RatingService::class;
    }

    public function test_submit_rating_creates_a_new_rating_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);
        $this->actingAs($user);

        $repository = $this->mockDependency(RatingRepository::class);
        $repository->shouldReceive('findExistingRating')
            ->once()
            ->with($user->id, $product->id)
            ->andReturnNull();
        $repository->shouldReceive('createRating')
            ->once()
            ->with([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'rating' => 4.5,
                'comment' => 'Very helpful',
            ])
            ->andReturnUsing(fn (array $data) => ProductRating::create($data));
        $repository->shouldReceive('getAverageRating')
            ->once()
            ->with($product->id)
            ->andReturn(4.5);
        $repository->shouldReceive('getTotalRatings')
            ->once()
            ->with($product->id)
            ->andReturn(1);
        $repository->shouldReceive('getRatingBreakdown')
            ->once()
            ->with($product->id)
            ->andReturn([
                ['stars' => 5, 'count' => 1, 'percentage' => 100],
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->submitRating($product->id, 4.5, 'Very helpful');

        $this->assertSame('Rating submitted successfully', $result['message']);
        $this->assertSame('Very helpful', $result['rating']->comment);
        $this->assertSame(4.5, $result['stats']['average_rating']);
        $this->assertSame(1, $result['stats']['total_ratings']);
    }

    public function test_submit_rating_updates_existing_rating_when_one_already_exists(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);
        $existingRating = ProductRating::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 3.5,
            'comment' => 'Old feedback',
        ]);
        $this->actingAs($user);

        $repository = $this->mockDependency(RatingRepository::class);
        $repository->shouldReceive('findExistingRating')
            ->once()
            ->with($user->id, $product->id)
            ->andReturn($existingRating);
        $repository->shouldReceive('updateRating')
            ->once()
            ->with($existingRating, [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'rating' => 5.0,
                'comment' => 'Updated feedback',
            ])
            ->andReturnUsing(function (ProductRating $rating, array $data): ProductRating {
                $rating->update($data);

                return $rating->fresh();
            });
        $repository->shouldReceive('getAverageRating')
            ->once()
            ->with($product->id)
            ->andReturn(5.0);
        $repository->shouldReceive('getTotalRatings')
            ->once()
            ->with($product->id)
            ->andReturn(1);
        $repository->shouldReceive('getRatingBreakdown')
            ->once()
            ->with($product->id)
            ->andReturn([
                ['stars' => 5, 'count' => 1, 'percentage' => 100],
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->submitRating($product->id, 5.0, 'Updated feedback');

        $this->assertSame('Rating updated successfully', $result['message']);
        $this->assertSame('Updated feedback', $result['rating']->comment);
        $this->assertSame(5.0, $result['stats']['average_rating']);
    }
}

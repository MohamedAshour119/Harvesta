<?php

namespace Tests\Repositories\Products;

use App\Http\Repositories\ProductRepository;
use App\Models\IndustryCategory;
use App\Models\ProductRating;
use App\Models\User;
use Tests\Support\Repositories\Products\BuildsProductRepositoryFixtures;
use Tests\Support\RepositoryTestCase;

class ProductRepositoryDetailsTest extends RepositoryTestCase
{
    use BuildsProductRepositoryFixtures;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProductRepository::class);
    }

    public function test_get_product_by_slug_returns_product_with_related_data_and_four_similar_products(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Packaging', 'ar' => 'ØªØºÙ„ÙŠÙ'],
        ]);
        $productData = $this->createProductWithVariant('Premium Tape', 'premium-tape', $category);
        $ratingUser = User::factory()->create(['full_name' => 'Maha Salem']);
        ProductRating::query()->create([
            'user_id' => $ratingUser->id,
            'product_id' => $productData['product']->id,
            'rating' => 4.0,
            'comment' => 'Reliable',
        ]);

        foreach (range(1, 5) as $index) {
            $this->createProductWithVariant("Similar {$index}", "similar-{$index}", $category);
        }

        $result = $this->repository->getProductBySlug('premium-tape');

        $this->assertSame($productData['product']->id, $result['product']?->id);
        $this->assertTrue($result['product']->relationLoaded('industrialCategory'));
        $this->assertTrue($result['product']->relationLoaded('variants'));
        $this->assertTrue($result['product']->relationLoaded('ratings'));
        $this->assertTrue($result['product']->ratings->first()->relationLoaded('user'));
        $this->assertCount(4, $result['similar_products']);
    }
}

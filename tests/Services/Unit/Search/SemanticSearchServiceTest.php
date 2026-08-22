<?php

namespace Tests\Services\Unit\Search;

use App\Http\Services\JinaEmbeddingService;
use App\Http\Services\SemanticSearchService;
use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Models\ProductImageEmbedding;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class SemanticSearchServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return SemanticSearchService::class;
    }

    public function test_search_returns_empty_array_when_query_embedding_is_empty(): void
    {
        $jinaService = $this->mockDependency(JinaEmbeddingService::class);
        $jinaService->shouldReceive('generateSingleEmbedding')
            ->once()
            ->with('no vector')
            ->andReturn([]);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame([], $service->search('no vector'));
    }

    public function test_search_returns_approved_products_sorted_by_similarity(): void
    {
        $bestMatch = $this->createSearchableProduct('best-match');
        $secondMatch = $this->createSearchableProduct('second-match');
        $pendingProduct = $this->createSearchableProduct('pending-match', ['status' => 'pending']);

        ProductEmbedding::updateOrCreate([
            'product_id' => $bestMatch->id,
        ], [
            'product_id' => $bestMatch->id,
            'embedding' => json_encode([1.0, 0.0], JSON_THROW_ON_ERROR),
            'text_hash' => hash('sha256', 'best-match'),
        ]);
        ProductEmbedding::updateOrCreate([
            'product_id' => $secondMatch->id,
        ], [
            'product_id' => $secondMatch->id,
            'embedding' => json_encode([0.6, 0.8], JSON_THROW_ON_ERROR),
            'text_hash' => hash('sha256', 'second-match'),
        ]);
        ProductEmbedding::updateOrCreate([
            'product_id' => $pendingProduct->id,
        ], [
            'product_id' => $pendingProduct->id,
            'embedding' => json_encode([1.0, 0.0], JSON_THROW_ON_ERROR),
            'text_hash' => hash('sha256', 'pending-match'),
        ]);

        $jinaService = $this->mockDependency(JinaEmbeddingService::class);
        $jinaService->shouldReceive('generateSingleEmbedding')
            ->once()
            ->with('industrial cleaner')
            ->andReturn([1.0, 0.0]);

        $service = $this->instantiateService($this->serviceClass());
        $results = $service->search('industrial cleaner', 2);

        $this->assertSame([$bestMatch->id, $secondMatch->id], array_map(fn (Product $product) => $product->id, $results));
    }

    public function test_search_by_image_returns_empty_array_when_image_embedding_is_empty(): void
    {
        $jinaService = $this->mockDependency(JinaEmbeddingService::class);
        $jinaService->shouldReceive('generateImageEmbedding')
            ->once()
            ->with('image-data')
            ->andReturn([]);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame([], $service->searchByImage('image-data'));
    }

    public function test_search_by_image_filters_out_low_scoring_and_empty_vectors(): void
    {
        $bestMatch = $this->createSearchableProduct('image-best');
        $lowScore = $this->createSearchableProduct('image-low');
        $emptyVector = $this->createSearchableProduct('image-empty');
        $pendingProduct = $this->createSearchableProduct('image-pending', ['status' => 'pending']);

        ProductImageEmbedding::create([
            'product_id' => $bestMatch->id,
            'image_url' => 'https://example.com/best.png',
            'embedding' => json_encode([0.0, 1.0], JSON_THROW_ON_ERROR),
            'image_hash' => hash('sha256', 'image-best'),
        ]);
        ProductImageEmbedding::create([
            'product_id' => $lowScore->id,
            'image_url' => 'https://example.com/low.png',
            'embedding' => json_encode([1.0, 0.0], JSON_THROW_ON_ERROR),
            'image_hash' => hash('sha256', 'image-low'),
        ]);
        ProductImageEmbedding::create([
            'product_id' => $emptyVector->id,
            'image_url' => 'https://example.com/empty.png',
            'embedding' => json_encode([], JSON_THROW_ON_ERROR),
            'image_hash' => hash('sha256', 'image-empty'),
        ]);
        ProductImageEmbedding::create([
            'product_id' => $pendingProduct->id,
            'image_url' => 'https://example.com/pending.png',
            'embedding' => json_encode([0.0, 1.0], JSON_THROW_ON_ERROR),
            'image_hash' => hash('sha256', 'image-pending'),
        ]);

        $jinaService = $this->mockDependency(JinaEmbeddingService::class);
        $jinaService->shouldReceive('generateImageEmbedding')
            ->once()
            ->with('sample-image')
            ->andReturn([0.0, 1.0]);

        $service = $this->instantiateService($this->serviceClass());
        $results = $service->searchByImage('sample-image', 5);

        $this->assertSame([$bestMatch->id], array_map(fn (Product $product) => $product->id, $results));
    }

    private function createSearchableProduct(string $slug, array $attributes = []): Product
    {
        $product = Product::factory()->create(array_merge([
            'slug' => $slug,
            'status' => 'approved',
        ], $attributes));

        ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        return $product;
    }
}

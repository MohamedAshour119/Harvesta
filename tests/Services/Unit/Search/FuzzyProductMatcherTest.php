<?php

namespace Tests\Services\Unit\Search;

use App\Http\Services\FuzzyProductMatcher;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuzzyProductMatcherTest extends TestCase
{
    use RefreshDatabase;

    private FuzzyProductMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new FuzzyProductMatcher();
    }

    public function test_match_returns_ranked_approved_products_that_have_variants(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Office', 'ar' => 'Office'],
            'slug' => 'office',
        ]);

        $exact = $this->createProduct($category, 'Luxury Office Chair', 'Ergonomic chair for long work days.', 'approved', true);
        $partial = $this->createProduct($category, 'Office Stool', 'Basic office seating.', 'approved', true);
        $this->createProduct($category, 'Luxury Office Chair Copy', 'Pending product.', 'pending', true);
        $this->createProduct($category, 'Office Chair Without Variant', 'Should be ignored.', 'approved', false);

        $matches = $this->matcher->match('luxury office chair');

        $this->assertGreaterThanOrEqual(2, $matches->count());
        $this->assertSame($exact->id, $matches->first()['product']->id);
        $this->assertTrue($matches->pluck('product.id')->contains($partial->id));
        $this->assertFalse($matches->pluck('product.slug')->contains('office-chair-without-variant'));
    }

    public function test_match_uses_drink_intent_expansion_for_beverage_queries(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Beverages', 'ar' => 'Beverages'],
            'slug' => 'beverages',
        ]);

        $tea = $this->createProduct($category, 'Green Tea', 'Tea leaves for cafes.', 'approved', true);

        $matches = $this->matcher->match('warm drink');

        $this->assertTrue($matches->pluck('product.id')->contains($tea->id));
    }

    public function test_match_returns_an_empty_collection_for_too_short_queries(): void
    {
        $this->assertTrue($this->matcher->match('a')->isEmpty());
    }

    private function createProduct(
        IndustryCategory $category,
        string $name,
        string $description,
        string $status,
        bool $withVariant
    ): Product {
        $product = Product::query()->create([
            'industry_category_id' => $category->id,
            'name' => ['en' => $name, 'ar' => $name],
            'description' => ['en' => $description, 'ar' => $description],
            'status' => $status,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]);

        if ($withVariant) {
            ProductVariant::query()->create([
                'product_id' => $product->id,
                'variant_value' => 'Default',
                'unit_price_usd' => 10,
                'availability' => 'in-stock',
            ]);
        }

        return $product;
    }
}

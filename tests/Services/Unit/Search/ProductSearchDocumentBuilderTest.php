<?php

namespace Tests\Services\Unit\Search;

use App\Http\Services\ProductSearchDocumentBuilder;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchDocumentBuilderTest extends TestCase
{
    use RefreshDatabase;

    private ProductSearchDocumentBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new ProductSearchDocumentBuilder();
    }

    public function test_build_includes_product_category_slug_and_variant_terms(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Beverages', 'ar' => 'Beverages'],
            'slug' => 'beverages',
        ]);

        $variantType = VariantType::query()->create([
            'name' => ['en' => 'Size', 'ar' => 'Size'],
        ]);

        $product = Product::query()->create([
            'industry_category_id' => $category->id,
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Loose leaf tea for cafes.', 'ar' => 'Loose leaf tea for cafes.'],
            'slug' => 'premium-tea',
            'status' => 'approved',
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_type_id' => $variantType->id,
            'variant_value' => '1 kg',
            'unit_price_usd' => 19.5,
        ]);

        $document = $this->builder->build($product->fresh());

        $this->assertStringContainsString('Premium Tea', $document);
        $this->assertStringContainsString('premium-tea', $document);
        $this->assertStringContainsString('beverages', $document);
        $this->assertStringContainsString('Size', $document);
        $this->assertStringContainsString('1 kg', $document);
    }

    public function test_build_deduplicates_segments_and_limits_long_descriptions(): void
    {
        $longDescription = str_repeat('a', 260);

        $product = Product::query()->create([
            'name' => ['en' => 'SameName', 'ar' => 'SameName'],
            'description' => ['en' => $longDescription, 'ar' => $longDescription],
            'slug' => 'same-name-sku',
            'status' => 'approved',
        ]);

        $document = $this->builder->build($product);

        $this->assertSame(1, substr_count($document, 'SameName'));
        $this->assertStringContainsString(str_repeat('a', 217) . '...', $document);
    }
}

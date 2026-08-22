<?php

namespace Tests\Repositories\Cart;

use App\Http\Repositories\CartRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Tests\Support\RepositoryTestCase;

class CartRepositoryTest extends RepositoryTestCase
{
    private CartRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CartRepository::class);
    }

    public function test_get_products_by_ids_returns_requested_products_with_only_requested_variants(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Beverages', 'ar' => 'مشروبات'],
        ]);
        $firstProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
            'name' => ['en' => 'Premium Tea', 'ar' => 'شاي فاخر'],
        ]);
        $secondProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
            'name' => ['en' => 'Coffee Beans', 'ar' => 'حبوب قهوة'],
        ]);
        $ignoredProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        $firstWantedVariant = ProductVariant::factory()->create(['product_id' => $firstProduct->id]);
        ProductVariant::factory()->create(['product_id' => $firstProduct->id]);
        $secondWantedVariant = ProductVariant::factory()->create(['product_id' => $secondProduct->id]);
        ProductVariant::factory()->create(['product_id' => $ignoredProduct->id]);

        $products = $this->repository->getProductsByIds([
            ['productId' => $firstProduct->id, 'productVariantId' => $firstWantedVariant->id],
            ['productId' => $secondProduct->id, 'productVariantId' => $secondWantedVariant->id],
        ]);

        $this->assertSame([$firstProduct->id, $secondProduct->id], $products->pluck('id')->all());
        $this->assertTrue($products->every(fn (Product $product): bool => $product->relationLoaded('industrialCategory')));
        $this->assertSame([$firstWantedVariant->id], $products[0]->variants->pluck('id')->all());
        $this->assertSame([$secondWantedVariant->id], $products[1]->variants->pluck('id')->all());
    }
}

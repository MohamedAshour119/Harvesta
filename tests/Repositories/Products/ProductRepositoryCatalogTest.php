<?php

namespace Tests\Repositories\Products;

use App\Http\Repositories\ProductRepository;
use App\Models\IndustryCategory;
use App\Models\Order;
use App\Models\Product;
use Tests\Support\Repositories\Products\BuildsProductRepositoryFixtures;
use Tests\Support\RepositoryTestCase;

class ProductRepositoryCatalogTest extends RepositoryTestCase
{
    use BuildsProductRepositoryFixtures;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProductRepository::class);
    }

    public function test_get_all_products_returns_paginated_products_that_have_variants_with_relations_loaded(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Cleaning', 'ar' => 'ØªÙ†Ø¸ÙŠÙ'],
        ]);
        $included = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);
        \App\Models\ProductVariant::factory()->create(['product_id' => $included->id]);

        Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        $results = $this->repository->getAllProducts();

        $this->assertSame([$included->id], collect($results->items())->pluck('id')->all());
        $this->assertTrue(collect($results->items())->every(fn (Product $product): bool => $product->relationLoaded('industrialCategory')));
    }

    public function test_get_top_products_orders_products_by_total_sold_quantity(): void
    {
        $first = $this->createProductWithVariant('Top Cleaner', 'top-cleaner');
        $second = $this->createProductWithVariant('Second Cleaner', 'second-cleaner');

        $order = Order::factory()->create(['status' => 'delivered', 'total' => 200, 'subtotal' => 200]);
        $order->items()->create([
            'product_id' => $first['product']->id,
            'product_variant_id' => $first['variant']->id,
            'supplier_id' => null,
            'quantity' => 5,
            'unit_price' => 20,
            'cost_plus_logistics' => 0,
            'total' => 100,
        ]);
        $order->items()->create([
            'product_id' => $second['product']->id,
            'product_variant_id' => $second['variant']->id,
            'supplier_id' => null,
            'quantity' => 2,
            'unit_price' => 50,
            'cost_plus_logistics' => 0,
            'total' => 100,
        ]);

        $results = $this->repository->getTopProducts();

        $this->assertSame([$first['product']->id, $second['product']->id], $results->pluck('id')->take(2)->all());
        $this->assertSame(5, (int) $results->first()->total_sold);
    }
}

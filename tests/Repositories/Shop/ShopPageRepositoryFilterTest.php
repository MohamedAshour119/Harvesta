<?php

namespace Tests\Repositories\Shop;

use App\Http\Repositories\ShopPageRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Repositories\Shop\BuildsShopPageProducts;
use Tests\Support\RepositoryTestCase;

class ShopPageRepositoryFilterTest extends RepositoryTestCase
{
    use BuildsShopPageProducts;

    private ShopPageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ShopPageRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_filter_products_returns_empty_paginator_when_category_slug_does_not_exist(): void
    {
        $result = $this->repository->filterProducts('missing-category');

        $this->assertSame(0, $result['products']->total());
        $this->assertSame([], $result['industrial_categories']);
    }

    public function test_filter_products_applies_search_semantic_price_category_and_stock_filters_for_agent_viewers(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-SHOP-1',
        ]);
        Sanctum::actingAs($agent);

        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Cleaning', 'ar' => 'ØªÙ†Ø¸ÙŠÙ'],
            'slug' => 'cleaning',
        ]);
        $otherCategory = IndustryCategory::query()->create([
            'name' => ['en' => 'Packaging', 'ar' => 'ØªØºÙ„ÙŠÙ'],
            'slug' => 'packaging',
        ]);

        $textMatch = $this->createShopProduct($category, 'Clean Pro', 'clean-pro', [
            'agent_price' => 40,
            'unit_price_usd' => 55,
            'availability' => 'in-stock',
        ]);
        $semanticMatch = $this->createShopProduct($category, 'Warehouse Supply', 'warehouse-supply', [
            'agent_price' => 35,
            'unit_price_usd' => 52,
            'availability' => 'in-stock',
        ]);
        $this->createShopProduct($category, 'Clean Max', 'clean-max', [
            'agent_price' => 60,
            'unit_price_usd' => 65,
            'availability' => 'in-stock',
        ]);
        $this->createShopProduct($category, 'Clean Empty', 'clean-empty', [
            'agent_price' => 30,
            'unit_price_usd' => 40,
            'availability' => 'out-of-stock',
        ]);
        $this->createShopProduct($otherCategory, 'Clean Other', 'clean-other', [
            'agent_price' => 32,
            'unit_price_usd' => 45,
            'availability' => 'in-stock',
        ]);

        $result = $this->repository->filterProducts(
            'cleaning/in-stock',
            30,
            45,
            'clean',
            [$semanticMatch['product']->id]
        );

        $productIds = collect($result['products']->items())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$textMatch['product']->id, $semanticMatch['product']->id], $productIds);
        $this->assertTrue(collect($result['products']->items())->every(
            fn (Product $product): bool => $product->variants->every(fn (ProductVariant $variant): bool => $variant->availability === 'in-stock')
        ));
        $this->assertTrue(collect($result['industrial_categories'])->contains('slug', 'cleaning'));
    }

    public function test_filter_products_supports_today_deals_and_all_discounts_filters(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Offers', 'ar' => 'Ø¹Ø±ÙˆØ¶'],
            'slug' => 'offers',
        ]);

        $todayDeal = $this->createShopProduct($category, 'Today Deal', 'today-deal', [
            'discount_percentage' => 15,
            'discount_applied_at' => now(),
            'availability' => 'in-stock',
        ]);
        $olderDiscount = $this->createShopProduct($category, 'Old Deal', 'old-deal', [
            'discount_percentage' => 10,
            'discount_applied_at' => now()->subDay(),
            'availability' => 'in-stock',
        ]);
        $this->createShopProduct($category, 'No Discount', 'no-discount', [
            'discount_percentage' => 0,
            'discount_applied_at' => null,
            'availability' => 'in-stock',
        ]);

        $todayDeals = $this->repository->filterProducts('today-deals');
        $allDiscounts = $this->repository->filterProducts('all-discounts');

        $this->assertSame([$todayDeal['product']->id], collect($todayDeals['products']->items())->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$todayDeal['product']->id, $olderDiscount['product']->id],
            collect($allDiscounts['products']->items())->pluck('id')->all()
        );
    }
}

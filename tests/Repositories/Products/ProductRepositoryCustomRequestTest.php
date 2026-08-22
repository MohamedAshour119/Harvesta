<?php

namespace Tests\Repositories\Products;

use App\Http\Repositories\ProductRepository;
use Illuminate\Support\Facades\Storage;
use Tests\Support\RepositoryTestCase;

class ProductRepositoryCustomRequestTest extends RepositoryTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProductRepository::class);
    }

    public function test_request_custom_product_persists_the_custom_product_record(): void
    {
        config()->set('media-library.disk_name', 'public');
        Storage::fake('public');

        $result = $this->repository->requestCustomProduct([
            'full_name' => 'Custom Buyer',
            'email' => 'custom@example.com',
            'phone' => '123456789',
            'product_name' => 'Custom Bottle',
            'product_description' => 'Need a private-label bottle.',
            'estimated_quantity' => 500,
            'budget' => 2500,
            'deadline' => now()->addWeek()->toDateString(),
        ]);

        $this->assertDatabaseHas('custom_products', [
            'email' => 'custom@example.com',
            'product_name' => 'Custom Bottle',
        ]);
        $this->assertSame('Custom Buyer', $result->full_name);
    }
}

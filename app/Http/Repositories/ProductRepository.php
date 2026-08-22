<?php

namespace App\Http\Repositories;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\CustomProduct;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function getAllProducts()
    {
        return Product::with(['industrialCategory', 'smartBundle'])
            ->has('variants')
            ->paginate(16);
    }
    public function getTopProducts()
    {
        return Product::with(['industrialCategory', 'smartBundle'])
            ->has('variants')
            ->withSum('orderItems as total_sold', 'quantity')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();
    }

    public function getProductBySlug($slug)
    {
        $product = Product::where('slug', $slug)
            ->with([
                'industrialCategory', 
                'variants', 
                'variants.variantType', 
                'smartBundle',
                'ratings' => function($query) {
                    $query->with('user:id,full_name')
                          ->select('id', 'user_id', 'product_id', 'rating', 'comment', 'created_at', 'updated_at')
                          ->latest();
                },
            ])
            ->first();

        $similarProducts = Product::with(['industrialCategory', 'variants'])
            ->has('variants')
            ->take(4)->get();

        return [
            'product' => $product,
            'similar_products' => $similarProducts
        ];
    }

    public function requestCustomProduct($data)
    {
        $filteredData = Arr::except($data, ['product_images']);
        info('filteredData', [$filteredData]);
        $customProduct = CustomProduct::create($filteredData);


        if (isset($data['product_images'])) {
            $customProduct->clearMediaCollection('custom_product_images');

            // Flatten the array to handle nested arrays of files
            $images = Arr::flatten($data['product_images']);

            info('final_images after flatten', $images);

            foreach ($images as $image) {
                if (is_object($image) && $image instanceof \Illuminate\Http\UploadedFile) {
                    $customProduct->addMedia($image)->toMediaCollection('custom_product_images');
                } 
            }
        }
        
        return $customProduct;
    }
}
<?php

namespace App\Http\Services;

use App\Helper\Helper;
use App\Http\Repositories\ProductRepository;
use App\Http\Resources\ProductResource;

class ProductService
{
    public function __construct(protected ProductRepository $productRepository) {}

    public function getAllProducts()
    {
        $results = $this->productRepository->getAllProducts();
        $pagination = Helper::preparePagination($results);
        $products = ProductResource::collection($results);
        
        return [
            "products" => $products,
            "pagination" => $pagination
        ];
    }

    public function getTopProducts()
    {
        $results = $this->productRepository->getTopProducts();
        // $pagination = Helper::preparePagination($results);
        $products = ProductResource::collection($results);
        
        return [
            "products" => $products,
            // "pagination" => $pagination
        ];
    }

    public function getProductBySlug($slug)
    {
        $data = $this->productRepository->getProductBySlug($slug);
        
        if (!$data['product']) {
            return null;
        }
        
        $product = new ProductResource($data['product'], 'product_page', true);
        $similarProducts = $data['similar_products']->map(function ($item) {
            return new ProductResource($item, 'product_card', false);
        });        

        return [
            'product' => $product,
            'similar_products' => $similarProducts
        ];
    }

    public function requestCustomProduct($data)
    {
        $results = $this->productRepository->requestCustomProduct($data);
        return $results;
    }
}

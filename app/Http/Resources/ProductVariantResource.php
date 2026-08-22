<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{

    protected $isWishlistable = false;

    public function __construct($resource, $isWishlistable = false)
    {
        parent::__construct($resource);
        $this->isWishlistable = $isWishlistable;
    }

    public function toArray(Request $request): array
    {
        $viewer = auth()->guard('sanctum')->user();
        $mainImage = $this->getFirstMediaUrl('variants_images');
        $mainProductImage = $this->product->getFirstMediaUrl('main_images', 'slider');

        $data = [];

        if ($this->isWishlistable) {
            $data['productId'] = $this->product_id;
            $data['variantId'] = $this->id;
            $data['productName'] = $this->product->name;
            $data['description'] = $this->product->description;
            $data['slug'] = $this->product->slug;
            $data['category'] = [
                'id' => $this->product->industry_category_id,
                'name' => $this->product->industrialCategory?->name,
            ];
            $data['variantName'] = $this->variantType?->name;
            $data['variantValue'] = $this->variant_value;
            $data['agent_price'] = $this->resolveAgentPrice();
            $data['price'] = $this->resolveSiteDisplayPrice($viewer);
            $data['originalPrice'] = $this->resolveSiteOriginalPrice($viewer);
            $data['image'] = $mainImage;
            $data['product_main_image'] = $mainProductImage;
        } else {
            $data['id'] = $this->id;
            $data['variant_name'] = $this->variantType?->name;
            $data['variant_value'] = $this->variant_value;
            $data['agent_price'] = $this->resolveAgentPrice();
            $data['unit_price_usd'] = $this->resolveSiteOriginalPrice($viewer);
            $data['stock_availability'] = $this->stock_availability ?? $this->availability;
            $data['total_quantity'] = $this->total_quantity;
            $data['price_after_discount'] = $this->resolveSiteDisplayPrice($viewer);
            $data['discount_percentage'] = $this->resolveSiteDiscountPercentage($viewer);
            $data['main_image'] = $mainImage;
            $data['main_product_image'] = $mainProductImage;
            $data['created_at'] = $this->created_at;
            $data['updated_at'] = $this->updated_at;
        }

        return $data;
    }
}

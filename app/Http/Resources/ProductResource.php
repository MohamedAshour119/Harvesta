<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth; 
use App\Models\WishlistItem; 

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    protected $image_conversion;
    protected $is_product_page;
    protected static $globalBundleCache = null;
    protected static $hasCheckedGlobal = false;

    public function __construct($resource, $image_conversion = null, $is_product_page = false)
    {
        parent::__construct($resource);
        $this->image_conversion = $image_conversion;
        $this->is_product_page = $is_product_page;
    }

    private function getGlobalBundle()
    {
        if (!self::$hasCheckedGlobal) {
            self::$globalBundleCache = \App\Models\SmartBundle::whereNull('product_id', 'and', false)
                ->where('is_active', '=', true, 'and')
                ->first(['*']);
            self::$hasCheckedGlobal = true;
        }
        return self::$globalBundleCache;
    }
    
    public function toArray(Request $request): array
    {
        $viewer = auth()->guard('sanctum')->user();

        $main_image = $this->is_product_page ? $this->getFirstMediaUrl('main_images', 'product_page') : $this->getFirstMediaUrl('main_images', 'product_page');
        $other_images = $this->getMedia('products_other_images')
        ->map(function ($media) {
            return $media->getUrl('product_page'); // Use getUrl with conversion name
        });
        
        $images = [
            "main_image" => $main_image,
        ];

        $cheapestVariant = $this->variants->sortBy(fn ($variant) => $variant->resolveSiteComparablePrice($viewer))->first();

        $variantData = null;
        if ($cheapestVariant) {
            if ($this->is_product_page) {
                $variantData = [
                    "id" => $cheapestVariant->id,
                    "product_id" => $cheapestVariant->product_id,
                    "variant_name" => $cheapestVariant->variantType?->name,
                    "variant_value" => $cheapestVariant->variant_value,
                    "agent_price" => $cheapestVariant->resolveAgentPrice(),
                    "unit_price_usd" => $cheapestVariant->resolveSiteOriginalPrice($viewer),
                    "total_quantity" => $cheapestVariant->total_quantity,
                    "main_image" => $cheapestVariant->getFirstMediaUrl('variants_images'),
                    "stock_availability" => $cheapestVariant->stock_availability ?? $cheapestVariant->availability,
                    "price_after_discount" => $cheapestVariant->resolveSiteDisplayPrice($viewer),
                    "discount_percentage" => $cheapestVariant->resolveSiteDiscountPercentage($viewer),
                    "created_at" => $cheapestVariant->created_at,
                    "updated_at" => $cheapestVariant->updated_at,
                ];
            } else {
                $variantData = [
                    "id" => $cheapestVariant->id,
                    "agent_price" => $cheapestVariant->resolveAgentPrice(),
                    "unit_price_usd" => $cheapestVariant->resolveSiteOriginalPrice($viewer),
                    "total_quantity" => $cheapestVariant->total_quantity,
                    "main_image" => $cheapestVariant->getFirstMediaUrl('variants_images'),
                    "stock_availability" => $cheapestVariant->stock_availability ?? $cheapestVariant->availability,
                    "price_after_discount" => $cheapestVariant->resolveSiteDisplayPrice($viewer),
                    "discount_percentage" => $cheapestVariant->resolveSiteDiscountPercentage($viewer),
                ];
            }
        }

        $isInWishlist = false;
        if ($viewer && $cheapestVariant) {
            $isInWishlist = WishlistItem::where('user_id', '=', $viewer->id, 'and')
                ->where('product_id', '=', $this->id, 'and')
                ->where('variant_id', '=', $cheapestVariant->id, 'and')
                ->exists();
        }

        $data = [
            "id"=> $this->id,
            "name"=> $this->name,
            "description" => $this->description,
            "slug" => $this->slug,
            "category" => new IndustrialCategoryResource($this->whenLoaded('industrialCategory'), false),
            "status" => $this->status,
            "images" => $images,
            "created_at" => $this->created_at,
            "cheapestVariant" => $variantData,
            "is_in_wishlist" => $isInWishlist, 
            "min_order_value" => $this->resolveSiteMinOrderValue($viewer),
            "agent_min_order_value" => $this->resolveAgentMinOrderValue(),
            "minimum_order_quantity" => $this->minimum_order_quantity,
            "smart_bundle" => $this->getSmartBundleData(),
        ];

        // $data["variants"] = ProductVariantResource::collection($this->variants)->map(function ($variant) {
        //     $variantResource = new ProductVariantResource($variant, false);
        //     $variantData = $variantResource->toArray(request());
            
        //     return $variantData;
        // });


        if ($this->is_product_page) {
            // Add rating statistics for each variant
            if ($this->relationLoaded('ratings')) {
                $ratingsWithScore = $this->ratings->whereNotNull('rating', 'and');
                $totalRatings = $ratingsWithScore->count();
                
                if ($totalRatings > 0) {
                    $averageRating = $ratingsWithScore->avg('rating');
                    
                    // Calculate rating breakdown
                    $ratingBreakdown = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $count = $ratingsWithScore->where('rating', '>=', (string)($i - 0.5), 'and', false)->where('rating', '<', (string)($i + 0.5), 'and', false)->count();
                        $percentage = $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0;
                        $ratingBreakdown[$i] = [
                            'count' => $count,
                            'percentage' => $percentage
                        ];
                    }
                    
                    $data['rating_stats'] = [
                        'average_rating' => round($averageRating),
                        'total_ratings' => $totalRatings,
                        'rating_breakdown' => array_reverse($ratingBreakdown) // 5 stars first
                    ];
                } else {
                    $data['rating_stats'] = [
                        'average_rating' => 0,
                        'total_ratings' => 0,
                        'rating_breakdown' => []
                    ];
                }
            }


            $data["images"]["other_images"] = $other_images;
            $data["min_order_value"] = $this->resolveSiteMinOrderValue($viewer);
            $data["agent_min_order_value"] = $this->resolveAgentMinOrderValue();
            $data["is_approved"] = $this->is_approved;
            $data["oem"] = $this->oem;
            $data["notes"] = $this->notes;
            $data["variants"] = ProductVariantResource::collection($this->variants)->map(function ($variant) {
                $variantResource = new ProductVariantResource($variant, false);
                $variantData = $variantResource->toArray(request());
                
                return $variantData;
            });
        }

        return $data;
    }

    private function getSmartBundleData()
    {
        $bundle = null;
        if ($this->relationLoaded('smartBundle') && $this->smartBundle) {
            $bundle = $this->smartBundle;
        } else {
            $bundle = $this->getGlobalBundle();
        }

        if (!$bundle) return null;

        return [
            'name' => $bundle->getTranslations('name'),
            'target_quantity' => $bundle->target_quantity,
            'discount_percentage' => (float) $bundle->discount_percentage,
        ];
    }
}

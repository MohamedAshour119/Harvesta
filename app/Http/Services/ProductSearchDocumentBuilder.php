<?php

namespace App\Http\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductSearchDocumentBuilder
{
    public function build(Product $product): string
    {
        $segments = collect([
            $product->getTranslation('name', 'en', false),
            $product->getTranslation('name', 'ar', false),
            $product->slug,
            optional($product->industrialCategory)->slug,
            optional($product->industrialCategory)->getTranslation('name', 'en', false),
            optional($product->industrialCategory)->getTranslation('name', 'ar', false),
            Str::limit((string) $product->getTranslation('description', 'en', false), 220),
            Str::limit((string) $product->getTranslation('description', 'ar', false), 220),
        ]);

        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->with('variantType')->get();

        $variantSegments = $variants
            ->flatMap(function ($variant) {
                return [
                    $variant->variantType?->name,
                    $variant->variant_value,
                ];
            });

        return $segments
            ->merge($variantSegments)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->implode(' | ');
    }
}

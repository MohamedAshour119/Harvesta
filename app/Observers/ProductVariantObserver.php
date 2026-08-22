<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "saved" event.
     */
    public function saved(ProductVariant $variant): void
    {
        try {
            // When a variant is saved, we want to make sure the Product's embeddings are updated
            // and specifically index the variant's image.
            $product = $variant->product;
            if ($product) {
                // Trigger the ProductObserver's logic or call the embedding generator
                // For now, we can just call the ProductObserver if we have access or use a service.
                // A better way is to move the logic to a service.
                $observer = new ProductObserver();
                
                // Index the Product's main images (just in case they weren't ready before)
                $observer->saved($product);

                // Index the Variant's specific images
                $this->indexVariantImages($variant, $observer);
            }
        } catch (\Exception $e) {
            Log::error('ProductVariantObserver Error: ' . $e->getMessage());
        }
    }

    /**
     * Index images specific to this variant
     */
    private function indexVariantImages(ProductVariant $variant, ProductObserver $productObserver): void
    {
        $mediaRecords = $variant->getMedia('variants_images');
        
        foreach ($mediaRecords as $media) {
            $imagePath = $media->getPath();
            $imageUrl = $media->getUrl();

            if (empty($imagePath) || !file_exists($imagePath)) {
                continue;
            }

            // We can reuse the logic from ProductObserver or make a shared service
            // For now, let's keep it simple.
            $this->generateVariantImageEmbedding($variant, $media);
        }
    }

    private function generateVariantImageEmbedding(ProductVariant $variant, $media): void
    {
        try {
            $imagePath = $media->getPath();
            $imageUrl = $media->getUrl();
            $hash = hash('sha256', file_get_contents($imagePath));

            // Fetch existing
            $existing = \App\Models\ProductImageEmbedding::where('image_hash', '=', $hash)
                ->where('product_id', '=', $variant->product_id)
                ->first();

            if ($existing) {
                return;
            }

            $jinaService = app(\App\Http\Services\JinaEmbeddingService::class);
            $imageData = file_get_contents($imagePath);
            $base64Image = 'data:' . mime_content_type($imagePath) . ';base64,' . base64_encode($imageData);

            $embedding = $jinaService->generateImageEmbedding($base64Image);

            if (!empty($embedding)) {
                \App\Models\ProductImageEmbedding::create([
                    'product_id' => $variant->product_id,
                    'image_url' => $imageUrl,
                    'embedding' => json_encode($embedding),
                    'image_hash' => $hash,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Variant Image Embedding Error: ' . $e->getMessage());
        }
    }
}

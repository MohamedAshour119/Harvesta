<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Models\ProductImageEmbedding;
use App\Http\Services\JinaEmbeddingService;
use App\Http\Services\ProductSearchDocumentBuilder;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "saved" event.
     * This triggers on both created and updated.
     */
    public function saved(Product $product): void
    {
        try {
            $jinaService = app(JinaEmbeddingService::class);
            $searchDocumentBuilder = app(ProductSearchDocumentBuilder::class);

            // Keep embeddings aligned with the same rich search text used by smart search ranking.
            $textForEmbedding = $searchDocumentBuilder->build($product);

            $hash = hash('sha256', $textForEmbedding);

            // Fetch existing embedding
            $existing = ProductEmbedding::where('product_id', '=', $product->id)->first();

            // Generate text embedding only if text has changed
            if (!$existing || $existing->text_hash !== $hash) {
                $embedding = $jinaService->generateSingleEmbedding($textForEmbedding, 'retrieval.passage');

                if (!empty($embedding)) {
                    ProductEmbedding::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'embedding' => json_encode($embedding),
                            'text_hash' => $hash,
                        ]
                    );
                }
            }

            // Always check Image Embedding (Visual Search) — even if text didn't change
            $this->generateImageEmbedding($product);

        } catch (\Exception $e) {
            Log::error('ProductObserver Embedding Error: ' . $e->getMessage());
        }
    }

    /**
     * Generate/Update image embedding for visual search
     */
    private function generateImageEmbedding(Product $product): void
    {
        try {
            $imagePath = $product->getFirstMediaPath('main_images');
            $imageUrl = $product->getFirstMediaUrl('main_images');

            // Fallback: use first variant's image if product has no main image
            if (empty($imagePath) || !file_exists($imagePath)) {
                $variant = $product->variants()->first();
                if ($variant) {
                    $imagePath = $variant->getFirstMediaPath('variant_images');
                    $imageUrl = $variant->getFirstMediaUrl('variant_images');
                }
            }

            if (empty($imagePath) || !file_exists($imagePath)) {
                return;
            }

            // Read image and calculate hash
            $imageData = file_get_contents($imagePath);
            $hash = hash('sha256', $imageData);

            // Fetch existing image embedding
            $existing = ProductImageEmbedding::where('product_id', '=', $product->id)
                ->where('image_hash', '=', $hash)
                ->first();

            // If image hash hasn't changed, skip
            if ($existing && $existing->image_hash === $hash) {
                return;
            }

            // Prepare for Jina AI
            $jinaService = app(JinaEmbeddingService::class);
            $base64Image = 'data:' . mime_content_type($imagePath) . ';base64,' . base64_encode($imageData);

            // Generate embedding using CLIP model
            $embedding = $jinaService->generateImageEmbedding($base64Image);

            if (!empty($embedding)) {
                ProductImageEmbedding::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'image_hash' => $hash,
                    ],
                    [
                        'image_url' => $imageUrl,
                        'embedding' => json_encode($embedding),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('ProductObserver Image Embedding Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Fallback safety measure
        ProductEmbedding::where('product_id', '=', $product->id)->delete();
        ProductImageEmbedding::where('product_id', '=', $product->id)->delete();
    }
}

<?php

namespace App\Observers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class MediaObserver
{
    /**
     * Handle the Media "created" event.
     * This fires when a new file is added/associated with a model.
     */
    public function created(Media $media): void
    {
        try {
            $model = $media->model;

            if ($model instanceof Product) {
                // Trigger product embeddings (text and image)
                $productObserver = new ProductObserver();
                $productObserver->saved($model);
                
                Log::info("MediaObserver: Triggered ProductObserver for Product ID {$model->id}");
            } elseif ($model instanceof ProductVariant) {
                // Trigger variant embeddings
                $variantObserver = new ProductVariantObserver();
                $variantObserver->saved($model);
                
                Log::info("MediaObserver: Triggered ProductVariantObserver for Variant ID {$model->id}");
            }
        } catch (\Exception $e) {
            Log::error('MediaObserver Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Media "updated" event.
     */
    public function updated(Media $media): void
    {
        $this->created($media);
    }

    /**
     * Handle the Media "deleted" event.
     */
    public function deleted(Media $media): void
    {
        try {
            $imageUrl = $media->getUrl();
            \App\Models\ProductImageEmbedding::where('image_url', '=', $imageUrl)->delete();
            Log::info("MediaObserver: Deleted embedding for image {$imageUrl}");
        } catch (\Exception $e) {
            Log::error('MediaObserver Delete Error: ' . $e->getMessage());
        }
    }
}

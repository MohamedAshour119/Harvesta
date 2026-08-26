<?php

namespace App\Console\Commands;

use App\Http\Services\JinaEmbeddingService;
use App\Models\Product;
use App\Models\ProductImageEmbedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateProductImageEmbeddings extends Command
{
    protected $signature = 'embeddings:generate-images
        {--force : Regenerate all embeddings even if unchanged}
        {--product= : ID of a specific product to process}
        {--delay=5 : Delay in seconds between API calls to avoid rate limiting}';

    protected $description = 'Generate Jina CLIP image embeddings for product main images';

    public function handle(JinaEmbeddingService $jinaService): int
    {
        $force = $this->option('force');
        $productId = $this->option('product');
        $delay = (int) $this->option('delay');

        $query = Product::query()->where('status', 'approved')->has('variants');
        if ($productId) {
            $query->where('id', $productId);
        }
        $products = $query->get();

        if ($products->isEmpty()) {
            $this->error('No approved products with variants found.');
            return Command::FAILURE;
        }

        $total = $products->count();
        $success = 0;
        $skipped = 0;
        $failed = 0;

        $this->info("Processing {$total} approved products (one main image each)...");
        $this->newLine();

        foreach ($products as $index => $product) {
            $counter = ($index + 1) . "/{$total}";
            $productName = $product->getTranslation('name', 'en');

            // Get main image, fallback to first variant's image
            $media = $product->getFirstMedia('main_images');

            if (!$media) {
                $variant = $product->variants()->first();
                if ($variant) {
                    $media = $variant->getFirstMedia('variant_images');
                }
            }

            if (!$media) {
                $this->warn("[{$counter}] ⚠️  No image found: {$productName} (ID: {$product->id})");
                $skipped++;
                continue;
            }

            $imagePath = $media->getPath();
            if (empty($imagePath) || !file_exists($imagePath)) {
                $this->warn("[{$counter}] ⚠️  Image file missing: {$media->file_name}");
                $skipped++;
                continue;
            }

            // Check if embedding already exists
            $imageData = file_get_contents($imagePath);
            $hash = hash('sha256', $imageData);

            $existing = ProductImageEmbedding::where('product_id', $product->id)
                ->where('image_hash', $hash)
                ->first();

            if ($existing && !$force) {
                $this->line("[{$counter}] ⏭️  Already exists: {$productName}");
                $skipped++;
                continue;
            }

            // Resize image to reduce payload size (max 512px)
            $resizedBase64 = $this->resizeAndEncode($imagePath, $media->mime_type);

            if (!$resizedBase64) {
                $this->error("[{$counter}] ❌ Failed to process image: {$media->file_name}");
                $failed++;
                continue;
            }

            $this->info("[{$counter}] ⌛ Generating: {$productName}...");

            // Retry up to 3 times
            $embedding = null;
            $maxRetries = 3;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $embedding = $jinaService->generateImageEmbedding($resizedBase64);

                if (!empty($embedding)) {
                    break;
                }

                if ($attempt < $maxRetries) {
                    $waitTime = $delay * $attempt * 2; // Exponential backoff
                    $this->warn("  ⏳ Retry {$attempt}/{$maxRetries} in {$waitTime}s...");
                    sleep($waitTime);
                }
            }

            if (!empty($embedding)) {
                // Delete any old embeddings for this product, keep only one per product
                ProductImageEmbedding::where('product_id', $product->id)->delete();

                // Generate CLIP text embedding for multimodal alignment
                $arName = $product->getTranslation('name', 'ar');
                $enName = $product->getTranslation('name', 'en');
                $catAr = $product->industrialCategory ? $product->industrialCategory->getTranslation('name', 'ar') : '';
                $catEn = $product->industrialCategory ? $product->industrialCategory->getTranslation('name', 'en') : '';
                $clipText = trim("{$arName} {$enName} {$catAr} {$catEn}");
                $textRes = $jinaService->generateEmbeddings([['text' => $clipText]], 'jina-clip-v2', null);
                $textEmbedding = $textRes[0] ?? null;

                ProductImageEmbedding::create([
                    'product_id' => $product->id,
                    'image_hash' => $hash,
                    'image_url' => $media->getUrl(),
                    'embedding' => json_encode($embedding),
                    'text_embedding' => !empty($textEmbedding) ? json_encode($textEmbedding) : null,
                ]);

                $this->info("[{$counter}] ✅ Done: {$productName}");
                $success++;
            } else {
                $this->error("[{$counter}] ❌ Failed after {$maxRetries} retries: {$productName}");
                $failed++;
            }

            // Delay between API calls
            if ($delay > 0 && $index < $total - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->info("========== Summary ==========");
        $this->info("Total products: {$total}");
        $this->info("✅ Success: {$success}");
        $this->info("⏭️  Skipped: {$skipped}");
        $this->error("❌ Failed: {$failed}");
        $this->info("Total embeddings in DB: " . ProductImageEmbedding::count());

        return $failed > 0 && $success === 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Resize image to max 512px and encode as base64 data URI.
     * Reduces payload size to avoid Jina API token limits.
     */
    private function resizeAndEncode(string $imagePath, string $mimeType): ?string
    {
        try {
            // Try using GD to resize
            if (extension_loaded('gd')) {
                $image = match (true) {
                    str_contains($mimeType, 'png') => imagecreatefrompng($imagePath),
                    str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => imagecreatefromjpeg($imagePath),
                    str_contains($mimeType, 'webp') => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($imagePath) : null,
                    default => null,
                };

                if ($image) {
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $maxDim = 512;

                    if ($width > $maxDim || $height > $maxDim) {
                        $ratio = min($maxDim / $width, $maxDim / $height);
                        $newWidth = (int) ($width * $ratio);
                        $newHeight = (int) ($height * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);

                        // Preserve transparency for PNG
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);

                        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        ob_start();
                        imagepng($resized, null, 6);
                        $imageData = ob_get_clean();

                        imagedestroy($image);
                        imagedestroy($resized);

                        return 'data:image/png;base64,' . base64_encode($imageData);
                    }

                    imagedestroy($image);
                }
            }

            // Fallback: use original image as-is
            $imageData = file_get_contents($imagePath);
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error("Image resize error for {$imagePath}: " . $e->getMessage());
            return null;
        }
    }
}

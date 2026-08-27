<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JinaEmbeddingService
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;

    protected string $clipModel;

    public function __construct()
    {
        $this->apiKey = (string) config('services.jina.api_key', '');
        $this->model = (string) config('services.jina.model', 'jina-embeddings-v3');
        $this->clipModel = (string) config('services.jina.clip_model', 'jina-clip-v2');
        $this->apiUrl = (string) config('services.jina.api_url', 'https://api.jina.ai/v1/embeddings');
    }

    /**
     * Generate embeddings for multiple inputs (text or image objects) in a single API call.
     *
     * @param array $inputs Array of strings or objects like ['image' => 'base64']
     * @param string|null $overrideModel Use a specific model
     * @param string|null $task Task type (text-matching, etc.)
     * @return array Array of float arrays (embeddings)
     */
    public function generateEmbeddings(array $inputs, ?string $overrideModel = null, ?string $task = 'text-matching'): array
    {
        if (empty($this->apiKey) || empty($inputs)) {
            return [];
        }

        $modelToUse = $overrideModel ?: $this->model;

        try {
            $payload = [
                'model' => $modelToUse,
                'input' => $inputs,
            ];

            if ($task && $modelToUse === $this->model) {
                $payload['task'] = $task;
                $payload['dimensions'] = 512;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post($this->apiUrl, $payload);

            if ($response->failed()) {
                Log::error('Jina Embedding API Error', [
                    'status' => $response->status(),
                    'error_message' => \Illuminate\Support\Str::limit($response->json('detail', 'Unknown error'), 200),
                ]);
                return [];
            }

            $data = $response->json('data', []);

            // Sort by index to ensure correct ordering
            usort($data, fn($a, $b) => $a['index'] <=> $b['index']);

            return array_map(fn($item) => $item['embedding'], $data);
        } catch (\Exception $e) {
            Log::error('Jina Embedding Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate a single embedding for one text (for search queries).
     */
    public function generateSingleEmbedding(string $text, ?string $task = 'retrieval.query'): array
    {
        $normalized = trim($text);
        if ($normalized === '') {
            return [];
        }

        $cacheKey = 'jina_query_emb:v1:' . md5($normalized . ':' . ($task ?? 'query'));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(7), function () use ($normalized, $task) {
            $results = $this->generateEmbeddings([$normalized], null, $task);
            return $results[0] ?? [];
        });
    }

    /**
     * Generate embeddings for product/passage text (for indexing).
     */
    public function generatePassageEmbeddings(array $texts): array
    {
        return $this->generateEmbeddings($texts, null, 'retrieval.passage');
    }

    /**
     * Generate embedding for an image (URL or base64)
     */
    public function generateImageEmbedding(string $imageSource): array
    {
        $processedImage = $this->preprocessImageSource($imageSource);
        // For Jina CLIP, input should be an array of objects
        $results = $this->generateEmbeddings([['image' => $processedImage]], $this->clipModel, null);
        return $results[0] ?? [];
    }

    /**
     * Preprocess and resize image source to max 512px to match embedding indexing pipeline.
     */
    private function preprocessImageSource(string $imageSource, int $maxDim = 512): string
    {
        if (str_starts_with($imageSource, 'http://') || str_starts_with($imageSource, 'https://')) {
            return $imageSource;
        }

        if (!str_starts_with($imageSource, 'data:image')) {
            return $imageSource;
        }

        if (!extension_loaded('gd')) {
            return $imageSource;
        }

        try {
            $commaPos = strpos($imageSource, ',');
            if ($commaPos === false) {
                return $imageSource;
            }

            $rawBase64 = substr($imageSource, $commaPos + 1);
            $decoded = base64_decode($rawBase64);
            if (!$decoded) {
                return $imageSource;
            }

            $srcImg = @imagecreatefromstring($decoded);
            if (!$srcImg) {
                return $imageSource;
            }

            $width = imagesx($srcImg);
            $height = imagesy($srcImg);

            if ($width <= $maxDim && $height <= $maxDim) {
                imagedestroy($srcImg);
                return $imageSource;
            }

            $ratio = min($maxDim / $width, $maxDim / $height);
            $newWidth = max(1, (int) ($width * $ratio));
            $newHeight = max(1, (int) ($height * $ratio));

            $dstImg = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagepng($dstImg, null, 6);
            $resizedData = ob_get_clean();

            imagedestroy($srcImg);
            imagedestroy($dstImg);

            return 'data:image/png;base64,' . base64_encode($resizedData);
        } catch (\Exception $e) {
            Log::warning('Image preprocess failed, falling back to original: ' . $e->getMessage());
            return $imageSource;
        }
    }
}

<?php

namespace App\Http\Services;

use App\Models\ProductEmbedding;
use Illuminate\Support\Facades\Log;

class SemanticSearchService
{
    protected JinaEmbeddingService $jinaService;

    public function __construct(JinaEmbeddingService $jinaService)
    {
        $this->jinaService = $jinaService;
    }

    /**
     * Perform semantic search: embed the query, compute cosine similarity with all products.
     *
     * @param string $query User search query
     * @param int $limit Max number of results
     * @return array Array of Product models sorted by similarity (descending)
     */
    public function search(string $query, int $limit = 10): array
    {
        $queryVector = $this->jinaService->generateSingleEmbedding($query);

        if (empty($queryVector)) {
            return [];
        }

        $productEmbeddings = ProductEmbedding::with(['product' => function ($q) {
            $q->where('status', 'approved')
              ->has('variants')
              ->with(['industrialCategory', 'variants.inventoryProducts', 'media']);
        }])->get();

        $scored = [];

        foreach ($productEmbeddings as $pe) {
            if (!$pe->product) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $pe->vector);

            if ($similarity >= 0.20) {
                $scored[] = [
                    'product' => $pe->product,
                    'score' => $similarity,
                ];
            }
        }

        // Sort descending by similarity score
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // Take top $limit results, return just the Product models
        return array_map(
            fn($item) => $item['product'],
            array_slice($scored, 0, $limit)
        );
    }

    /**
     * Perform visual search: embed the input image, compute similarity with all product images.
     * 
     * @param string $imageSource Image (base64 or URL)
     * @param int $limit
     * @return array
     */
    public function searchByImage(string $imageSource, int $limit = 10): array
    {
        $queryVector = $this->jinaService->generateImageEmbedding($imageSource);

        if (empty($queryVector)) {
            return [];
        }

        $imageEmbeddings = \App\Models\ProductImageEmbedding::with(['product' => function ($q) {
            $q->where('status', 'approved')
              ->has('variants')
              ->with(['industrialCategory', 'variants.inventoryProducts', 'media']);
        }])->get();

        $scored = [];

        foreach ($imageEmbeddings as $ie) {
            if (!$ie->product) {
                continue;
            }

            $currentVector = $ie->vector;
            if (empty($currentVector)) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $currentVector);

            // Images usually need a slightly higher or different threshold
            if ($similarity >= 0.25) { 
                $scored[] = [
                    'product' => $ie->product,
                    'score' => $similarity,
                ];
            }
        }

        // Sort descending by similarity score
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(
            fn($item) => $item['product'],
            array_slice($scored, 0, $limit)
        );
    }

    /**
     * Compute cosine similarity between two vectors.
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        $len = min(count($vecA), count($vecB));

        for ($i = 0; $i < $len; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $magnitudeA += $vecA[$i] * $vecA[$i];
            $magnitudeB += $vecB[$i] * $vecB[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}

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

        $threshold = mb_strlen(trim($query)) <= 6 ? 0.22 : 0.30;

        foreach ($productEmbeddings as $pe) {
            if (!$pe->product) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $pe->vector);

            if ($similarity >= $threshold) {
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
     * Perform visual search using multimodal vision-language fusion:
     * Computes similarity with both product image embeddings and product CLIP text representations,
     * applying dynamic relative thresholding and category coherence gating.
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

            $imgVec = $ie->vector;
            if (empty($imgVec)) {
                continue;
            }

            $imgSim = $this->cosineSimilarity($queryVector, $imgVec);
            $textVec = $ie->text_vector;
            $textSim = !empty($textVec) ? $this->cosineSimilarity($queryVector, $textVec) : 0.0;

            // Multimodal Vision-Language Fusion:
            // Normalize CLIP text similarity (~0.12 - 0.40 -> scaled by 2.3) and fuse with visual similarity
            $fusedScore = ($textSim > 0.0) 
                ? ($imgSim * 0.55 + ($textSim * 2.3) * 0.45) 
                : $imgSim;

            if ($fusedScore >= 0.58) { 
                $scored[] = [
                    'product' => $ie->product,
                    'category_id' => $ie->product->industry_category_id,
                    'score' => $fusedScore,
                    'img_sim' => $imgSim,
                    'text_sim' => $textSim,
                ];
            }
        }

        if (empty($scored)) {
            return [];
        }

        // Sort descending by multimodal similarity score
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        $topScore = $scored[0]['score'];

        // If top match is too low, no confident visual match found
        if ($topScore < 0.65) {
            return [];
        }

        // Dynamic relative floor: results must be within tight relative distance of top match
        $relativeMinScore = max(0.66, $topScore * 0.80, $topScore - 0.14);

        // Identify dominant category IDs from top high-confidence candidates
        $topCandidates = array_slice($scored, 0, min(3, count($scored)));
        $dominantCategories = collect($topCandidates)
            ->where('score', '>=', $topScore - 0.10)
            ->pluck('category_id')
            ->filter()
            ->unique()
            ->toArray();

        $filtered = [];
        $prevScore = $topScore;

        foreach ($scored as $index => $item) {
            $score = $item['score'];
            $isSameCategory = in_array($item['category_id'], $dominantCategories);

            // Discard items falling below the dynamic relative floor
            if ($score < $relativeMinScore) {
                break;
            }

            // Cliff drop-off detection: stop if there is an abrupt quality gap (> 0.06) after at least 3 items
            if ($index >= 3 && ($prevScore - $score) > 0.06) {
                break;
            }

            // Category gating: products from completely different categories must have near-top similarity
            if (!empty($dominantCategories) && !$isSameCategory) {
                if ($score < ($topScore - 0.04)) {
                    continue;
                }
            }

            $filtered[] = $item['product'];
            $prevScore = $score;

            if (count($filtered) >= $limit) {
                break;
            }
        }

        return $filtered;
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

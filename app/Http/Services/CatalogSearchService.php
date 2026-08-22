<?php

namespace App\Http\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CatalogSearchService
{
    private const ENGLISH_STOP_WORDS = [
        'a', 'an', 'and', 'are', 'can', 'do', 'find', 'for', 'from', 'get', 'give',
        'have', 'how', 'i', 'in', 'is', 'it', 'me', 'my', 'need', 'of', 'on', 'or',
        'our', 'please', 'product', 'products', 'show', 'something', 'the', 'to',
        'want', 'what', 'which', 'with', 'you', 'your',
    ];

    private const ARABIC_STOP_WORDS = [
        'ا', 'او', 'اي', 'ايه', 'ازاي', 'ال', 'التي', 'اللي', 'الى', 'ان', 'انا',
        'انت', 'انتم', 'اه', 'ابغى', 'ابي', 'ابيها', 'بدي', 'بس', 'بعد', 'بين',
        'تكون', 'دا', 'ده', 'دي', 'ذلك', 'عايز', 'عاوزه', 'عاوز', 'عن', 'في',
        'فين', 'كيف', 'كم', 'ل', 'لا', 'لو', 'ما', 'ماذا', 'ممكن', 'من', 'منتج',
        'منتجات', 'هل', 'هو', 'هي', 'و', 'وايه', 'يكون', 'ينفع', 'يستخدم', 'يستعمل',
        'مناسب', 'للطلب', 'بالجمله', 'بالجملة', 'اقدر', 'اطلبها', 'حاجه', 'حاجة',
        'شي', 'شيء', 'احتاج', 'محتاج',
    ];

    public function __construct(
        protected SemanticSearchService $semanticSearchService,
        protected ProductSearchDocumentBuilder $productSearchDocumentBuilder,
        protected SearchQueryUnderstandingService $searchQueryUnderstandingService
    ) {
    }

    /**
     * @return array{literal: Collection<int, Product>, semantic: Collection<int, Product>, combined: Collection<int, Product>}
     */
    public function search(string $query, int $literalLimit = 7, int $semanticLimit = 8): array
    {
        $context = $this->buildBaseSearchContext($query);

        if ($this->searchQueryUnderstandingService->shouldAnalyze($query)) {
            $context = $this->mergeAiContext(
                $context,
                $this->searchQueryUnderstandingService->analyze($query)
            );
        }

        $literal = $this->searchLiteralCandidates($context, $literalLimit);
        $semantic = $this->searchSemanticCandidates($context, $literal, $semanticLimit);

        return [
            'literal' => $literal->values(),
            'semantic' => $semantic->values(),
            'combined' => $literal->concat($semantic)->values(),
        ];
    }

    /**
     * @param array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * } $context
     * @param Collection<int, Product> $literal
     * @return Collection<int, Product>
     */
    private function searchSemanticCandidates(array $context, Collection $literal, int $limit): Collection
    {
        $semantic = collect();
        $seenIds = $literal->pluck('id')->all();
        $semanticFetchLimit = max($limit * 2, 8);

        foreach ($context['semantic_queries'] as $candidate) {
            try {
                $products = collect($this->semanticSearchService->search($candidate, $semanticFetchLimit));
            } catch (\Throwable) {
                $products = collect();
            }

            foreach ($products as $product) {
                if (in_array($product->id, $seenIds, true)) {
                    continue;
                }

                $semantic->push($product);
                $seenIds[] = $product->id;
            }

            if ($semantic->count() >= $semanticFetchLimit) {
                break;
            }
        }

        return $this->rankProducts($semantic, $context, true)
            ->take($limit)
            ->values();
    }

    /**
     * @param array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * } $context
     * @return Collection<int, Product>
     */
    private function searchLiteralCandidates(array $context, int $limit): Collection
    {
        $results = collect();
        $fetchLimit = max($limit * 4, 24);

        foreach ($context['candidate_queries'] as $candidate) {
            $keyword = $this->normalizeText($candidate);

            if ($keyword === '') {
                continue;
            }

            $contains = '%' . $keyword . '%';
            $productNameEn = $this->jsonTextExpression('products.name', 'en');
            $productNameAr = $this->jsonTextExpression('products.name', 'ar');
            $productDescriptionEn = $this->jsonTextExpression('products.description', 'en');
            $productDescriptionAr = $this->jsonTextExpression('products.description', 'ar');
            $categoryNameEn = $this->jsonTextExpression('industry_categories.name', 'en');
            $categoryNameAr = $this->jsonTextExpression('industry_categories.name', 'ar');

            $models = Product::query()
                ->select('products.*')
                ->leftJoin('industry_categories', 'industry_categories.id', '=', 'products.industry_category_id')
                ->where('products.status', 'approved')
                ->has('variants')
                ->where(function ($query) use ($contains, $productNameEn, $productNameAr, $productDescriptionEn, $productDescriptionAr, $categoryNameEn, $categoryNameAr) {
                    $query->whereRaw("LOWER({$productNameEn}) LIKE ?", [$contains])
                        ->orWhereRaw("LOWER({$productNameAr}) LIKE ?", [$contains])
                        ->orWhereRaw("LOWER({$productDescriptionEn}) LIKE ?", [$contains])
                        ->orWhereRaw("LOWER({$productDescriptionAr}) LIKE ?", [$contains])
                        ->orWhereRaw('LOWER(products.slug) LIKE ?', [$contains])
                        ->orWhereRaw("LOWER({$categoryNameEn}) LIKE ?", [$contains])
                        ->orWhereRaw("LOWER({$categoryNameAr}) LIKE ?", [$contains]);
                })
                ->with(['variants.inventoryProducts', 'industrialCategory', 'media'])
                ->distinct()
                ->take($fetchLimit)
                ->get();

            $results = $results->concat($models);
        }

        return $this->rankProducts($results, $context, false)
            ->take($limit)
            ->values();
    }

    /**
     * @param Collection<int, Product> $products
     * @param array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * } $context
     * @return Collection<int, Product>
     */
    private function rankProducts(Collection $products, array $context, bool $semantic): Collection
    {
        return $products
            ->unique('id')
            ->map(function (Product $product) use ($context, $semantic) {
                return [
                    'product' => $product,
                    'score' => $this->scoreProductMatch($product, $context, $semantic),
                ];
            })
            ->sortByDesc('score')
            ->pluck('product')
            ->values();
    }

    /**
     * @param array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * } $context
     */
    private function scoreProductMatch(Product $product, array $context, bool $semantic): int
    {
        $nameText = $this->normalizeText(implode(' ', array_filter([
            $product->getTranslation('name', 'en', false),
            $product->getTranslation('name', 'ar', false),
        ])));
        $descriptionText = $this->normalizeText(implode(' ', array_filter([
            $product->getTranslation('description', 'en', false),
            $product->getTranslation('description', 'ar', false),
        ])));
        $categoryText = $this->normalizeText(implode(' ', array_filter([
            optional($product->industrialCategory)->getTranslation('name', 'en', false),
            optional($product->industrialCategory)->getTranslation('name', 'ar', false),
            optional($product->industrialCategory)->slug,
        ])));
        $slugText = $this->normalizeText($product->slug);
        $searchDocument = $this->normalizeText($this->productSearchDocumentBuilder->build($product));

        $score = $semantic ? 30 : 100;

        foreach ($context['candidate_queries'] as $index => $candidate) {
            $weight = max(46 - ($index * 4), 12);

            if ($candidate !== '' && str_contains($nameText, $candidate)) {
                $score += $weight + 24;
            }

            if ($candidate !== '' && str_contains($categoryText, $candidate)) {
                $score += $weight + 18;
            }

            if ($candidate !== '' && str_contains($slugText, $candidate)) {
                $score += $weight + 12;
            }

            if ($candidate !== '' && str_contains($descriptionText, $candidate)) {
                $score += $weight + 8;
            }

            if ($candidate !== '' && str_contains($searchDocument, $candidate)) {
                $score += $weight + 10;
            }
        }

        foreach ($context['keywords'] as $keyword) {
            if (str_contains($nameText, $keyword)) {
                $score += 12;
            }

            if (str_contains($categoryText, $keyword)) {
                $score += 10;
            }

            if (str_contains($descriptionText, $keyword)) {
                $score += 4;
            }

            if (str_contains($searchDocument, $keyword)) {
                $score += 5;
            }
        }

        foreach ($context['negative_keywords'] as $keyword) {
            if (str_contains($nameText, $keyword)) {
                $score -= 18;
            }

            if (str_contains($categoryText, $keyword)) {
                $score -= 16;
            }

            if (str_contains($descriptionText, $keyword)) {
                $score -= 8;
            }

            if (str_contains($searchDocument, $keyword)) {
                $score -= 10;
            }
        }

        return $score;
    }

    /**
     * @return array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * }
     */
    private function buildBaseSearchContext(string $query): array
    {
        $normalized = $this->normalizeText($query);
        $simplified = $this->simplifyQuery($normalized);

        $candidateQueries = collect([$normalized, $simplified])
            ->merge($this->extractQueryFragments($simplified))
            ->filter(fn (string $candidate) => $candidate !== '' && mb_strlen($candidate) >= 1)
            ->unique()
            ->take(10)
            ->values()
            ->all();

        $keywords = collect($candidateQueries)
            ->flatMap(function (string $candidate) {
                return preg_split('/\s+/u', $candidate, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            })
            ->filter(fn (string $keyword) => mb_strlen($keyword) >= 1)
            ->unique()
            ->values()
            ->all();

        return [
            'candidate_queries' => $candidateQueries,
            'semantic_queries' => collect([$normalized, $simplified])
                ->filter(fn (string $candidate) => $candidate !== '' && mb_strlen($candidate) >= 1)
                ->unique()
                ->values()
                ->all(),
            'keywords' => $keywords,
            'negative_keywords' => [],
        ];
    }

    /**
     * @param array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * } $context
     * @param array{
     *   search_phrases: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>,
     *   language: string,
     *   is_intent_query: bool
     * } $analysis
     * @return array{
     *   candidate_queries: array<int, string>,
     *   semantic_queries: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>
     * }
     */
    private function mergeAiContext(array $context, array $analysis): array
    {
        $searchPhrases = collect($analysis['search_phrases'] ?? [])
            ->map(fn (string $phrase) => $this->normalizeText($phrase))
            ->filter();
        $aiKeywords = collect($analysis['keywords'] ?? [])
            ->map(fn (string $keyword) => $this->normalizeText($keyword))
            ->filter(fn (string $keyword) => $keyword !== '' && mb_strlen($keyword) >= 1);

        return [
            'candidate_queries' => collect($context['candidate_queries'])
                ->merge($searchPhrases)
                ->merge($aiKeywords)
                ->unique()
                ->take(10)
                ->values()
                ->all(),
            'semantic_queries' => collect($context['semantic_queries'])
                ->merge($searchPhrases)
                ->unique()
                ->take(6)
                ->values()
                ->all(),
            'keywords' => collect($context['keywords'])
                ->merge($aiKeywords)
                ->unique()
                ->take(10)
                ->values()
                ->all(),
            'negative_keywords' => collect($analysis['negative_keywords'] ?? [])
                ->map(fn (string $keyword) => $this->normalizeText($keyword))
                ->filter(fn (string $keyword) => $keyword !== '' && mb_strlen($keyword) >= 1)
                ->unique()
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractQueryFragments(string $query): array
    {
        $words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($words)
            ->filter(fn (string $word) => mb_strlen($word) >= 1)
            ->reject(fn (string $word) => in_array($word, self::ENGLISH_STOP_WORDS, true))
            ->reject(fn (string $word) => in_array($word, self::ARABIC_STOP_WORDS, true))
            ->values()
            ->all();
    }

    private function simplifyQuery(string $query): string
    {
        return trim(implode(' ', $this->extractQueryFragments($query)));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text) ?? $text;
        $text = str_replace(
            ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ؤ', 'ئ', 'ة', 'ـ'],
            ['ا', 'ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه', ''],
            $text
        );
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function jsonTextExpression(string $column, string $locale): string
    {
        $path = '$.' . $locale;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "COALESCE(json_extract({$column}, '{$path}'), '')";
        }

        return "COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}')), '')";
    }
}

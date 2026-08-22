<?php

namespace App\Http\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class FuzzyProductMatcher
{
    private const ENGLISH_STOP_WORDS = [
        'a', 'an', 'and', 'are', 'can', 'do', 'for', 'from', 'get', 'have', 'i',
        'in', 'is', 'it', 'me', 'my', 'of', 'on', 'or', 'our', 'please', 'product',
        'products', 'sell', 'show', 'that', 'the', 'there', 'to', 'what', 'which',
        'with', 'would', 'you', 'your',
    ];

    private const ARABIC_STOP_WORDS = [
        'ا', 'او', 'اي', 'اية', 'ازاي', 'ال', 'التي', 'اللى', 'اللي', 'الى', 'ان',
        'انا', 'انت', 'انتم', 'اه', 'او', 'ايه', 'بدي', 'بس', 'بعد', 'بدي', 'بي',
        'بين', 'تكون', 'ده', 'دي', 'ذلك', 'ده', 'دي', 'عايز', 'عاوزه', 'عندي',
        'عن', 'في', 'فين', 'كيف', 'كم', 'كنت', 'كويس', 'ل', 'لا', 'لو', 'ما',
        'ماذا', 'ممكن', 'من', 'منتج', 'منتجات', 'مين', 'نفس', 'هل', 'هو', 'هي',
        'و', 'يا', 'يعني', 'يمكن', 'يوجد',
    ];

    /**
     * Find products matching a query using fuzzy name matching.
     * Uses similar_text() + substring matching on product names (en/ar).
     *
     * @param string $query User search query
     * @param int $limit Max results
     * @return Collection Collection of ['product' => Product, 'score' => float]
     */
    public function match(string $query, int $limit = 10): Collection
    {
        $query = $this->normalizeText($query);

        if (mb_strlen(str_replace(' ', '', $query)) < 2) {
            return collect();
        }

        $searchTerms = $this->extractSearchTerms($query);

        if (empty($searchTerms)) {
            $searchTerms = [$query];
        }

        $products = Product::where('status', 'approved')
            ->has('variants')
            ->with(['variants.inventoryProducts', 'industrialCategory'])
            ->get();

        $scored = collect();

        foreach ($products as $product) {
            $bestScore = 0;

            foreach ($this->getSearchableFields($product) as $field) {
                if ($field === '') {
                    continue;
                }

                $bestScore = max($bestScore, $this->scoreTermAgainstField($query, $field, true));

                foreach ($searchTerms as $term) {
                    $bestScore = max($bestScore, $this->scoreTermAgainstField($term, $field));
                }
            }

            if ($bestScore > 0.35) {
                $scored->push([
                    'product' => $product,
                    'score' => round($bestScore, 4),
                ]);
            }
        }

        return $scored
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function getSearchableFields(Product $product): array
    {
        return array_values(array_filter([
            $this->normalizeText($product->getTranslation('name', 'en') ?? ''),
            $this->normalizeText($product->getTranslation('name', 'ar') ?? ''),
            $this->normalizeText($product->getTranslation('description', 'en') ?? ''),
            $this->normalizeText($product->getTranslation('description', 'ar') ?? ''),
            $this->normalizeText($product->industrialCategory?->getTranslation('name', 'en') ?? ''),
            $this->normalizeText($product->industrialCategory?->getTranslation('name', 'ar') ?? ''),
            $this->normalizeText((string) $product->slug),
        ]));
    }

    private function extractSearchTerms(string $query): array
    {
        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_filter($terms, function (string $term) {
            if (mb_strlen($term) < 2) {
                return false;
            }

            return !in_array($term, self::ENGLISH_STOP_WORDS, true)
                && !in_array($term, self::ARABIC_STOP_WORDS, true);
        });

        return array_values(array_unique($this->expandTerms($query, $terms)));
    }

    private function expandTerms(string $query, array $terms): array
    {
        $expanded = $terms;

        $hasDrinkIntent = str_contains($query, 'tea')
            || str_contains($query, 'drink')
            || str_contains($query, 'beverage')
            || str_contains($query, 'شاي')
            || str_contains($query, 'مشروب')
            || str_contains($query, 'مشروبات')
            || str_contains($query, 'شرب');

        if ($hasDrinkIntent) {
            $expanded = array_merge($expanded, ['tea', 'drinks', 'beverages', 'شاي', 'مشروبات']);
        }

        return $expanded;
    }

    private function scoreTermAgainstField(string $term, string $field, bool $isFullQuery = false): float
    {
        if ($term === '' || $field === '') {
            return 0;
        }

        if (mb_strpos($field, $term) !== false) {
            $coverage = min(1, mb_strlen($term) / max(mb_strlen($field), 1));
            return $isFullQuery ? 0.88 + ($coverage * 0.07) : 0.72 + ($coverage * 0.18);
        }

        if (mb_strlen($field) > 3 && mb_strpos($term, $field) !== false) {
            return $isFullQuery ? 0.74 : 0.62;
        }

        $fieldWords = preg_split('/\s+/u', $field, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $bestWordSimilarity = 0.0;

        foreach ($fieldWords as $fieldWord) {
            similar_text($term, $fieldWord, $percent);
            $bestWordSimilarity = max($bestWordSimilarity, $percent / 100);
        }

        if ($bestWordSimilarity >= 0.8) {
            return 0.45 + ($bestWordSimilarity * 0.25);
        }

        if (!$isFullQuery) {
            similar_text($term, $field, $percent);
            $normalizedSimilarity = $percent / 100;

            if ($normalizedSimilarity >= 0.55) {
                return $normalizedSimilarity * 0.55;
            }
        }

        return 0;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $text = str_replace(
            ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ؤ', 'ئ', 'ة'],
            ['ا', 'ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه'],
            $text
        );

        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}

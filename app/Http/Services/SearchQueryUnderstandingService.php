<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;

class SearchQueryUnderstandingService
{
    public function __construct(
        protected GroqService $groqService
    ) {
    }

    /**
     * @return array{
     *   search_phrases: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>,
     *   language: string,
     *   is_intent_query: bool
     * }
     */
    public function analyze(string $query): array
    {
        $normalized = $this->normalizeText($query);

        if ($normalized === '' || !$this->shouldAnalyze($query)) {
            return $this->emptyAnalysis();
        }

        return Cache::remember(
            'search-query-understanding:v1:' . sha1($normalized),
            now()->addHours(12),
            fn () => $this->requestAnalysis($query, $normalized)
        );
    }

    public function shouldAnalyze(string $query): bool
    {
        $normalized = $this->normalizeText($query);

        if ($normalized === '' || mb_strlen($normalized) < 4) {
            return false;
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($words) >= 2;
    }

    /**
     * @return array{
     *   search_phrases: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>,
     *   language: string,
     *   is_intent_query: bool
     * }
     */
    private function requestAnalysis(string $originalQuery, string $normalizedQuery): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => <<<'PROMPT'
You convert multilingual e-commerce queries into retrieval hints for product search.

Return JSON only with this schema:
{
  "language": "ar|en|mixed",
  "is_intent_query": true,
  "search_phrases": ["short phrase", "..."],
  "keywords": ["core term", "..."],
  "negative_keywords": ["term to deprioritize", "..."]
}

Rules:
- "search_phrases" should be 2 to 6 short product-search phrases.
- Include both the user's language and English when useful for retrieval.
- Infer the likely product type or use-case from the query.
- Keep phrases concrete and searchable, not long explanations.
- Use "negative_keywords" only for terms that likely represent the wrong type of result.
- Do not mention brands unless the query implies one.
- Never include text outside the JSON object.

Examples:
Query: "منتج يستخدم للقلي"
Return:
{"language":"ar","is_intent_query":true,"search_phrases":["زيت للقلي","زيت طبخ","frying oil","cooking oil"],"keywords":["زيت","قلي"],"negative_keywords":["مقلي","وجبة مقلية"]}

Query: "عايز منتج للتنظيف"
Return:
{"language":"ar","is_intent_query":true,"search_phrases":["منتج تنظيف","مواد تنظيف","cleaning product","cleaner"],"keywords":["تنظيف"],"negative_keywords":[]}

Query: "Roots corn oil"
Return:
{"language":"en","is_intent_query":false,"search_phrases":["roots corn oil"],"keywords":["roots","corn oil"],"negative_keywords":[]}
PROMPT,
            ],
            [
                'role' => 'user',
                'content' => "Query: {$originalQuery}\nNormalized query: {$normalizedQuery}",
            ],
        ];

        $response = $this->groqService->chat($messages, [
            'temperature' => 0.1,
            'max_tokens' => 220,
        ]);

        return $this->sanitizeAnalysis(
            $this->decodeAnalysis($response['content'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAnalysis(string $content): array
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $analysis
     * @return array{
     *   search_phrases: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>,
     *   language: string,
     *   is_intent_query: bool
     * }
     */
    private function sanitizeAnalysis(array $analysis): array
    {
        $empty = $this->emptyAnalysis();

        $searchPhrases = $this->sanitizeStringList($analysis['search_phrases'] ?? [])
            ->take(6)
            ->values()
            ->all();

        $keywords = $this->sanitizeStringList($analysis['keywords'] ?? [])
            ->take(8)
            ->values()
            ->all();

        $negativeKeywords = $this->sanitizeStringList($analysis['negative_keywords'] ?? [])
            ->take(6)
            ->values()
            ->all();

        return [
            'search_phrases' => $searchPhrases,
            'keywords' => $keywords,
            'negative_keywords' => $negativeKeywords,
            'language' => in_array(($analysis['language'] ?? ''), ['ar', 'en', 'mixed'], true)
                ? $analysis['language']
                : $empty['language'],
            'is_intent_query' => (bool) ($analysis['is_intent_query'] ?? (!empty($searchPhrases) && count($searchPhrases) > 1)),
        ];
    }

    /**
     * @param mixed $items
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function sanitizeStringList(mixed $items)
    {
        return collect(is_array($items) ? $items : [])
            ->filter(fn ($item) => is_string($item))
            ->map(fn (string $item) => $this->normalizeText($item))
            ->filter(fn (string $item) => $item !== '' && mb_strlen($item) >= 2)
            ->unique()
            ->values();
    }

    /**
     * @return array{
     *   search_phrases: array<int, string>,
     *   keywords: array<int, string>,
     *   negative_keywords: array<int, string>,
     *   language: string,
     *   is_intent_query: bool
     * }
     */
    private function emptyAnalysis(): array
    {
        return [
            'search_phrases' => [],
            'keywords' => [],
            'negative_keywords' => [],
            'language' => 'mixed',
            'is_intent_query' => false,
        ];
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
}

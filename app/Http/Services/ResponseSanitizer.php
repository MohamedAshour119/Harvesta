<?php

namespace App\Http\Services;

class ResponseSanitizer
{
    /**
     * Patterns that indicate the LLM leaked system prompt or internal data.
     * If matched, the response is replaced with a safe fallback.
     */
    protected array $leakPatterns = [
        // System prompt leakage
        '/CRITICAL RULES|RULE \d+|NON-NEGOTIABLE/i',
        '/AVAILABLE PRODUCTS \(This is the COMPLETE list/i',
        '/CURRENTLY VIEWING PRODUCT/i',
        '/SHIPPING INFORMATION:/i',
        '/BUSINESS POLICIES:/i',
        '/SECURITY \(NON-NEGOTIABLE\)/i',
        '/<<CUSTOMER_QUERY>>|<<END_CUSTOMER_QUERY>>/i',
        '/\[EXACT NAME:/i',
        '/Cairo3A Assistant.*CRITICAL/is',
        '/You are "Cairo3A Assistant"/i',
        '/Harvesta Assistant.*CRITICAL/is',
        '/You are "Harvesta Assistant"/i',
        '/system\s*prompt/i',
        '/RULE \d+ - (PRODUCT ACCURACY|WHEN NO PRODUCTS|RESPONSE FORMAT)/i',

        // Internal data patterns - database info, SQL, internal IDs
        '/SELECT\s+.+\s+FROM\s+/i',
        '/INSERT\s+INTO\s+/i',
        '/UPDATE\s+.+\s+SET\s+/i',
        '/DELETE\s+FROM\s+/i',
        '/\b(user_id|session_id|conversation_id|ip_address|ua_hash)\s*[:=]/i',

        // API keys or tokens
        '/\b(api[_-]?key|secret[_-]?key|access[_-]?token|bearer)\s*[:=]\s*\S+/i',
        '/\b(sk-|pk-|gsk_|xai-)[a-zA-Z0-9]{10,}/i',

        // Internal endpoints or paths
        '/\/api\/(chatbot|auth)\/(session|send|history)/i',
        '/groq\.com\/openai/i',
        '/jina\.ai/i',

        // Email addresses of staff/system
        '/[\w.-]+@(company|internal|admin|devno)\.\w+/i',
    ];

    /**
     * Sensitive words that should never appear in customer-facing responses.
     */
    protected array $sensitiveKeywords = [
        'system prompt',
        'system instructions',
        'my instructions',
        'my rules',
        'my programming',
        'I was instructed',
        'I was programmed',
        'my configuration',
        'database query',
        'SQL query',
        'API key',
        'secret key',
        'access token',
        'internal system',
    ];

    /**
     * Sanitize the LLM response before returning to the user.
     *
     * @return array{content: string, was_filtered: bool, matched_reason: string|null}
     */
    public function sanitize(string $content): array
    {
        // 1. Check for leak patterns (regex)
        foreach ($this->leakPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return [
                    'content' => $this->getFallbackResponse(),
                    'was_filtered' => true,
                    'matched_reason' => 'leak_pattern: ' . $pattern,
                ];
            }
        }

        // 2. Check for sensitive keywords (case-insensitive)
        $contentLower = mb_strtolower($content);
        foreach ($this->sensitiveKeywords as $keyword) {
            if (str_contains($contentLower, mb_strtolower($keyword))) {
                return [
                    'content' => $this->getFallbackResponse(),
                    'was_filtered' => true,
                    'matched_reason' => 'sensitive_keyword: ' . $keyword,
                ];
            }
        }

        // 3. Strip any HTML that the LLM might have generated
        $content = strip_tags($content);

        // 4. Remove any remaining delimiter tags
        $content = preg_replace('/<<\/?[A-Z_]+>>/', '', $content) ?? $content;

        // 5. Remove invisible direction/control characters that can corrupt Arabic rendering
        $content = preg_replace(
            '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}\x{FFFD}]/u',
            '',
            $content
        ) ?? $content;
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content) ?? $content;

        // 6. Repair common mojibake sequences such as "Ø§Ù" before returning the response
        $content = $this->repairEncodingArtifacts($content);
        $content = $this->removeDisplayArtifacts($content);

        return [
            'content' => trim($content),
            'was_filtered' => false,
            'matched_reason' => null,
        ];
    }

    /**
     * Safe fallback response when filtering is triggered.
     */
    protected function getFallbackResponse(): string
    {
        $responses = [
            'I can help you with our products and ordering. What would you like to know?',
            'How can I assist you with our catalog today?',
            'I\'m here to help with product inquiries and orders. What are you looking for?',
        ];

        return $responses[array_rand($responses)];
    }

    protected function repairEncodingArtifacts(string $content): string
    {
        if (!$this->looksLikeEncodingArtifact($content)) {
            return $content;
        }

        $repaired = @mb_convert_encoding($content, 'UTF-8', 'Windows-1252');

        if (!is_string($repaired) || $repaired === '') {
            return $content;
        }

        $originalNoise = $this->countEncodingNoise($content);
        $repairedNoise = $this->countEncodingNoise($repaired);

        if ($repairedNoise >= $originalNoise) {
            return $content;
        }

        if (!preg_match('/[\p{Arabic}\p{Latin}\p{N}]/u', $repaired)) {
            return $content;
        }

        return $repaired;
    }

    protected function looksLikeEncodingArtifact(string $content): bool
    {
        return preg_match('/[ØÙÃÂâï]/u', $content) === 1
            || str_contains($content, "\u{FFFD}");
    }

    protected function countEncodingNoise(string $content): int
    {
        preg_match_all('/[ØÙÃÂâï]/u', $content, $matches);

        return count($matches[0]) + substr_count($content, "\u{FFFD}");
    }

    protected function removeDisplayArtifacts(string $content): string
    {
        if (!preg_match('/\p{Arabic}/u', $content)) {
            return $content;
        }

        $cleaned = preg_replace('/[\x{0590}-\x{05FF}\x{2500}-\x{257F}\x{00A1}-\x{00FF}]+/u', '', $content);

        if (!is_string($cleaned) || $cleaned === '') {
            return $content;
        }

        $cleaned = preg_replace('/[^\S\r\n]{2,}/u', ' ', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}

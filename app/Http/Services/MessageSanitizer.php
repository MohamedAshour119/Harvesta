<?php

namespace App\Http\Services;

class MessageSanitizer
{
    /**
     * Sanitize a user message before processing.
     */
    public function sanitize(string $message): string
    {
        // Strip HTML/XML tags
        $message = strip_tags($message);

        // Remove zero-width and invisible unicode characters
        $message = preg_replace('/[\x{200B}-\x{200F}\x{2028}-\x{202F}\x{2060}-\x{206F}\x{FEFF}]/u', '', $message);

        // Remove control characters except newlines and tabs
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message);

        // Collapse excessive newlines (max 2 consecutive)
        $message = preg_replace('/\n{3,}/', "\n\n", $message);

        // Collapse excessive whitespace on a single line
        $message = preg_replace('/[^\S\n]{5,}/', '    ', $message);

        return trim($message);
    }

    /**
     * Validate that the message has meaningful content.
     */
    public function isValid(string $message): bool
    {
        // Must not be empty after sanitization
        if (empty(trim($message))) {
            return false;
        }

        // Must contain at least one alphanumeric character (any language)
        if (!preg_match('/[\p{L}\p{N}]/u', $message)) {
            return false;
        }

        // Reject excessive repeated characters (spam)
        if (preg_match('/(.)\1{20,}/u', $message)) {
            return false;
        }

        return true;
    }

    /**
     * Wrap a user message in delimiters to prevent role confusion in LLM.
     */
    public function wrapForLLM(string $message): string
    {
        return "<<CUSTOMER_QUERY>>\n{$message}\n<<END_CUSTOMER_QUERY>>";
    }
}

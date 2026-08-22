<?php

namespace App\Http\Services;

class ChatbotIntentDetector
{
    /**
     * Detect the intent of a user message using keyword/pattern matching.
     *
     * @param string $message User's message
     * @return string Intent: greeting, product_inquiry, purchase_intent, order_status, shipping_inquiry, comparison, general
     */
    public function detect(string $message): string
    {
        $lower = mb_strtolower(trim($message));

        // Greeting patterns (EN + AR)
        if ($this->matchesAny($lower, [
            '/^(hi|hello|hey|good\s*(morning|afternoon|evening)|howdy|what\'?s up)/i',
            '/^(مرحبا|أهلا|السلام عليكم|صباح الخير|مساء الخير|هلا|اهلا)/u',
            '/^(شكر|thanks|thank you|متشكر)/u',
        ])) {
            return 'greeting';
        }

        // Order status patterns
        if ($this->matchesAny($lower, [
            '/\b(order\s*(status|track|where|number)|my\s*order|track\s*(my|order|package))\b/i',
            '/\b(حالة\s*الطلب|تتبع\s*الطلب|طلبي|أين\s*طلبي|رقم\s*الطلب)\b/u',
        ])) {
            return 'order_status';
        }

        // Shipping inquiry patterns
        if ($this->matchesAny($lower, [
            '/\b(ship(ping|ment)?|deliver(y)?|freight|how\s*long|arrival|arrive|dispatch|courier)\b/i',
            '/\b(شحن|توصيل|تسليم|كم\s*يوم|متى\s*يوصل|مدة\s*التوصيل)\b/u',
        ])) {
            return 'shipping_inquiry';
        }

        // Purchase intent patterns
        if ($this->matchesAny($lower, [
            '/\b(buy|purchase|order|add\s*to\s*cart|checkout|want\s*to\s*(buy|order|get)|i\'?ll\s*take|how\s*(can|do)\s*i\s*(buy|order|get))\b/i',
            '/\b(place\s*(an?\s*)?order|make\s*(an?\s*)?order|minimum\s*order|moq|mov|bulk|wholesale)\b/i',
            '/\b(أريد|أشتري|اطلب|أضف|سلة|الشراء|كيف\s*اشتري|كيف\s*اطلب|الحد\s*الأدنى)\b/u',
        ])) {
            return 'purchase_intent';
        }

        // Comparison patterns
        if ($this->matchesAny($lower, [
            '/\b(compare|vs|versus|difference|better|which\s*one|or\b.*\bor\b)\b/i',
            '/\b(مقارنة|الفرق|أيهما|أفضل|ولا)\b/u',
        ])) {
            return 'comparison';
        }

        // Product inquiry (catch-all for product-related questions)
        if ($this->matchesAny($lower, [
            '/\b(product|products|price|cost|how\s*much|available|stock|do\s*you\s*have|tell\s*me\s*about|what\s*is|info|detail|spec|feature|tea|drink|drinks|beverage|beverages)\b/i',
            '/\b(منتج|سعر|كم|متوفر|عندكم|أخبرني|ما\s*هو|معلومات|تفاصيل|مواصفات)\b/u',
        ])) {
            return 'product_inquiry';
        }

        return 'general';
    }

    /**
     * Check if the text matches any of the given regex patterns.
     */
    private function matchesAny(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }
}

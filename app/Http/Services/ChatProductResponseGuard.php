<?php

namespace App\Http\Services;

use App\Models\Product;
use App\Models\User;

class ChatProductResponseGuard
{
    public function __construct(
        protected CatalogSearchService $catalogSearchService,
        protected ChatbotIntentDetector $intentDetector
    ) {
    }

    public function enforce(string $userMessage, string $responseContent, string $locale = 'en'): string
    {
        $intent = $this->intentDetector->detect($userMessage);

        if (!in_array($intent, ['general', 'product_inquiry', 'purchase_intent', 'comparison'], true)) {
            return $responseContent;
        }

        $searchResults = $this->catalogSearchService->search($userMessage, 3, 3);
        $matches = $searchResults['literal']->isNotEmpty()
            ? $searchResults['literal']
            : $searchResults['combined'];

        if ($searchResults['literal']->count() === 1) {
            /** @var Product $product */
            $product = $searchResults['literal']->first();

            return $this->buildMatchedProductResponse($product, $locale);
        }

        if ($matches->isEmpty()) {
            return $responseContent;
        }

        if ($this->containsCatalogDenial($responseContent)) {
            /** @var Product $product */
            $product = $matches->first();

            return $this->buildMatchedProductResponse($product, $locale);
        }

        if ($this->containsAvailabilityMention($responseContent, $locale)) {
            return $responseContent;
        }

        return rtrim($responseContent) . "\n\n" . $this->buildAvailabilitySummary($matches->take(3)->all(), $locale);
    }

    private function containsCatalogDenial(string $content): bool
    {
        $patterns = [
            '/we currently don\'t have that product in our catalog/i',
            '/we do not have that product in our catalog/i',
            '/we don\'t have that product/i',
            '/product is not available in our catalog/i',
            '/sorry[^\n]*don\'t have/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function buildMatchedProductResponse(Product $product, string $locale): string
    {
        $viewer = $this->resolveViewer();
        $arabicName = $product->getTranslation('name', 'ar') ?: $product->getTranslation('name', 'en');
        $englishName = $product->getTranslation('name', 'en') ?: $arabicName;
        $description = $product->getTranslation('description', 'en')
            ?: $product->getTranslation('description', 'ar')
            ?: $englishName;
        $price = $this->resolveLowestDisplayPrice($product, $viewer);
        $moq = $product->minimum_order_quantity ?? 1;
        $mov = $product->resolveSiteMinOrderValue($viewer);
        $stock = $product->variants->sum(fn ($variant) => $variant->inventoryProducts->sum('quantity'));
        $availability = $this->getProductAvailabilityLabel($product, $locale, true);
        $link = '/products/' . $product->slug;
        $isAvailable = !in_array($availability, ['غير متوفر', 'Out of stock'], true);

        if ($this->isArabicLocale($locale)) {
            $response = "نعم، المنتج [{$arabicName}]({$link}) موجود في الكتالوج.\n"
                . "حالة التوفر: {$availability}\n"
                . "السعر يبدأ من: {$price} جنيه مصري\n"
                . "الحد الأدنى لكمية الطلب: {$moq}\n"
                . "الحد الأدنى لقيمة الطلب: {$mov} جنيه مصري\n";

            if ($isAvailable) {
                $response .= "المخزون الحالي: {$stock}\n";
            }

            return $response . "صفحة المنتج: [{$arabicName}]({$link})";
        }

        $response = "Matched product: [{$arabicName}]({$link})\n"
            . "English name: {$englishName}\n"
            . "Matching description: {$description}\n"
            . "Availability: {$availability}\n"
            . "Price starts from: {$price} EGP\n"
            . "Minimum order quantity: {$moq}\n"
            . "Minimum order value: {$mov} EGP\n";

        if ($isAvailable) {
            $response .= "Current stock: {$stock}\n";
        }

        return $response . "Product page: [{$arabicName}]({$link})";
    }

    private function buildAvailabilitySummary(array $products, string $locale): string
    {
        if ($this->isArabicLocale($locale)) {
            $lines = ["حالة التوفر:"];

            foreach ($products as $product) {
                if (!$product instanceof Product) {
                    continue;
                }

                $name = $product->getTranslation('name', 'ar') ?: $product->getTranslation('name', 'en');
                $lines[] = "- {$name}: " . $this->getProductAvailabilityLabel($product, $locale, false);
            }

            return implode("\n", $lines);
        }

        $lines = ['Availability:'];

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $name = $product->getTranslation('name', 'en') ?: $product->getTranslation('name', 'ar');
            $lines[] = "- {$name}: " . $this->getProductAvailabilityLabel($product, $locale, false);
        }

        return implode("\n", $lines);
    }

    private function containsAvailabilityMention(string $content, string $locale): bool
    {
        $patterns = [
            '/availability\s*:/i',
            '/in stock|out of stock|available|unavailable/i',
        ];

        if ($this->isArabicLocale($locale)) {
            $patterns[] = '/حالة\s*التوفر|التوفر|متوفر|غير\s*متوفر/u';
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function getProductAvailabilityLabel(Product $product, string $locale, bool $withCount): string
    {
        $variants = $product->variants ?? collect();
        $totalStock = $variants->sum(fn ($variant) => $variant->inventoryProducts->sum('quantity'));
        $hasAvailableVariant = false;

        foreach ($variants as $variant) {
            $rawAvailability = strtolower((string) ($variant->stock_availability ?? $variant->availability ?? ''));

            if ($rawAvailability === 'in-stock') {
                $hasAvailableVariant = true;
                break;
            }

            if ($rawAvailability === 'out-of-stock') {
                continue;
            }

            if ($variant->inventoryProducts->sum('quantity') > 0) {
                $hasAvailableVariant = true;
                break;
            }
        }

        if ($this->isArabicLocale($locale)) {
            if ($hasAvailableVariant) {
                return $withCount ? "متوفر ({$totalStock})" : 'متوفر';
            }

            return 'غير متوفر';
        }

        if ($hasAvailableVariant) {
            return $withCount ? "Available ({$totalStock})" : 'Available';
        }

        return 'Out of stock';
    }

    private function isArabicLocale(string $locale): bool
    {
        return str_starts_with(strtolower($locale), 'ar');
    }

    private function resolveViewer(): ?User
    {
        return auth('sanctum')->user() ?? auth()->user();
    }

    private function resolveLowestDisplayPrice(Product $product, ?User $viewer): float|string
    {
        $price = $product->variants
            ->map(fn ($variant) => $variant->resolveSiteDisplayPrice($viewer))
            ->filter(fn ($value) => $value > 0)
            ->min();

        return $price !== null ? round((float) $price, 2) : 'N/A';
    }
}

<?php

namespace App\Http\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ChatProductLinker
{
    /**
     * Add markdown links to product names mentioned in chatbot responses.
     */
    public function linkProducts(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        [$protectedContent, $placeholders] = $this->protectExistingMarkdownLinks($content);
        $linkedContent = $this->linkKnownProductNames($protectedContent);

        return strtr($linkedContent, $placeholders);
    }

    private function linkKnownProductNames(string $content): string
    {
        foreach ($this->getProductNameMap() as $name => $slug) {
            $escapedName = preg_quote($name, '/');
            $pattern = '/(?<![\p{L}\p{N}\]\/_-])(' . $escapedName . ')(?![\p{L}\p{N}\[\/_-])/u';
            $replacement = '[' . '$1' . '](/products/' . $slug . ')';

            $content = preg_replace($pattern, $replacement, $content) ?? $content;
        }

        return $content;
    }

    /**
     * Prevent double-linking product names that are already inside markdown links.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function protectExistingMarkdownLinks(string $content): array
    {
        $placeholders = [];
        $index = 0;

        $protected = preg_replace_callback('/\[[^\]]+\]\([^)]+\)/u', function (array $matches) use (&$placeholders, &$index) {
            $placeholder = "__CHAT_LINK_{$index}__";
            $placeholders[$placeholder] = $matches[0];
            $index++;

            return $placeholder;
        }, $content);

        return [$protected ?? $content, $placeholders];
    }

    /**
     * @return array<string, string>
     */
    private function getProductNameMap(): array
    {
        return Cache::remember('chatbot_product_name_map_v1', now()->addMinutes(10), function () {
            $products = Product::query()
                ->where('status', 'approved')
                ->has('variants')
                ->get(['id', 'name', 'slug']);

            $map = [];

            foreach ($products as $product) {
                foreach (['ar', 'en'] as $locale) {
                    $name = trim((string) $product->getTranslation('name', $locale, false));

                    if ($name === '' || isset($map[$name])) {
                        continue;
                    }

                    $map[$name] = $product->slug;
                }
            }

            uksort($map, fn(string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

            return $map;
        });
    }
}

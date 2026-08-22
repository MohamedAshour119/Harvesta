<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ImportShahdCatalogSeeder extends Seeder
{
    private const BASE_URL = 'https://shahd.shop';

    private const COLLECTION_CATEGORY_MAP = [
        'frontpage' => 'fresh',
        'frozen-مجمد' => 'frozen',
        'oil' => 'oil',
        'breaded-chicken-فراخ-مقرمشة' => 'fried',
        'frozen-vegetables-خضار-مجمد' => 'frozen-vegetables',
        'lanalou-x-shahd' => 'lanalou-x-shahd',
        'tea-شاى' => 'tea',
        'meat-range-لحوم-مجمدة' => 'frozen',
        'promotions-عروض' => 'fried',
        'promotion-offers' => 'fried',
    ];

    private const VARIANT_TYPE_TRANSLATIONS = [
        'Size' => ['en' => 'Size', 'ar' => 'الحجم'],
        'Style' => ['en' => 'Style', 'ar' => 'النوع'],
        'Title' => ['en' => 'Option', 'ar' => 'الخيار'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ShahdIndustryCategoriesSeeder::class,
        ]);

        $products = $this->fetchAllProducts();
        $categorySlugByHandle = $this->buildCategoryMap();
        $categoryIdsBySlug = $this->resolveAllCategoryIds();
        $variantTypeIds = $this->ensureVariantTypes();

        $this->command?->info('Fetched ' . count($products) . ' products from Shahd Shop.');
        $this->command?->info('Resolved ' . count($categoryIdsBySlug) . ' category slugs.');

        $imported = 0;

        foreach ($products as $productData) {
            $handle = (string) ($productData['handle'] ?? '');
            $variants = $productData['variants'] ?? [];

            if ($handle === '' || empty($variants)) {
                $this->command?->warn("Skipping product with empty handle or no variants: " . ($productData['title'] ?? 'unknown'));
                continue;
            }

            $categorySlug = $categorySlugByHandle[$handle] ?? $this->guessCategorySlug($productData);
            $categoryId = $categoryIdsBySlug[$categorySlug] ?? null;

            if (! $categoryId) {
                // Fallback: try to find any category that starts with the slug
                $fallbackCategory = IndustryCategory::query()
                    ->where('slug', 'like', $categorySlug . '%')
                    ->first();

                if ($fallbackCategory) {
                    $categoryId = $fallbackCategory->id;
                    $categoryIdsBySlug[$categorySlug] = $categoryId;
                    $this->command?->info("Resolved '{$categorySlug}' via fallback to '{$fallbackCategory->slug}' (ID: {$categoryId})");
                } else {
                    // Last resort: use 'frozen' as default
                    $categoryId = $categoryIdsBySlug['frozen'] ?? IndustryCategory::query()->where('slug', 'like', 'frozen%')->whereNot('slug', 'like', 'frozen-vegetables%')->value('id');
                    $this->command?->warn("No category found for '{$handle}' (slug: {$categorySlug}), defaulting to frozen (ID: {$categoryId})");
                }
            }

            if (! $categoryId) {
                $this->command?->error("Cannot import '{$handle}': no category available at all.");
                continue;
            }

            $localizedName = $this->localizeText((string) ($productData['title'] ?? 'Shahd Product'));
            $rawDescription = $this->cleanText((string) ($productData['body_html'] ?? ''));
            $localizedDescription = $this->localizeText($rawDescription !== '' ? $rawDescription : $localizedName['en']);
            $pricing = $this->extractProductPricing($variants);
            $hasStock = $this->productHasStock($variants);

            try {
                $product = Product::withoutEvents(function () use (
                    $categoryId,
                    $categorySlug,
                    $handle,
                    $localizedDescription,
                    $localizedName,
                    $pricing,
                    $hasStock
                ) {
                    return Product::query()->updateOrCreate(
                        ['slug' => $handle],
                        [
                            'industry_category_id' => $categoryId,
                            'name' => $localizedName,
                            'description' => $localizedDescription,
                            'min_order_value' => $pricing['minimum'],
                            'supplier_id' => null,
                            'is_approved' => true,
                            'status' => 'approved',
                            'order_count' => 0,
                            'stock_availability' => $hasStock ? 'In Stock' : 'Out Of Stock',
                            'oem' => false,
                            'notes' => "Imported from Shahd Shop ({$categorySlug})",
                            'minimum_order_quantity' => 1,
                        ]
                    );
                });

                $this->syncProductImages($product, $productData['images'] ?? []);
                $this->syncVariants($product, $productData, $variantTypeIds);

                $imported++;
            } catch (\Throwable $e) {
                $this->command?->error("Failed to import '{$handle}': {$e->getMessage()}");
            }
        }

        $this->command?->info("Imported or updated {$imported} Shahd products.");
    }

    private function buildCategoryMap(): array
    {
        $map = [];

        foreach (self::COLLECTION_CATEGORY_MAP as $collectionHandle => $categorySlug) {
            $page = 1;

            do {
                $url = self::BASE_URL . '/collections/' . rawurlencode($collectionHandle) . '/products.json?limit=250&page=' . $page;

                try {
                    $products = $this->fetchJson($url, 'products');
                } catch (\Throwable $e) {
                    $this->command?->warn("Failed to fetch collection '{$collectionHandle}' page {$page}: {$e->getMessage()}");
                    $products = [];
                }

                foreach ($products as $product) {
                    $handle = (string) ($product['handle'] ?? '');

                    if ($handle !== '' && ! isset($map[$handle])) {
                        $map[$handle] = $categorySlug;
                    }
                }

                $page++;
            } while (count($products) >= 250);
        }

        $this->command?->info('Built category map with ' . count($map) . ' product handles.');

        return $map;
    }

    /**
     * Fetch all products from the Shahd Shop with pagination support.
     */
    private function fetchAllProducts(): array
    {
        $allProducts = [];
        $page = 1;

        do {
            $url = self::BASE_URL . '/products.json?limit=250&page=' . $page;
            $products = $this->fetchJson($url, 'products');
            $allProducts = array_merge($allProducts, $products);
            $page++;
        } while (count($products) >= 250);

        return $allProducts;
    }

    /**
     * Resolve all category IDs by slug, handling slugs with ID suffixes.
     */
    private function resolveAllCategoryIds(): array
    {
        $expectedSlugs = array_unique(array_values(self::COLLECTION_CATEGORY_MAP));
        $resolved = [];

        foreach ($expectedSlugs as $slug) {
            // First try exact match
            $category = IndustryCategory::query()->where('slug', $slug)->first();

            if (! $category) {
                // Try LIKE match for slugs with ID suffixes (e.g. 'fresh-15' matches 'fresh')
                $category = IndustryCategory::query()
                    ->where('slug', 'like', $slug . '%')
                    ->where('slug', 'not like', $slug . '-%-%')  // avoid matching deeper slugs
                    ->first();
            }

            if ($category) {
                $resolved[$slug] = $category->id;
            } else {
                $this->command?->warn("Category slug '{$slug}' not found in database.");
            }
        }

        return $resolved;
    }

    private function ensureVariantTypes(): array
    {
        $variantTypeIds = [];

        foreach (self::VARIANT_TYPE_TRANSLATIONS as $shopifyName => $translations) {
            $variantType = VariantType::query()
                ->where('name->en', $translations['en'])
                ->first();

            if (! $variantType) {
                $variantType = VariantType::query()->create([
                    'name' => $translations,
                ]);
            } else {
                $variantType->update([
                    'name' => $translations,
                ]);
            }

            $variantTypeIds[$shopifyName] = $variantType->id;
        }

        return $variantTypeIds;
    }

    private function syncProductImages(Product $product, array $images): void
    {
        $urls = [];

        foreach ($images as $image) {
            $url = (string) ($image['src'] ?? '');

            if ($url !== '' && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        if (empty($urls)) {
            return;
        }

        $mainImage = array_shift($urls);

        try {
            $product->clearMediaCollection('main_images');
            $product->addMediaFromUrl($mainImage)->toMediaCollection('main_images');
        } catch (\Throwable $exception) {
            $this->command?->warn("Failed to import main image for {$product->slug}: {$mainImage}");
        }

        $product->clearMediaCollection('products_other_images');

        foreach ($urls as $url) {
            try {
                $product->addMediaFromUrl($url)->toMediaCollection('products_other_images');
            } catch (\Throwable $exception) {
                $this->command?->warn("Failed to import gallery image for {$product->slug}: {$url}");
            }
        }
    }

    private function syncVariants(Product $product, array $productData, array $variantTypeIds): void
    {
        $syncedVariantIds = [];
        $productImages = $productData['images'] ?? [];
        $optionName = $this->resolveOptionName($productData);
        $variantTypeId = $variantTypeIds[$optionName] ?? $variantTypeIds['Title'] ?? null;

        foreach (($productData['variants'] ?? []) as $variantData) {
            $pricing = $this->extractVariantPricing($variantData);
            $variantValue = $this->resolveVariantValue($variantData);

            $variant = ProductVariant::withoutEvents(function () use ($product, $variantData, $variantTypeId, $variantValue, $pricing) {
                return ProductVariant::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'variant_type_id' => $variantTypeId,
                        'variant_value' => $variantValue,
                    ],
                    [
                        'supplier_id' => null,
                        'unit_price_usd' => $pricing['original'],
                        'cost_plus_logistics' => null,
                        'discount_percentage' => $pricing['discount_percentage'],
                        'price_after_discount' => $pricing['sale'],
                        'availability' => ! empty($variantData['available']) ? 'in-stock' : 'out-of-stock',
                        'weight' => $this->extractWeightInKg($variantData),
                        'volume' => 0,
                    ]
                );
            });

            $this->syncVariantImage($variant, $variantData, $productImages);

            $syncedVariantIds[] = $variant->id;
        }

        if (! empty($syncedVariantIds)) {
            $product->variants()->whereNotIn('id', $syncedVariantIds)->delete();
        }
    }

    private function syncVariantImage(ProductVariant $variant, array $variantData, array $productImages): void
    {
        $imageUrl = (string) ($variantData['featured_image']['src'] ?? '');

        if ($imageUrl === '' && ! empty($productImages)) {
            $imageUrl = (string) ($productImages[0]['src'] ?? '');
        }

        if ($imageUrl === '') {
            return;
        }

        try {
            $variant->clearMediaCollection('variants_images');
            $variant->addMediaFromUrl($imageUrl)->toMediaCollection('variants_images');
        } catch (\Throwable $exception) {
            $this->command?->warn("Failed to import variant image for product {$variant->product_id}: {$imageUrl}");
        }
    }

    private function fetchJson(string $url, string $key): array
    {
        $response = Http::timeout(60)
            ->acceptJson()
            ->get($url)
            ->throw()
            ->json($key);

        return is_array($response) ? $response : [];
    }

    private function extractProductPricing(array $variants): array
    {
        $minimum = null;

        foreach ($variants as $variant) {
            $pricing = $this->extractVariantPricing($variant);

            if ($minimum === null || $pricing['sale'] < $minimum) {
                $minimum = $pricing['sale'];
            }
        }

        return [
            'minimum' => $minimum ?? 0,
        ];
    }

    private function extractVariantPricing(array $variantData): array
    {
        $salePrice = round((float) ($variantData['price'] ?? 0), 2);
        $compareAtPrice = round((float) ($variantData['compare_at_price'] ?? 0), 2);
        $originalPrice = $compareAtPrice > $salePrice ? $compareAtPrice : $salePrice;
        $discountPercentage = 0;

        if ($compareAtPrice > $salePrice && $compareAtPrice > 0) {
            $discountPercentage = round((($compareAtPrice - $salePrice) / $compareAtPrice) * 100, 2);
        }

        return [
            'original' => $originalPrice,
            'sale' => $salePrice,
            'discount_percentage' => $discountPercentage,
        ];
    }

    private function productHasStock(array $variants): bool
    {
        foreach ($variants as $variant) {
            if (! empty($variant['available'])) {
                return true;
            }
        }

        return false;
    }

    private function extractWeightInKg(array $variantData): float
    {
        $grams = (float) ($variantData['grams'] ?? 0);

        if ($grams <= 0) {
            return 0;
        }

        return round($grams / 1000, 2);
    }

    private function resolveOptionName(array $productData): string
    {
        $optionName = (string) ($productData['options'][0]['name'] ?? 'Title');

        return array_key_exists($optionName, self::VARIANT_TYPE_TRANSLATIONS) ? $optionName : 'Title';
    }

    private function resolveVariantValue(array $variantData): string
    {
        $value = $this->cleanText((string) ($variantData['title'] ?? ''));

        if ($value === '' || strcasecmp($value, 'Default Title') === 0) {
            $value = $this->cleanText((string) ($variantData['option1'] ?? 'Default'));
        }

        if ($value === '' || strcasecmp($value, 'Default Title') === 0) {
            return 'Default';
        }

        return $value;
    }

    private function guessCategorySlug(array $productData): string
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            (string) ($productData['handle'] ?? ''),
            (string) ($productData['title'] ?? ''),
            (string) ($productData['product_type'] ?? ''),
            implode(' ', $productData['tags'] ?? []),
        ])));

        $keywordMap = [
            'lanalou-x-shahd' => ['lanalou'],
            'tea' => ['tea', 'شاي', 'شاى', 'zaman'],
            'oil' => ['oil', 'زيت', 'roots', 'روتس', 'رووتس', 'sultan', 'سلطان'],
            'fresh' => ['fresh', 'فريش', 'طازة'],
            'frozen-vegetables' => ['okra', 'molokhia', 'peas', 'vegetables', 'بامية', 'ملوخية', 'بسلة', 'خضار'],
            'fried' => ['fried', 'pane', 'nuggets', 'strips', 'fillet', 'rings', 'chips', 'leg', 'ناجتس', 'ستربس', 'بانية', 'مقلي', 'بطاطس'],
            'frozen' => ['frozen', 'مجمد', 'moomoos', 'burger', 'kofta', 'hawawshi', 'meat', 'لحم', 'بتلو', 'سجق'],
        ];

        foreach ($keywordMap as $categorySlug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    return $categorySlug;
                }
            }
        }

        return 'frozen';
    }

    /**
     * Known translations for products that only have one language in their title.
     */
    private const KNOWN_TRANSLATIONS = [
        // Arabic-only products -> English translations
        'دجاجه كامله مجمده وزن 1100/1200' => ['en' => 'Frozen Whole Chicken 1100/1200 gm', 'ar' => 'دجاجه كامله مجمده وزن 1100/1200'],
        'دجاجه كامله مجمده وزن 1300/1400' => ['en' => 'Frozen Whole Chicken 1300/1400 gm', 'ar' => 'دجاجه كامله مجمده وزن 1300/1400'],
        'دجاجه كامله مجمده وزن 1400/1500' => ['en' => 'Frozen Whole Chicken 1400/1500 gm', 'ar' => 'دجاجه كامله مجمده وزن 1400/1500'],
        'لحم مفروم لحم بلدي' => ['en' => 'Minced Meat - Local Beef', 'ar' => 'لحم مفروم لحم بلدي'],
        'لحم مفروم 450جم لحم بلدي' => ['en' => 'Minced Meat 450 gm - Local Beef', 'ar' => 'لحم مفروم 450جم لحم بلدي'],
        'زيت ذره روتس بلزعتر 250مل' => ['en' => 'Roots Corn Oil with Thyme 250 ml', 'ar' => 'زيت ذره روتس بلزعتر 250مل'],
        'زيت ذره روتس بلثوم 250مل' => ['en' => 'Roots Corn Oil with Garlic 250 ml', 'ar' => 'زيت ذره روتس بلثوم 250مل'],
        'زيت ذره روتس بلشطه 250مل' => ['en' => 'Roots Corn Oil with Chili 250 ml', 'ar' => 'زيت ذره روتس بلشطه 250مل'],
        'زيت ذره روتس بلروزماري 250مل' => ['en' => 'Roots Corn Oil with Rosemary 250 ml', 'ar' => 'زيت ذره روتس بلروزماري 250مل'],
        'حواوشي بلدي 600 جم' => ['en' => 'Hawawshi 600 gm', 'ar' => 'حواوشي بلدي 600 جم'],
        'كرات لحم بلدي 350 جم - Meat Balls' => ['en' => 'Meat Balls 350 gm', 'ar' => 'كرات لحم بلدي 350 جم'],
        'تحفظ مجمدة في درجة حرارة -18 درجة مئوية' => ['en' => 'Store frozen at -18°C', 'ar' => 'تحفظ مجمدة في درجة حرارة -18 درجة مئوية'],
        // English-only products -> Arabic translations
        'CHICKEN CHIPS 400gm' => ['en' => 'Chicken Chips 400 gm', 'ar' => 'تشيكين شيبس 400 جم'],
        'Chicken rings 750gm' => ['en' => 'Chicken Rings 750 gm', 'ar' => 'حلقات دجاج 750 جم'],
        'Spicy Pane Beyti 800gm' => ['en' => 'Spicy Pane Beyti 800 gm', 'ar' => 'بانية بيتي حار 800 جم'],
        'CHICKEN LEG' => ['en' => 'Chicken Leg', 'ar' => 'ورك دجاج'],
        'SPICY CHICKEN LEG' => ['en' => 'Spicy Chicken Leg', 'ar' => 'ورك دجاج حار'],
        'Shahd potatoes 2500 kg' => ['en' => 'Shahd Potatoes 2500 gm', 'ar' => 'بطاطس شهد 2500 جم'],
        'Lanalou  x shahd Booklet' => ['en' => 'Lanalou x Shahd Booklet', 'ar' => 'كتيب لانالاو × شهد'],
        'Lanalou x shahd Booklet' => ['en' => 'Lanalou x Shahd Booklet', 'ar' => 'كتيب لانالاو × شهد'],
    ];

    private function localizeText(string $text): array
    {
        $text = $this->cleanText($text);

        if ($text === '') {
            return ['en' => '', 'ar' => ''];
        }

        // Check known translations first (exact match)
        if (isset(self::KNOWN_TRANSLATIONS[$text])) {
            return self::KNOWN_TRANSLATIONS[$text];
        }

        // Try to find a language-boundary separator (dash between Latin and Arabic text)
        // Shahd titles commonly use: "English Title - Arabic Title" or "Arabic Title - English"
        // We scan for all dashes and find the one that separates language zones
        $parts = $this->splitByLanguageBoundary($text);

        if (count($parts) === 2) {
            $part1 = trim($parts[0]);
            $part2 = trim($parts[1]);

            $part1HasArabic = preg_match('/\p{Arabic}/u', $part1) === 1;
            $part1HasLatin = preg_match('/[A-Za-z]/u', $part1) === 1;
            $part2HasArabic = preg_match('/\p{Arabic}/u', $part2) === 1;
            $part2HasLatin = preg_match('/[A-Za-z]/u', $part2) === 1;

            // Pattern: "English - Arabic"
            if ($part1HasLatin && ! $part1HasArabic && $part2HasArabic && ! $part2HasLatin) {
                return [
                    'en' => $this->cleanLocalizedSegment($part1),
                    'ar' => $this->cleanLocalizedSegment($part2),
                ];
            }

            // Pattern: "Arabic - English"
            if ($part1HasArabic && ! $part1HasLatin && $part2HasLatin && ! $part2HasArabic) {
                return [
                    'en' => $this->cleanLocalizedSegment($part2),
                    'ar' => $this->cleanLocalizedSegment($part1),
                ];
            }

            // Pattern: "English with numbers - Arabic with numbers"
            if ($part1HasLatin && $part2HasArabic) {
                $en = $this->extractLanguageSegment($part1, 'en');
                $ar = $this->extractLanguageSegment($part2, 'ar');

                if ($en !== '' && $ar !== '') {
                    return ['en' => $en, 'ar' => $ar];
                }
            }

            // Pattern: "Arabic with numbers - English with numbers"
            if ($part1HasArabic && $part2HasLatin) {
                $ar = $this->extractLanguageSegment($part1, 'ar');
                $en = $this->extractLanguageSegment($part2, 'en');

                if ($en !== '' && $ar !== '') {
                    return ['en' => $en, 'ar' => $ar];
                }
            }
        }

        // No clear separator — try character-based extraction
        $hasArabic = preg_match('/\p{Arabic}/u', $text) === 1;
        $hasLatin = preg_match('/[A-Za-z]/u', $text) === 1;

        if (! $hasArabic && $hasLatin) {
            // English only
            return ['en' => $text, 'ar' => $text];
        }

        if ($hasArabic && ! $hasLatin) {
            // Arabic only
            return ['en' => $text, 'ar' => $text];
        }

        if ($hasArabic && $hasLatin) {
            // Mixed — extract each language keeping numbers/measurements
            $english = $this->extractLanguageSegment($text, 'en');
            $arabic = $this->extractLanguageSegment($text, 'ar');

            return [
                'en' => $english !== '' ? $english : $text,
                'ar' => $arabic !== '' ? $arabic : $text,
            ];
        }

        return ['en' => $text, 'ar' => $text];
    }

    /**
     * Split text by finding the dash that separates Latin text from Arabic text.
     * Returns array of 2 parts if a language boundary is found, or array of 1 part if not.
     */
    private function splitByLanguageBoundary(string $text): array
    {
        // Find all dash positions and check if they separate language zones
        // by examining the IMMEDIATE context (nearby characters) around each dash,
        // not the entire preceding/following text. This avoids splitting words
        // like "Bone-in" or "Whole-legs" where the dash is within an English word.
        $offset = 0;
        $bestPos = null;
        $textLen = mb_strlen($text);

        while (($pos = mb_strpos($text, '-', $offset)) !== false) {
            // Get a few characters immediately before and after the dash
            $nearBeforeStart = max(0, $pos - 5);
            $nearBefore = mb_substr($text, $nearBeforeStart, $pos - $nearBeforeStart);
            $nearAfterEnd = min($textLen, $pos + 6);
            $nearAfter = mb_substr($text, $pos + 1, $nearAfterEnd - $pos - 1);

            $nearBeforeHasArabic = preg_match('/\p{Arabic}/u', $nearBefore) === 1;
            $nearBeforeHasLatin = preg_match('/[A-Za-z]/u', $nearBefore) === 1;
            $nearAfterHasArabic = preg_match('/\p{Arabic}/u', $nearAfter) === 1;
            $nearAfterHasLatin = preg_match('/[A-Za-z]/u', $nearAfter) === 1;

            // Language boundary: one side is Latin-only, the other is Arabic-only (in the near context)
            $isLangBoundary = ($nearBeforeHasLatin && ! $nearBeforeHasArabic && $nearAfterHasArabic && ! $nearAfterHasLatin)
                || ($nearBeforeHasArabic && ! $nearBeforeHasLatin && $nearAfterHasLatin && ! $nearAfterHasArabic);

            if ($isLangBoundary) {
                $bestPos = $pos;
                break;
            }

            $offset = $pos + 1;
        }

        if ($bestPos !== null) {
            return [
                mb_substr($text, 0, $bestPos),
                mb_substr($text, $bestPos + 1),
            ];
        }

        return [$text];
    }

    /**
     * Extract a language segment from mixed text, preserving numbers and measurements.
     */
    private function extractLanguageSegment(string $text, string $lang): string
    {
        if ($lang === 'en') {
            // Keep Latin letters, digits, common punctuation, and measurements
            $segment = (string) preg_replace('/[\p{Arabic}\x{060C}\x{061B}\x{061F}]+/u', ' ', $text);
        } else {
            // Keep Arabic letters, digits, common punctuation
            $segment = (string) preg_replace('/[A-Za-z]+/u', ' ', $text);
        }

        $segment = preg_replace('/\s+/u', ' ', $segment) ?? $segment;

        return $this->cleanLocalizedSegment($segment);
    }

    /**
     * Ensure a string is valid UTF-8.
     */
    private function sanitizeUtf8(string $text): string
    {
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove null bytes and other control characters except newlines/tabs
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    }

    private function cleanLocalizedSegment(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        // Use regex-based trimming to avoid corrupting multi-byte UTF-8 sequences
        // PHP's trim() operates on bytes, not characters, so multi-byte chars in the
        // trim list (like ، U+060C) can corrupt adjacent UTF-8 sequences.
        $text = preg_replace('/^[\s\-–|\/"\',:]+|[\s\-–|\/"\',:]+$/u', '', $text) ?? $text;

        return $text;
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = $this->sanitizeUtf8($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}

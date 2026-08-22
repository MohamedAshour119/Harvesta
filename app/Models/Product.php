<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;
    protected $guarded = [];
    public array $translatable = ['name', 'description'];

    /**
     * Register media collections for the product
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_images')
            ->singleFile();
        
        $this->addMediaCollection('products_other_images')
            ->useDisk('public');
    }

    public function resolveAgentMinOrderValue(): float
    {
        return round((float) ($this->agent_min_order_value ?? $this->min_order_value ?? 0), 2);
    }

    public function resolveSiteMinOrderValue(?User $user = null): float
    {
        if ($user?->is_agent) {
            return $this->resolveAgentMinOrderValue();
        }

        return round((float) ($this->min_order_value ?? 0), 2);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function industrialCategory()
    {
        return $this->belongsTo(IndustryCategory::class, 'industry_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ProductRating::class);
    }

    public function embedding()
    {
        return $this->hasOne(ProductEmbedding::class);
    }

    public function imageEmbedding()
    {
        return $this->hasOne(ProductImageEmbedding::class);
    }

    public function smartBundle()
    {
        return $this->hasOne(SmartBundle::class)->where('is_active', true);
    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('slider')
            ->width(300)
            ->height(230)
            ->quality(75)
            ->format('webp');

        $this->addMediaConversion('product_page')
            ->width(800)
            ->height(600)
            ->quality(75)
            ->format('webp');

        $this->addMediaConversion('product_card')
            ->width(260)
            ->height(160)
            ->quality(75)
            ->format('webp');
    }

    protected static function boot(): void
    {
        parent::boot();
        static::created(function (Product $product) {
            if (empty($product->slug)) {
                $englishName = self::extractEnglishText($product->name);
                if (empty($englishName)) {
                    $slug = 'product-' . $product->id;
                } else {
                    $slug = Str::slug($englishName, '-');
                    $wordCount = str_word_count($englishName);
                    if ($wordCount <= 1) {
                        $slug .= '-' . $product->id;
                    }
                }
                // Ensure uniqueness
                $existing = Product::where('slug', $slug)
                    ->where('id', '!=', $product->id)
                    ->first();
                if ($existing) {
                    $slug .= '-' . $product->id;
                }
                $product->slug = $slug;
                $product->saveQuietly();
            }
        });
        // Regenerate slug when name is updated
        static::updating(function (Product $product) {
            if ($product->isDirty('name')) {
                $englishName = self::extractEnglishText($product->name);
                if (empty($englishName)) {
                    $slug = 'product-' . $product->id;
                } else {
                    $slug = Str::slug($englishName, '-');
                    $wordCount = str_word_count($englishName);
                    if ($wordCount <= 1) {
                        $slug .= '-' . $product->id;
                    }
                }
                // Ensure uniqueness
                $existing = Product::where('slug', $slug)
                    ->where('id', '!=', $product->id)
                    ->first();
                if ($existing) {
                    $slug .= '-' . $product->id;
                }
                $product->slug = $slug;
            }
        });
    }

    /**
     * Extract English text from mixed language string
     */
    private static function extractEnglishText(string $text): string
    {
        $englishText = preg_replace('/[^\x00-\x7F\s]/', '', $text);
        $englishText = preg_replace('/\s+/', ' ', $englishText);
        return trim($englishText);
    }
}

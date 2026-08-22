<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class IndustryCategory extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;
    protected $guarded = [];
    public array $translatable = ['name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('industry_category_images')
            ->useDisk('public')
            ->singleFile();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (IndustryCategory $category) {
            if (empty($category->slug)) {
                $en = is_array($category->name) && isset($category->name['en'])
                    ? (string) $category->name['en']
                    : (string) $category->name;
                $english = self::extractEnglishText($en);
                $slug = Str::slug($english ?: 'category');
                // ensure uniqueness
                $original = $slug;
                $i = 1;
                while (IndustryCategory::where('slug', $slug)->exists()) {
                    $slug = $original.'-'.(++$i);
                }
                $category->slug = $slug;
            }
        });

        static::updating(function (IndustryCategory $category) {
            if ($category->isDirty('name')) {
                $en = is_array($category->name) && isset($category->name['en'])
                    ? (string) $category->name['en']
                    : (string) $category->name;
                $english = self::extractEnglishText($en);
                $slug = Str::slug($english ?: 'category');
                // ensure uniqueness excluding current ID
                $original = $slug;
                $i = 1;
                while (IndustryCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $original.'-'.(++$i);
                }
                $category->slug = $slug;
            }
        });
    }

    private static function extractEnglishText(string $text): string
    {
        $englishText = preg_replace('/[^\x00-\x7F\s]/', '', $text);
        $englishText = preg_replace('/\s+/', ' ', $englishText);
        return trim($englishText);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class VariantType extends Model
{
    use HasTranslations;

    protected $table = 'variants_type';
    protected $guarded = [];
    public $translatable = ['name'];

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}

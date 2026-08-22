<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEmbedding extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Decode JSON embedding string to PHP float array
     */
    public function getVectorAttribute(): array
    {
        return json_decode($this->embedding, true) ?? [];
    }
}

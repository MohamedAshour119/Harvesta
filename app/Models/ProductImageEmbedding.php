<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImageEmbedding extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the vector as an array
     */
    public function getVectorAttribute(): array
    {
        return json_decode($this->embedding, true) ?: [];
    }

    /**
     * Get the multimodal text vector as an array
     */
    public function getTextVectorAttribute(): array
    {
        return json_decode($this->text_embedding ?? '', true) ?: [];
    }
}

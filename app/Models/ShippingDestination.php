<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ShippingDestination extends Model
{
    use HasTranslations;
    protected $guarded = [];

    public array $translatable = ['city'];
}

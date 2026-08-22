<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasTranslations;
    public $translatable = ['name'];
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function (Agent $agent) {
            // auto-generate code if admin left it blank
            if (empty($agent->code)) {
                do {
                    $candidate = Str::upper(Str::random(8));
                } while (self::where('code', $candidate)->exists());
                $agent->code = $candidate;
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

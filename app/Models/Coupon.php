<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded =[];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['used_status'])
            ->withTimestamps();
    }
}

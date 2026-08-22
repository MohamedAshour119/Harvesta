<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class User extends Authenticatable implements FilamentUser, HasName, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (! $user->is_agent) {
                $user->code = null;

                return;
            }

            if (blank($user->code)) {
                $user->code = static::generateUniqueAgentCode($user);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class)
            ->withPivot(['used_status'])
            ->withTimestamps();
    }

    public function bonus()
    {
        return $this->hasOne(Bonus::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function hasRole($role)
    {
        if ($this->relationLoaded('roles')) {
            $roles = $this->roles;

            if (is_array($role)) {
                return $roles->pluck('name')->intersect($role)->isNotEmpty();
            }

            return $roles->pluck('name')->contains($role);
        }

        // fallback for when not eager loaded
        if (is_array($role)) {
            return $this->roles()->whereIn('name', $role)->exists();
        }

        return $this->roles()->where('name', $role)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(['Admin', 'Super Admin']);
    }
    public function getFilamentName(): string
    {
        return "{$this->full_name}";
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    protected static function generateUniqueAgentCode(User $user): string
    {
        do {
            $code = (string) Str::uuid();
        } while (
            static::query()
                ->where('code', $code)
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->exists()
        );

        return $code;
    }
}

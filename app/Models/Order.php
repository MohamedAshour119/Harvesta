<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\UpdateAgentMonthlyAchievedAmount;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Order extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'sold_at' => 'datetime',
        'approved_at' => 'datetime',
        'invoice_sent_at' => 'datetime',
        'special_sales_options' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (empty($order->invoice_uuid)) {
                $order->invoice_uuid = (string) Str::uuid();
            }
        });

        static::updated(function (self $order): void {
            if ($order->wasChanged('status') && $order->status === 'delivered') {
                UpdateAgentMonthlyAchievedAmount::dispatch($order->id);
            }
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeManualSales(Builder $query): Builder
    {
        return $query->where('source', 'agent_manual_sale');
    }

    public function scopeSubmittedManualSales(Builder $query): Builder
    {
        return $query->manualSales()->where('manual_sale_status', 'submitted');
    }

    public function isManualSale(): bool
    {
        return $this->source === 'agent_manual_sale';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('manual_sale_proof_photo')
            ->singleFile();
    }
}

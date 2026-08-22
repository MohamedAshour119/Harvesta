<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'event_type',
        'ip_address',
        'user_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

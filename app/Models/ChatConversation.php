<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'metadata',
        'ip_address',
        'ua_hash',
        'expires_at',
        'summary',
        'topic_context',
        'type',
    ];

    protected $casts = [
        'metadata' => 'array',
        'topic_context' => 'array',
        'expires_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this conversation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verify that the request belongs to the conversation owner.
     * Checks user_id for authenticated users, IP + UA hash for guests.
     */
    public function belongsToRequest(string $ip, ?int $userId = null, ?string $uaHash = null): bool
    {
        // Authenticated user: verify user_id
        if ($userId && $this->user_id) {
            return $this->user_id === $userId;
        }

        // Guest: verify IP address AND user agent hash
        if ($this->ip_address !== $ip) {
            return false;
        }

        // If UA hash is stored and provided, verify it matches
        if ($uaHash && $this->ua_hash && $this->ua_hash !== $uaHash) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is an admin bot conversation.
     */
    public function isAdminConversation(): bool
    {
        return $this->type === 'admin';
    }
}

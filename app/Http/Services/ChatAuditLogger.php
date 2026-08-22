<?php

namespace App\Http\Services;

use App\Models\ChatAuditLog;
use Illuminate\Http\Request;

class ChatAuditLogger
{
    /**
     * Log a chatbot security/audit event.
     */
    public static function log(
        string $eventType,
        ?int $conversationId,
        Request $request,
        array $metadata = []
    ): void {
        try {
            ChatAuditLog::create([
                'conversation_id' => $conversationId,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_id' => auth('sanctum')->id(),
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the main flow
            \Illuminate\Support\Facades\Log::warning('ChatAuditLogger failed: ' . $e->getMessage());
        }
    }
}

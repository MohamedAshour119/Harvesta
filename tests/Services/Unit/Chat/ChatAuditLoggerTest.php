<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\ChatAuditLogger;
use App\Models\ChatAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\ServiceSmokeTestCase;

class ChatAuditLoggerTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ChatAuditLogger::class;
    }

    public function test_log_persists_audit_entries_without_breaking_the_flow(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        ChatAuditLogger::log('prompt_injection_blocked', null, $request, ['reason' => 'prompt']);

        $log = ChatAuditLog::query()->first();

        $this->assertNotNull($log);
        $this->assertSame('prompt_injection_blocked', $log->event_type);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(['reason' => 'prompt'], $log->metadata);
    }
}

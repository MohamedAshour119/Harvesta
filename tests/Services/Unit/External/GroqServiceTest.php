<?php

namespace Tests\Services\Unit\External;

use App\Http\Services\GroqService;
use Illuminate\Support\Facades\Http;
use Tests\Support\ServiceSmokeTestCase;

class GroqServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return GroqService::class;
    }

    public function test_chat_returns_configuration_message_when_api_key_is_missing(): void
    {
        config(['services.groq.api_key' => null]);

        $service = new GroqService();
        $result = $service->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertSame('AI service is not configured. Please contact support.', $result['content']);
        $this->assertSame(0, $result['tokens_used']);
    }

    public function test_chat_returns_content_and_token_usage_from_api_response(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.groq.model' => 'llama-test',
            'services.groq.temperature' => 0.2,
            'services.groq.max_tokens' => 50,
        ]);

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Hello from Groq']],
                ],
                'usage' => ['total_tokens' => 33],
            ]),
        ]);

        $service = new GroqService();
        $result = $service->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertSame('Hello from Groq', $result['content']);
        $this->assertSame(33, $result['tokens_used']);
    }
}

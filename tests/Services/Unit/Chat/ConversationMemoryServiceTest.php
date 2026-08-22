<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\ConversationMemoryService;
use App\Http\Services\GroqService;
use App\Models\ChatConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class ConversationMemoryServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ConversationMemoryService::class;
    }

    public function test_should_summarize_returns_true_on_every_fourth_user_message(): void
    {
        $conversation = ChatConversation::query()->create([
            'session_id' => 'conversation-1',
            'status' => 'active',
        ]);

        foreach (range(1, 4) as $index) {
            $conversation->messages()->create([
                'role' => 'user',
                'content' => 'Message ' . $index,
            ]);
        }

        $service = $this->instantiateService($this->serviceClass());

        $this->assertTrue($service->shouldSummarize($conversation));
    }

    public function test_generate_summary_updates_conversation_summary_and_topic_context(): void
    {
        $groq = $this->mockDependency(GroqService::class);
        $groq->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => 'Customer asked about Premium Tea and pricing.',
            ]);

        $conversation = ChatConversation::query()->create([
            'session_id' => 'conversation-2',
            'status' => 'active',
            'topic_context' => [],
        ]);

        foreach (range(1, 4) as $index) {
            $conversation->messages()->create([
                'role' => 'user',
                'content' => "Ask about [Premium Tea](/products/premium-tea) {$index}",
            ]);
        }

        $service = $this->instantiateService($this->serviceClass());
        $service->generateSummary($conversation);

        $conversation->refresh();

        $this->assertSame('Customer asked about Premium Tea and pricing.', $conversation->summary);
        $this->assertSame(['premium-tea'], $conversation->topic_context['product_slugs']);
        $this->assertSame(4, $conversation->topic_context['message_count_at_summary']);
    }
}

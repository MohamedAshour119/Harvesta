<?php

namespace App\Http\Services;

use App\Models\ChatConversation;
use Illuminate\Support\Str;

class ConversationMemoryService
{
    protected GroqService $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    /**
     * Build context from conversation memory.
     *
     * @return array ['summary' => string|null, 'topic_slugs' => array]
     */
    public function buildContext(ChatConversation $conversation, string $currentMessage): array
    {
        $summary = $conversation->summary;
        $topicSlugs = $conversation->topic_context['product_slugs'] ?? [];

        // Extract product slugs from recent messages
        $recentSlugs = $this->extractTopicSlugs($conversation);
        $topicSlugs = array_unique(array_merge($topicSlugs, $recentSlugs));

        return [
            'summary' => $summary,
            'topic_slugs' => $topicSlugs,
        ];
    }

    /**
     * Check if the conversation needs summarization.
     * Triggers after 4 user messages, then every 4 user messages after that.
     * This ensures the bot has a memory summary available early.
     */
    public function shouldSummarize(ChatConversation $conversation): bool
    {
        $userMessageCount = $conversation->messages()
            ->where('role', 'user')
            ->count();

        // First summary at 4 messages, then every 4 after that
        return $userMessageCount >= 4 && $userMessageCount % 4 === 0;
    }

    /**
     * Generate a rolling summary of the conversation.
     * Includes existing summary + new messages since last summary.
     * Uses Groq with a compact summarization prompt.
     */
    public function generateSummary(ChatConversation $conversation): void
    {
        $existingSummary = $conversation->summary;
        $lastSummarizedCount = $conversation->topic_context['message_count_at_summary'] ?? 0;

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id', 'asc')
            ->get();

        if ($messages->count() < 4) {
            return;
        }

        // Build conversation text
        // If we have an existing summary, include it + only new messages
        $conversationText = '';

        if ($existingSummary && $lastSummarizedCount > 0) {
            $conversationText = "PREVIOUS SUMMARY: {$existingSummary}\n\nNEW MESSAGES SINCE LAST SUMMARY:\n";
            // Only include messages after the last summary
            $newMessages = $messages->slice($lastSummarizedCount);
            $conversationText .= $newMessages->map(function ($msg) {
                $role = $msg->role === 'user' ? 'Customer' : 'Assistant';
                return "{$role}: " . Str::limit($msg->content, 200);
            })->implode("\n");
        } else {
            // First summary - include all messages
            $conversationText = $messages->map(function ($msg) {
                $role = $msg->role === 'user' ? 'Customer' : 'Assistant';
                return "{$role}: " . Str::limit($msg->content, 200);
            })->implode("\n");
        }

        $summaryPrompt = [
            [
                'role' => 'system',
                'content' => 'You are a conversation summarizer. Create a comprehensive summary of this customer support chat in 3-5 sentences. You MUST include:
1. ALL products that were discussed (names, prices, stock status)
2. What the customer wanted or asked about
3. Key information that was shared (MOQ, pricing, ordering steps)
4. Any actions suggested or taken
Be specific with product names and details. Output ONLY the summary, nothing else.'
            ],
            [
                'role' => 'user',
                'content' => $conversationText,
            ],
        ];

        $response = $this->groqService->chat($summaryPrompt, [
            'temperature' => 0.1,
            'max_tokens' => 300,
        ]);

        $summary = trim($response['content']);

        if (!empty($summary) && $summary !== 'AI service is not configured. Please contact support.') {
            // Extract product slugs
            $slugs = $this->extractTopicSlugs($conversation);

            $conversation->update([
                'summary' => $summary,
                'topic_context' => [
                    'product_slugs' => $slugs,
                    'last_summarized_at' => now()->toISOString(),
                    'message_count_at_summary' => $messages->count(),
                ],
            ]);
        }
    }

    /**
     * Extract product slugs mentioned in conversation messages.
     * Looks for /products/slug patterns and known product references.
     */
    private function extractTopicSlugs(ChatConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        $slugs = [];

        foreach ($messages as $msg) {
            // Match /products/slug-name patterns
            preg_match_all('/\/products\/([a-z0-9\-]+)/i', $msg->content, $matches);
            if (!empty($matches[1])) {
                $slugs = array_merge($slugs, $matches[1]);
            }

            // Match [Product Name](/products/slug) markdown links
            preg_match_all('/\[.+?\]\(\/products\/([a-z0-9\-]+)\)/i', $msg->content, $linkMatches);
            if (!empty($linkMatches[1])) {
                $slugs = array_merge($slugs, $linkMatches[1]);
            }
        }

        return array_unique($slugs);
    }
}

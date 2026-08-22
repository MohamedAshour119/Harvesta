<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = (string) config('services.groq.api_key', '');
        $this->model = (string) config('services.groq.model', 'llama-3.1-8b-instant');
    }

    /**
     * Send a chat completion request to Groq API.
     *
     * @param array $messages Array of message objects [['role' => '...', 'content' => '...']]
     * @param array $options Optional overrides: temperature, max_tokens, model
     * @return array ['content' => string, 'tokens_used' => int]
     */
    public function chat(array $messages, array $options = []): array
    {
        if (empty($this->apiKey)) {
            Log::error('GroqService: API key is not configured.');
            return [
                'content' => 'AI service is not configured. Please contact support.',
                'tokens_used' => 0,
            ];
        }

        $model = $options['model'] ?? $this->model;
        $temperature = $options['temperature'] ?? config('services.groq.temperature', 0.4);
        $maxTokens = $options['max_tokens'] ?? config('services.groq.max_tokens', 1024);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            if ($response->failed()) {
                Log::error('GroqService API Error', [
                    'status' => $response->status(),
                    'error_message' => \Illuminate\Support\Str::limit($response->json('error.message', 'Unknown error'), 200),
                ]);
                return [
                    'content' => 'Sorry, I am unable to respond right now. Please try again later.',
                    'tokens_used' => 0,
                ];
            }

            $content = $response->json('choices.0.message.content', '');
            $tokensUsed = $response->json('usage.total_tokens', 0);

            return [
                'content' => $content,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('GroqService Exception: ' . $e->getMessage());
            return [
                'content' => 'Sorry, I am unable to respond right now. Please try again later.',
                'tokens_used' => 0,
            ];
        }
    }
}

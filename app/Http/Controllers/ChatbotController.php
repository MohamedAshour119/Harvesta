<?php

namespace App\Http\Controllers;

use App\Http\Services\ChatAuditLogger;
use App\Http\Services\ChatbotContextBuilder;
use App\Http\Services\ChatProductLinker;
use App\Http\Services\ChatProductResponseGuard;
use App\Http\Services\ConversationMemoryService;
use App\Http\Services\GroqService;
use App\Http\Services\MessageSanitizer;
use App\Http\Services\PromptInjectionDetector;
use App\Http\Services\ResponseSanitizer;
use App\Http\Services\UserContextService;
use App\Models\ChatConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected GroqService $groqService;
    protected ChatbotContextBuilder $contextBuilder;
    protected MessageSanitizer $sanitizer;
    protected PromptInjectionDetector $injectionDetector;
    protected ResponseSanitizer $responseSanitizer;
    protected ChatProductLinker $productLinker;
    protected ChatProductResponseGuard $productResponseGuard;
    protected ConversationMemoryService $memoryService;
    protected UserContextService $userContextService;

    /**
     * Maximum messages allowed per conversation to prevent abuse.
     */
    protected const MAX_MESSAGES_PER_CONVERSATION = 100;

    public function __construct(
        GroqService $groqService,
        ChatbotContextBuilder $contextBuilder,
        ChatProductLinker $productLinker,
        ChatProductResponseGuard $productResponseGuard,
        MessageSanitizer $sanitizer,
        PromptInjectionDetector $injectionDetector,
        ResponseSanitizer $responseSanitizer,
        ConversationMemoryService $memoryService,
        UserContextService $userContextService
    ) {
        $this->groqService = $groqService;
        $this->contextBuilder = $contextBuilder;
        $this->productLinker = $productLinker;
        $this->productResponseGuard = $productResponseGuard;
        $this->sanitizer = $sanitizer;
        $this->injectionDetector = $injectionDetector;
        $this->responseSanitizer = $responseSanitizer;
        $this->memoryService = $memoryService;
        $this->userContextService = $userContextService;
    }

    /**
     * Initialize a new chat session with a server-generated secure token.
     */
    public function initSession(Request $request): JsonResponse
    {
        $sessionId = Str::random(64);
        $userId = auth('sanctum')->id();

        $conversation = ChatConversation::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => 'active',
            'metadata' => [],
            'ip_address' => $request->ip(),
            'ua_hash' => hash('sha256', $request->userAgent() ?? ''),
            'expires_at' => $userId ? now()->addDays(30) : now()->addHours(72),
            'type' => 'customer',
        ]);

        ChatAuditLogger::log('session_created', $conversation->id, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
            ],
        ]);
    }

    /**
     * Handle incoming chat message and return AI response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'session_id' => ['required', 'string', 'size:64'],
            'product_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $userMessage = $request->input('message');
        $sessionId = $request->input('session_id');
        $productSlug = $request->input('product_slug');
        $locale = $request->header('Accept-Language', 'en');
        $userId = auth('sanctum')->id();

        // 1. Sanitize the message
        $userMessage = $this->sanitizer->sanitize($userMessage);

        if (!$this->sanitizer->isValid($userMessage)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid message. Please enter a valid question.',
            ], 422);
        }

        // 2. Check for prompt injection
        $detection = $this->injectionDetector->analyze($userMessage);

        if (!$detection['safe']) {
            ChatAuditLogger::log('injection_detected', null, $request, [
                'score' => $detection['score'],
                'patterns' => count($detection['matched_patterns']),
                'message_preview' => Str::limit($userMessage, 100),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'I can only help with product and ordering questions. How can I assist you with our catalog?',
                ],
            ]);
        }

        // 3. Find conversation and verify ownership
        $conversation = ChatConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Please refresh the page.',
            ], 403);
        }

        // Verify ownership (IP + UA hash for guests, user_id for authenticated)
        $uaHash = hash('sha256', $request->userAgent() ?? '');
        if (!$conversation->belongsToRequest($request->ip(), $userId, $uaHash)) {
            ChatAuditLogger::log('session_hijack_attempt', $conversation->id, $request);

            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Please refresh the page.',
            ], 403);
        }

        // Check expiry
        if ($conversation->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please refresh the page to start a new chat.',
            ], 403);
        }

        // Check per-conversation message limit
        $messageCount = $conversation->messages()->where('role', 'user')->count();
        if ($messageCount >= self::MAX_MESSAGES_PER_CONVERSATION) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation has reached its message limit. Please start a new chat.',
            ], 429);
        }

        // 4. Save user message (sanitized)
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 5. Build conversation memory context
        $memoryContext = $this->memoryService->buildContext($conversation, $userMessage);

        // 6. Build user context for personalization (authenticated users only)
        $userContext = null;
        if ($userId) {
            $userContext = $this->userContextService->buildUserContext($userId);
        }

        // 7. Build the system prompt using RAG + memory + user context
        $systemPrompt = $this->contextBuilder->buildSystemPrompt(
            $userMessage,
            $productSlug,
            $locale,
            $memoryContext['summary'],
            $memoryContext['topic_slugs'],
            $userContext
        );

        // 8. Load last 20 messages for context, with user messages wrapped in delimiters
        $previousMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id', 'desc')
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                $content = $msg->content;
                // Wrap user messages in delimiters to prevent role confusion
                if ($msg->role === 'user') {
                    $content = $this->sanitizer->wrapForLLM(
                        $this->sanitizer->sanitize($content)
                    );
                }
                return ['role' => $msg->role, 'content' => $content];
            })
            ->toArray();

        // 9. Assemble messages array for Groq
        $messagesArray = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $previousMessages
        );

        // 10. Call Groq API
        $response = $this->groqService->chat($messagesArray);

        // 11. Sanitize the LLM response (prevent system prompt leaks, sensitive data)
        $sanitizedResponse = $this->responseSanitizer->sanitize($response['content']);
        $sanitizedResponse['content'] = $this->productResponseGuard->enforce(
            $userMessage,
            $sanitizedResponse['content'],
            $locale
        );
        $sanitizedResponse['content'] = $this->productLinker->linkProducts($sanitizedResponse['content']);

        if ($sanitizedResponse['was_filtered']) {
            ChatAuditLogger::log('response_filtered', $conversation->id, $request, [
                'reason' => Str::limit($sanitizedResponse['matched_reason'], 200),
            ]);
        }

        // 12. Save assistant response (sanitized)
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $sanitizedResponse['content'],
            'tokens_used' => $response['tokens_used'],
        ]);

        ChatAuditLogger::log('message_sent', $conversation->id, $request, [
            'tokens' => $response['tokens_used'],
        ]);

        // 13. Check if conversation needs summarization (async-safe, non-blocking)
        if ($this->memoryService->shouldSummarize($conversation)) {
            try {
                $this->memoryService->generateSummary($conversation);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Memory summarization failed', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $sanitizedResponse['content'],
            ],
        ]);
    }

    /**
     * Get conversation history for a session.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'size:64'],
        ]);

        $userId = auth('sanctum')->id();

        $conversation = ChatConversation::where('session_id', $request->input('session_id'))
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => true,
                'data' => ['messages' => []],
            ]);
        }

        // Verify ownership
        $uaHash = hash('sha256', $request->userAgent() ?? '');
        if (!$conversation->belongsToRequest($request->ip(), $userId, $uaHash)) {
            ChatAuditLogger::log('history_access_denied', $conversation->id, $request);

            return response()->json([
                'success' => true,
                'data' => ['messages' => []],
            ]);
        }

        // Check expiry
        if ($conversation->isExpired()) {
            return response()->json([
                'success' => true,
                'data' => ['messages' => []],
            ]);
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at->toISOString(),
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => ['messages' => $messages],
        ]);
    }
}

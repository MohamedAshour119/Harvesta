<?php

namespace App\Http\Controllers;

use App\Http\Services\AdminChatbotContextBuilder;
use App\Http\Services\GroqService;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class AdminChatbotController extends Controller
{
    protected GroqService $groqService;
    protected AdminChatbotContextBuilder $contextBuilder;

    public function __construct(
        GroqService $groqService,
        AdminChatbotContextBuilder $contextBuilder
    ) {
        $this->groqService = $groqService;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * Initialize admin chat session.
     */
    public function initSession(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->isSuperAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $sessionId = Str::random(64);

        $conversation = ChatConversation::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'status' => 'active',
            'metadata' => ['admin' => true],
            'ip_address' => $request->ip(),
            'ua_hash' => hash('sha256', $request->userAgent() ?? ''),
            'expires_at' => now()->addDays(7),
            'type' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'data' => ['session_id' => $sessionId],
        ]);
    }

    /**
     * Handle admin chat message.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['required', 'string', 'size:64'],
        ]);

        $user = $request->user();

        if (!$this->isSuperAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $userMessage = trim($request->input('message'));
        $sessionId = $request->input('session_id');

        // Find conversation
        $conversation = ChatConversation::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->where('type', 'admin')
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session.',
            ], 403);
        }

        // Save user message
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Build admin system prompt with real data
        $adminName = $user->full_name ?? $user->name ?? 'Admin';
        $promptResult = $this->contextBuilder->buildSystemPrompt($userMessage, $adminName);

        // Load conversation history
        $previousMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->toArray();

        // Assemble messages for Groq
        $messagesArray = array_merge(
            [['role' => 'system', 'content' => $promptResult['system_prompt']]],
            $previousMessages
        );

        // Call Groq with admin model (70b)
        $response = $this->groqService->chat($messagesArray, [
            'model' => config('services.groq.admin_model', 'llama-3.3-70b-versatile'),
            'temperature' => config('services.groq.admin_temperature', 0.3),
            'max_tokens' => 2048,
        ]);

        // Save assistant response
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $response['content'],
            'tokens_used' => $response['tokens_used'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $response['content'],
                'intent' => $promptResult['intent'],
            ],
        ]);
    }

    /**
     * Get admin chat history.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'size:64'],
        ]);

        $user = $request->user();

        if (!$this->isSuperAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $conversation = ChatConversation::where('session_id', $request->input('session_id'))
            ->where('user_id', $user->id)
            ->where('type', 'admin')
            ->first();

        if (!$conversation) {
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

    /**
     * Check if user is Super Admin.
     */
    private function isSuperAdmin(?User $user): bool
    {
        return $user ? Gate::forUser($user)->allows('superAdminAccess') : false;
    }
}

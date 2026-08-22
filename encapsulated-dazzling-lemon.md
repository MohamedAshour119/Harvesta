# Chatbot Features Implementation Plan

## Context

The client provided a PDF with 7 required chatbot features. The current chatbot is a sales assistant using Groq (Llama 3.1 8b) + Jina embeddings (RAG). It works for guests and authenticated users but lacks: proper memory, natural conversation, order guidance, stock awareness, user personalization, and admin analytics bot.

---

## Feature Analysis: Current vs Required

| # | Feature | Status | Effort |
|---|---------|--------|--------|
| 1 | Memory & Context | PARTIAL - loads 10 msgs but no summarization | Medium |
| 2 | Gen AI Creativity | PARTIAL - temperature 0.1, rigid prompt | Low |
| 3 | Order Creation & Navigation | NOT DONE | Medium |
| 4 | Smart Search & Concise Responses | PARTIAL - semantic search exists but noisy | Medium |
| 5 | Stock/Inventory Integration | NOT DONE - shows static availability string | Low |
| 6 | Personalized per User | NOT DONE - bot doesn't know user | Medium |
| 7 | Super Admin Bot | NOT DONE - completely new system | High |

---

## Implementation Phases

### Phase 1: Quick Wins (Features 5, 2) - Independent, High Impact

### Phase 2: Smart Search & Intent (Features 4, 3) - Better UX

### Phase 3: Memory & Personalization (Features 1, 6) - Build on Phase 1-2

### Phase 4: Admin Bot (Feature 7) - Separate parallel track

---

## FEATURE 5: Stock/Inventory Integration

**Problem**: `ChatbotContextBuilder::formatProductLine()` line 148 uses `$product->variants->first()?->availability` - a static string, NOT real inventory count. `ProductVariant` has `getTotalQuantityAttribute()` that sums `InventoryProduct.quantity` but chatbot never uses it.

**Files to modify:**
- `app/Http/Services/SemanticSearchService.php` - eager load `variants.inventoryProducts`
- `app/Http/Services/ChatbotContextBuilder.php` - compute real stock, update formatProductLine + formatCurrentProduct

**Steps:**
1. In `SemanticSearchService::search()` line 35, change `with(['industrialCategory', 'variants', 'media'])` to `with(['industrialCategory', 'variants.inventoryProducts', 'media'])`
2. Same for the fallback query in `ChatbotContextBuilder` line 59
3. In `formatProductLine()`, replace static availability with:
   ```php
   $totalStock = $product->variants->sum(fn($v) => $v->inventoryProducts->sum('quantity'));
   $availability = $totalStock > 0 ? "In Stock ({$totalStock})" : 'Out of Stock';
   ```
4. In `formatCurrentProduct()`, for each variant:
   ```php
   $qty = $variant->inventoryProducts->sum('quantity');
   $stockStatus = $qty > 0 ? "In Stock ({$qty})" : 'Out of Stock';
   ```
5. Add to system prompt STOCK RULES section telling bot to clearly say "out of stock" when applicable

**DB changes**: None
**Frontend changes**: None

---

## FEATURE 2: Gen AI Creativity

**Problem**: Temperature 0.1 in `GroqService.php` line 45. System prompt forces bot to dump MOQ/MOV on every response. Feels like FAQ database.

**Files to modify:**
- `app/Http/Services/GroqService.php` - make temperature/model configurable
- `app/Http/Services/ChatbotContextBuilder.php` - rewrite prompt tone
- `config/services.php` - add temperature/max_tokens config

**Steps:**
1. Modify `GroqService::chat()` to accept `array $options = []` for temperature/max_tokens/model overrides
2. Change default temperature from 0.1 to 0.4 via `config('services.groq.temperature', 0.4)`
3. Add to config/services.php: `'temperature' => env('GROQ_TEMPERATURE', 0.4)`
4. Rewrite system prompt RULE 3:
   - "Include MOQ and minimum order value ONLY when customer asks about ordering or pricing"
   - "Match response length to question complexity. Simple greetings get short replies."
   - "Be conversational and professional. Interpret ambiguous questions charitably."
5. Consider upgrading model to `llama-3.3-70b-versatile` via .env (configurable, no code change needed)

**DB changes**: None
**Frontend changes**: None

---

## FEATURE 4: Smart Search & Concise Responses

**Problem**: Semantic search threshold too low (0.12), returns weakly related products. No fuzzy text matching. Bot dumps all info regardless of question type.

**New files:**
- `app/Http/Services/FuzzyProductMatcher.php` - fuzzy name matching
- `app/Http/Services/ChatbotIntentDetector.php` - lightweight intent classification

**Files to modify:**
- `app/Http/Services/SemanticSearchService.php` - raise threshold to 0.20
- `app/Http/Services/ChatbotContextBuilder.php` - hybrid search, intent-aware detail levels, tiered product format
- `app/Http/Controllers/ChatbotController.php` - wire intent detector

**Steps:**
1. Create `FuzzyProductMatcher` - uses `similar_text()` + substring matching on product names (en/ar), returns scored Products
2. Create `ChatbotIntentDetector` - keyword/pattern matcher returning intent: `product_inquiry`, `purchase_intent`, `order_status`, `shipping_inquiry`, `greeting`, `comparison`
3. Raise semantic search threshold from 0.12 to 0.20
4. In `ChatbotContextBuilder`, merge semantic + fuzzy results, deduplicate, take top 10
5. Create tiered product format:
   - **Brief** (for general queries): `"1. [EXACT NAME: X] | From: $Y"`
   - **Detailed** (for purchase intent): full format with MOQ/MOV/variants
6. Intent-aware prompting: `greeting` intent = skip product catalog, `shipping_inquiry` = only shipping info

**DB changes**: None
**Frontend changes**: None

---

## FEATURE 3: Order Creation & User Navigation

**Problem**: Bot can't guide users to products, cart, or checkout. Can't answer detailed product questions.

**New files:**
- (Uses `ChatbotIntentDetector` from Feature 4)

**Files to modify:**
- `app/Http/Services/ChatbotContextBuilder.php` - add product URLs, navigation section
- `resources/frontend/src/components/chatbot/ChatMessage.tsx` - render markdown links

**Steps:**
1. In `formatProductLine()` add: `| Link: /products/{$product->slug}`
2. Add NAVIGATION section to system prompt:
   ```
   When customer wants to view a product, provide: [Product Name](/products/slug)
   When customer wants to checkout: direct to /checkout
   When customer wants cart: direct to /cart
   When customer wants orders: direct to /profile/orders
   Never claim you can create orders or add to cart. You guide users.
   ```
3. In `ChatMessage.tsx`, replace plain text `<p>` with a renderer that converts `[text](url)` to `<a href="url">text</a>` links. Simple regex, no markdown library needed:
   ```tsx
   const renderContent = (text: string) => {
     return text.split(/(\[.+?\]\(.+?\))/g).map((part, i) => {
       const match = part.match(/\[(.+?)\]\((.+?)\)/);
       if (match) return <a key={i} href={match[2]}>{match[1]}</a>;
       return <span key={i}>{part}</span>;
     });
   };
   ```

**DB changes**: None
**Frontend changes**: `ChatMessage.tsx` - markdown link rendering

---

## FEATURE 1: Memory & Conversation Context

**Problem**: System prompt rebuilds fresh each message. Only last 10 raw messages kept. No summarization. If user discussed a product 8 messages ago and now says "what about the blue one?", context is lost.

**New files:**
- `app/Http/Services/ConversationMemoryService.php`
- `database/migrations/xxxx_add_context_fields_to_chat_conversations.php`

**Files to modify:**
- `app/Http/Services/ChatbotContextBuilder.php` - add conversation context section
- `app/Http/Controllers/ChatbotController.php` - wire memory service
- `app/Models/ChatConversation.php` - add summary, topic_context to fillable/casts

**Steps:**
1. Migration: add `summary TEXT NULL`, `topic_context JSON NULL` to `chat_conversations`
2. Create `ConversationMemoryService`:
   - `buildContext(conversation, currentMessage)` - returns summary + previously discussed product slugs
   - `shouldSummarize(conversation)` - true every 8 user messages
   - `generateSummary(conversation)` - calls Groq with compact summarization prompt (~200 tokens cost)
   - `extractTopicSlugs(messages)` - regex/pattern to find product references in history
3. Modify `ChatbotContextBuilder::buildSystemPrompt()` to accept `?string $summary = null, array $topicSlugs = []`
4. Add CONVERSATION CONTEXT section to system prompt (between current product and available products)
5. When topic slugs exist, load those products explicitly alongside semantic search results
6. In controller, call memory service between saving user message and building prompt
7. After saving assistant response, check if summarization is needed and trigger if so

**DB changes**: `chat_conversations` + `summary`, `topic_context`
**Frontend changes**: None

---

## FEATURE 6: Personalized Responses (Per User)

**Problem**: Bot doesn't know user's name, purchase history, or preferences. `auth('sanctum')->id()` is used only for session ownership.

**New files:**
- `app/Http/Services/UserContextService.php`

**Files to modify:**
- `app/Http/Services/ChatbotContextBuilder.php` - add user context section
- `app/Http/Controllers/ChatbotController.php` - inject UserContextService

**Steps:**
1. Create `UserContextService::buildUserContext(int $userId): array`:
   - Returns: name, total_orders, last_order_date, total_spent, frequently_ordered_products (top 5), last_ordered_products
   - Uses existing Order, OrderItem, User models
   - Cache per user for 15 min: `Cache::remember("chatbot_user_ctx_{$userId}", 900, ...)`
2. In controller, if `$userId` exists, fetch user context and pass to context builder
3. Add CUSTOMER PROFILE section to system prompt:
   ```
   Name: {name}
   Total Orders: {count}
   Last Order: {date}
   Frequently Purchased: {products}

   PERSONALIZATION RULES:
   - Greet by first name
   - Reference purchase history when relevant
   - NEVER reveal email, phone, or other personal data
   ```
4. Optional frontend: show personalized welcome in `ChatWidget.tsx` using Redux auth state

**DB changes**: None (reads from existing orders, order_items, users)
**Frontend changes**: Optional personalized welcome message

---

## FEATURE 7: Super Admin Bot

**Problem**: No admin chatbot. Need a separate analytics bot for Super Admins in Filament panel.

**New files:**
- `app/Http/Controllers/AdminChatbotController.php`
- `app/Http/Services/AdminChatbotContextBuilder.php`
- `app/Http/Services/AdminDataQueryService.php`
- `app/Http/Services/AdminIntentRouter.php`
- `app/Filament/Pages/AdminAIChatbot.php`
- `resources/views/filament/pages/admin-ai-chatbot.blade.php`
- `database/migrations/xxxx_add_type_to_chat_conversations.php`

**Files to modify:**
- `routes/api.php` - add admin chatbot routes (auth:sanctum protected)
- `config/services.php` - add admin_model, admin_temperature config
- `app/Models/ChatConversation.php` - add type to fillable

**Steps:**
1. Migration: add `type ENUM('customer','admin') DEFAULT 'customer'` to chat_conversations
2. Create `AdminDataQueryService` with methods:
   - `getSalesOverview(period)` - revenue, order count, avg order value
   - `getTopProducts(limit, period)` - best sellers with revenue
   - `getTopCustomers(limit)` - highest spenders
   - `getInventoryAlerts()` - low/out of stock products
   - `getOrdersByStatus()` - pending/shipped/delivered counts
   - `lookupOrder(query)` - find by order ID or customer info
   - `lookupCustomer(query)` - search by name/email/phone
   - `getSalesTrends(months)` - monthly revenue trend
   - `getAgentPerformance()` - agent sales comparison
3. Create `AdminIntentRouter` - classifies admin query and calls relevant data methods
4. Create `AdminChatbotContextBuilder` - builds admin-specific system prompt with queried data
5. Create `AdminChatbotController` - same pattern as ChatbotController but:
   - Requires auth + Super Admin role check
   - Uses admin context builder + 70b model
   - Separate conversation type
6. Add routes: `POST /api/admin/chatbot/session`, `/send`, `GET /history`
7. Create Filament page with Blade/Alpine.js chat UI matching Filament design
8. Use `llama-3.3-70b-versatile` for admin bot (better reasoning for data analysis)
9. Admin bot system prompt: "Fashionista Analytics Assistant" with data-driven instructions

**DB changes**: `chat_conversations` + `type` column
**Frontend changes**: Filament admin page with chat UI (Blade + Alpine.js)

---

## Migration Summary

Single combined migration:
```
chat_conversations:
  + summary TEXT NULL (Feature 1)
  + topic_context JSON NULL (Feature 1)
  + type ENUM('customer','admin') DEFAULT 'customer' (Feature 7)
```

## New Files Summary (10 files)

| File | Feature |
|------|---------|
| `app/Http/Services/ConversationMemoryService.php` | 1 |
| `app/Http/Services/FuzzyProductMatcher.php` | 4 |
| `app/Http/Services/ChatbotIntentDetector.php` | 3, 4 |
| `app/Http/Services/UserContextService.php` | 6 |
| `app/Http/Controllers/AdminChatbotController.php` | 7 |
| `app/Http/Services/AdminChatbotContextBuilder.php` | 7 |
| `app/Http/Services/AdminDataQueryService.php` | 7 |
| `app/Http/Services/AdminIntentRouter.php` | 7 |
| `app/Filament/Pages/AdminAIChatbot.php` | 7 |
| `resources/views/filament/pages/admin-ai-chatbot.blade.php` | 7 |

## Modified Files Summary

| File | Features |
|------|----------|
| `app/Http/Controllers/ChatbotController.php` | 1, 3, 4, 5, 6 |
| `app/Http/Services/ChatbotContextBuilder.php` | 1, 2, 3, 4, 5, 6 (major rewrite) |
| `app/Http/Services/GroqService.php` | 2, 7 |
| `app/Http/Services/SemanticSearchService.php` | 4, 5 |
| `app/Models/ChatConversation.php` | 1, 7 |
| `config/services.php` | 2, 7 |
| `routes/api.php` | 7 |
| `resources/frontend/src/components/chatbot/ChatMessage.tsx` | 3 |

## Verification Plan

1. **Feature 5**: Ask bot about a product that's out of stock - should say "out of stock"
2. **Feature 2**: Ask "hi" - should get a short friendly reply, not a product dump
3. **Feature 4**: Misspell a product name - should still find it. Ask about shipping only - shouldn't get product MOQ
4. **Feature 3**: Ask "I want to buy X" - should get a link to the product page
5. **Feature 1**: Have a 10+ message conversation about one topic, then reference something from early on - bot should remember
6. **Feature 6**: Login and ask "what did I order last time?" - should know your history
7. **Feature 7**: Open admin panel chatbot, ask "what are this month's top selling products?" - should get real data

# المرحلة 5: AI Features

المشروع يحتوي على عدة ميزات ذكاء اصطناعي وتوصيات. هذه الميزات موزعة بين Groq للـ LLM، Jina للـ embeddings، وRecombee للتوصيات.

## خريطة الميزات

| الميزة | الملفات الأساسية | المزود |
| --- | --- | --- |
| Customer chatbot | `ChatbotController`, `ChatbotContextBuilder`, `GroqService` | Groq |
| Admin chatbot | `AdminChatbotController`, `AdminChatbotContextBuilder`, `AdminDataQueryService`, `AdminIntentRouter` | Groq + database context |
| Semantic search | `SearchController`, `CatalogSearchService`, `SemanticSearchService`, `JinaEmbeddingService` | Jina |
| Visual search | `SearchController`, `SemanticSearchService`, `JinaEmbeddingService` | Jina CLIP |
| Recommendations | `RecommendationController`, `RecombeeService`, `SendRecombeeInteractionJob` | Recombee |
| Prompt safety | `PromptInjectionDetector`, `MessageSanitizer`, `ResponseSanitizer` | Local rules + LLM response checks |
| Conversation memory | `ConversationMemoryService`, `ChatConversation`, `ChatMessage` | Database + Groq summary |

## إعدادات AI Providers

كل الإعدادات الأساسية موجودة في `config/services.php`:

| Provider | مفاتيح البيئة |
| --- | --- |
| Groq | `GROQ_API_KEY`, `GROQ_MODEL`, `GROQ_TEMPERATURE`, `GROQ_MAX_TOKENS`, `GROQ_ADMIN_MODEL`, `GROQ_ADMIN_TEMPERATURE` |
| Jina | `JINA_API_KEY`, `JINA_EMBEDDING_MODEL`, `JINA_CLIP_MODEL`, `JINA_API_URL` |
| Recombee | `RECOMBEE_DATABASE_ID`, `RECOMBEE_PRIVATE_TOKEN`, `RECOMBEE_PUBLIC_TOKEN`, `RECOMBEE_REGION` |

## Customer Chatbot

الـ customer chatbot يعمل من خلال endpoints:

- `POST /api/chatbot/session`
- `POST /api/chatbot/send`
- `GET /api/chatbot/history`

### إنشاء جلسة

`ChatbotController@initSession` ينشئ:

- `session_id` عشوائي 64 حرف.
- `user_id` إذا المستخدم authenticated.
- `ip_address`.
- `ua_hash`.
- `expires_at`.
- `type = customer`.

الضيف يحصل على session تنتهي بعد 72 ساعة، والمستخدم المسجل يحصل على session أطول.

### إرسال رسالة

`ChatbotController@sendMessage` ينفذ pipeline واضح:

1. validation للرسالة و`session_id`.
2. sanitize للرسالة باستخدام `MessageSanitizer`.
3. رفض الرسائل غير الصالحة.
4. prompt injection detection باستخدام `PromptInjectionDetector`.
5. التحقق من session ownership عبر user id أو IP + user agent hash.
6. التحقق من expiry.
7. تطبيق message limit لكل conversation.
8. حفظ رسالة المستخدم.
9. بناء memory context من `ConversationMemoryService`.
10. بناء user context للمستخدم المسجل من `UserContextService`.
11. بناء system prompt من `ChatbotContextBuilder`.
12. تحميل آخر 20 رسالة للمحادثة.
13. إرسال messages إلى `GroqService`.
14. تنظيف الرد من `ResponseSanitizer`.
15. فرض catalog/product guard من `ChatProductResponseGuard`.
16. تحويل أسماء المنتجات إلى links باستخدام `ChatProductLinker`.
17. حفظ رد assistant.
18. تسجيل audit event.
19. تلخيص conversation عند الحاجة.

### تعديل سلوك chatbot

| المطلوب | مكان التعديل |
| --- | --- |
| تغيير موديل Groq | `config/services.php` أو env `GROQ_MODEL`. |
| تعديل system prompt | `ChatbotContextBuilder@buildSystemPrompt`. |
| تغيير قواعد intent | `ChatbotIntentDetector`. |
| تغيير product context | `ChatbotContextBuilder` و`CatalogSearchService`. |
| تغيير prompt injection rules | `PromptInjectionDetector`. |
| تغيير response filtering | `ResponseSanitizer`. |
| تغيير قواعد إجبار الرد على منتجات موجودة | `ChatProductResponseGuard`. |
| تغيير مدة session | `ChatbotController@initSession`. |
| تغيير message limit | `MAX_MESSAGES_PER_CONVERSATION`. |

## Conversation Memory

`ConversationMemoryService` يحافظ على سياق المحادثة:

- `buildContext` يرجع summary وtopic slugs لاستخدامهم في prompt.
- `shouldSummarize` يحدد متى يجب تلخيص conversation.
- `generateSummary` يطلب من Groq تلخيص المحادثة ويحفظ summary وtopic context.

الفكرة أن chatbot لا يحمل كل التاريخ دائما، لكنه يحتفظ بملخص وموضوعات مهمة مثل product slugs.

## Admin Chatbot

الـ admin chatbot محمي داخل `auth:sanctum` ويستخدم:

- `AdminChatbotController`
- `AdminChatbotContextBuilder`
- `AdminIntentRouter`
- `AdminDataQueryService`
- `GroqService`

الـ endpoints:

- `POST /api/admin-chatbot/session`
- `POST /api/admin-chatbot/send`
- `GET /api/admin-chatbot/history`

### الصلاحيات

كل request يتحقق من super admin access باستخدام:

```text
Gate::forUser($user)->allows('superAdminAccess')
```

### بناء الرد

1. admin يرسل رسالة.
2. `AdminIntentRouter` يحلل النية.
3. `AdminDataQueryService` يجلب بيانات حقيقية من orders, customers, products, agents, inventory.
4. `AdminChatbotContextBuilder` يبني prompt يحتوي snapshot وrelevant context.
5. Groq يستخدم admin model الافتراضي `llama-3.3-70b-versatile`.
6. الرد يرجع مع `intent`.

### تعديل admin chatbot

| المطلوب | مكان التعديل |
| --- | --- |
| إضافة نوع سؤال إداري جديد | `AdminIntentRouter@route`. |
| إضافة data section جديد | `AdminDataQueryService`. |
| تعديل شكل prompt | `AdminChatbotContextBuilder`. |
| تغيير موديل الإدارة | env `GROQ_ADMIN_MODEL`. |
| تغيير temperature | env `GROQ_ADMIN_TEMPERATURE`. |

## Semantic Search

Semantic search يسمح بالبحث بمعنى السؤال وليس فقط مطابقة كلمات.

Endpoint:

```text
GET /api/semantic-search?query=...
```

التدفق:

1. `SearchController@semanticSearch` يستقبل query.
2. `CatalogSearchService@search` يجمع literal وsemantic candidates.
3. `SearchQueryUnderstandingService` قد يستخدم Groq لتحليل query إلى intent/terms/category clues.
4. `SemanticSearchService@search` ينشئ embedding للquery باستخدام Jina.
5. يتم مقارنة vector الخاص بالquery مع `ProductEmbedding.vector`.
6. النتائج التي تتجاوز threshold ترجع مرتبة حسب cosine similarity.
7. controller يرجع `literal_results`, `semantic_results`, `has_semantic`, و`industrial_categories`.

### تعديل semantic search

| المطلوب | مكان التعديل |
| --- | --- |
| تغيير embedding model | env `JINA_EMBEDDING_MODEL`. |
| تغيير API URL | env `JINA_API_URL`. |
| تغيير similarity threshold | `SemanticSearchService@search`. |
| تغيير ranking rules | `CatalogSearchService@rankProducts`. |
| تغيير AI query analysis | `SearchQueryUnderstandingService`. |

## Visual Search

Endpoint:

```text
POST /api/visual-search
```

المدخلات:

- file باسم `image`.
- أو `image_base64`.

التدفق:

1. controller يحول image إلى data URI إذا كانت file.
2. `SemanticSearchService@searchByImage` يطلب image embedding من Jina.
3. يقارن vector مع `ProductImageEmbedding.vector`.
4. يرجع products approved التي لها variants.
5. يرجع categories المرتبطة بالنتائج.

### تعديل visual search

| المطلوب | مكان التعديل |
| --- | --- |
| تغيير CLIP model | env `JINA_CLIP_MODEL`. |
| تغيير image threshold | `SemanticSearchService@searchByImage`. |
| تغيير عدد النتائج | parameter `limit`. |
| تغيير response shape | `SearchController@visualSearch`. |

## Recombee Recommendations

Recombee يستخدم للتوصيات وتتبع التفاعلات.

Endpoints:

- `GET /api/recommendations/user`
- `GET /api/recommendations/item/{productId}`
- `GET /api/recommendations/frequent/{productId}`
- `POST /api/cart/track`

### أنواع التوصيات

| النوع | method | الوصف |
| --- | --- | --- |
| توصيات للمستخدم | `recommendItemsToUser` | تعتمد على user interactions. |
| توصيات مشابهة لمنتج | `recommendItemsToItem` | تعتمد على item similarity وسياق المستخدم إن وجد. |
| Frequently bought together | `recommendItemsToItem` مع `ecommerce:cross-sell` | تستخدم لاقتراح منتجات تشترى معا. |

### التفاعلات المرسلة إلى Recombee

| التفاعل | method |
| --- | --- |
| مشاهدة تفاصيل | `addDetailView` |
| إضافة wishlist | `addBookmark` |
| إزالة wishlist | `removeBookmark` |
| إضافة cart | `addCartAddition` |
| شراء | `addPurchase` |
| مزامنة item | `syncItem` |

`SendRecombeeInteractionJob` يستقبل action مثل `detail_view`, `add_bookmark`, `remove_bookmark`, `cart_add`, `purchase` وينادي method المناسبة في `RecombeeService`.

### تعديل Recombee

| المطلوب | مكان التعديل |
| --- | --- |
| تغيير region أو database | env `RECOMBEE_REGION`, `RECOMBEE_DATABASE_ID`. |
| تغيير عدد النتائج | query parameter `count`. |
| تغيير logic لـ frequently bought together | `RecommendationController@frequentlyBoughtTogether`. |
| تغيير خصائص المنتج المرسلة | `RecombeeService@syncItem`. |
| إضافة interaction جديد | `SendRecombeeInteractionJob@handle` و`RecombeeService`. |

## Prompt Safety

المشروع يحمي chatbot بعدة طبقات:

- `MessageSanitizer` ينظف رسالة المستخدم ويتأكد أنها صالحة.
- `PromptInjectionDetector` يمنع محاولات تغيير دور النظام أو استخراج prompt.
- user messages يتم تغليفها قبل إرسالها للـ LLM لمنع role confusion.
- `ResponseSanitizer` يحاول منع تسريب system prompt أو sensitive content.
- `ChatProductResponseGuard` يمنع chatbot من نفي وجود منتجات مع أن الكتالوج يحتوي نتائج.
- `ChatAuditLogger` يسجل أحداث مثل session creation, injection detected, response filtered.

## حدود مهمة

- إذا لم يوجد `GROQ_API_KEY`، `GroqService` يجب أن يتعامل مع عدم توفر المزود.
- إذا لم يوجد `JINA_API_KEY`، embeddings ترجع فارغة، وبالتالي semantic/visual search قد يرجع نتائج فارغة.
- إذا لم يوجد Recombee config، `RecombeeService@getClient` يرجع `null` والعمليات لا ترسل للمزود.
- أي تعديل في prompts يجب اختباره ضد prompt injection والردود المختلطة عربي/إنجليزي.

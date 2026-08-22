# المرحلة 2: Project Architecture

المشروع يستخدم Laravel API architecture مبنية حول `Repository Pattern` مع `Services Layer`. هذا التقسيم يجعل الكود أوضح، أسهل في الاختبار، وأسهل في تعديل business rules بدون خلطها مع database queries أو HTTP responses.

## الصورة العامة

تدفق أغلب الطلبات يكون بهذا الشكل:

```text
Frontend
  -> ApiClient.ts
  -> routes/api.php
  -> Controller
  -> Form Request validation
  -> Service
  -> Repository
  -> Eloquent Models / Database
  -> API Resource / Response helper
  -> Frontend
```

## طبقة Routes

`routes/api.php` هي نقطة الدخول لمعظم وظائف التطبيق. الملف يقسم endpoints إلى:

- Public routes مثل login, register, products, search, shop filters, chatbot.
- Protected routes داخل `auth:sanctum` مثل wishlist, ratings, coupons, checkout, profile, agent dashboard.
- Agent-only routes باستخدام `EnsureAgent`.
- Checkout restriction باستخدام `PreventAgents`.
- Payment callbacks/webhooks.

أمثلة:

- `POST /api/auth/login` يذهب إلى `AuthController@login`.
- `GET /api/products/{slug}` يذهب إلى `ProductController@getProductBySlug`.
- `GET /api/semantic-search` يذهب إلى `SearchController@semanticSearch`.
- `POST /api/chatbot/send` يذهب إلى `ChatbotController@sendMessage`.
- `POST /api/checkout` يذهب إلى `PaymentController@stripeCheckout`.

## طبقة Controllers

الـ controllers مسؤولة عن التعامل مع HTTP فقط:

- قراءة request والـ route parameters.
- تنفيذ validation أو استخدام Form Requests.
- استدعاء service مناسب.
- إرجاع JSON response أو redirect.
- التعامل مع HTTP status codes.

الـ controller لا يجب أن يحتوي على database queries كبيرة أو business rules معقدة. في المشروع الحالي توجد بعض flows الكبيرة داخل `PaymentController` بسبب checkout creation، لكن باقي الأجزاء تميل إلى استخدام services بوضوح.

أمثلة controllers:

- `AuthController`: auth endpoints وإدارة refresh cookie.
- `ProductController`: home/products/custom product requests.
- `SearchController`: literal, semantic, visual search.
- `ChatbotController`: customer chatbot session/message/history.
- `AdminChatbotController`: admin AI assistant.
- `AgentManualOrderController`: manual sales flow للوكيل.
- `ProfileController`: profile/orders/invoice/agent activity.
- `PaymentController`: coupons, shipping, checkout, callbacks.

## طبقة Services

الـ services تمثل business logic:

- تطبيق قواعد المنتج.
- تنسيق data بين repositories وcontrollers.
- إدارة integrations الخارجية.
- بناء prompts أو context للذكاء الاصطناعي.
- معالجة tokens, coupons, ratings, checkout helpers.

أمثلة:

- `AuthService` يصدر token pair ويطلب من `UserRepository` تنفيذ عمليات المستخدم.
- `ProductService` يحول product repository results إلى resources.
- `PaymentService` يطبق قواعد الكوبونات والشحن والحد الأدنى للطلب.
- `CatalogSearchService` يجمع literal search وsemantic search وAI query understanding.
- `ChatbotContextBuilder` يبني system prompt من catalog context وmemory وuser context.
- `RecombeeService` يغلف Recombee SDK.

## طبقة Repositories

الـ repositories مسؤولة عن data access:

- Eloquent queries.
- joins/aggregations.
- pagination.
- filtering.
- تحميل العلاقات المطلوبة.
- حفظ أو تحديث records البسيطة.

هذا يمنع تكرار queries داخل controllers/services ويسهل اختبار queries بشكل مستقل.

أمثلة:

- `ProductRepository`: products listing, top products, product details, custom requests.
- `ShopPageRepository`: filter products by category, price, stock, semantic ids.
- `PaymentRepository`: coupon state, pending coupons, shipping cost.
- `AgentDashboardRepository`: agent metrics, sales performance, commissions.
- `ProfileRepository`: user order summaries, agent summaries, invoice order lookup.
- `SearchRepository`: keyword search.
- `UserRepository`: users, login checks, Sanctum token lookup.

## Models and Resources

الـ models تمثل domain entities وعلاقاتها:

- `Product` و`ProductVariant` للكتالوج والأسعار.
- `Order` و`OrderItem` للطلبات.
- `Inventory` و`InventoryProduct` للمخزون.
- `User`, `Role`, `RoleUser` للمستخدمين والصلاحيات.
- `ChatConversation`, `ChatMessage`, `ChatAuditLog` للمحادثات والـ audit.
- `ProductEmbedding`, `ProductImageEmbedding` للبحث بالـ embeddings.

الـ resources تحافظ على شكل response:

- `ProductResource`
- `UserResource`
- `CouponResource`
- موارد أخرى داخل `app/Http/Resources`

## Frontend Architecture

الواجهة تنظم الكود في:

- `pages`: صفحات التطبيق مثل shop, profile, dashboard, login.
- `components`: مكونات مشتركة.
- `auth`: route guards وحماية dashboard/checkout.
- `store`: Redux slices مثل user, cart, filters, coupon, chatbot.
- `i18n`: الترجمة واللغة.
- `ApiClient.ts`: طبقة واحدة لكل API calls.

`ApiClient.ts` مهم لأنه:

- يضبط `baseURL` على `/api/`.
- يرسل `Accept-Language`.
- يضيف access token إلى `Authorization`.
- يجدد access token عند 401 باستخدام refresh cookie.
- يطلق event باسم `auth:session-expired` عند فشل التجديد.

## لماذا هذا التصميم جيد؟

| السبب | الفائدة |
| --- | --- |
| فصل HTTP عن business logic | تعديل rules داخل services بدون لمس routing/response كثيرا. |
| فصل business logic عن queries | repositories تجعل data access واضحا وقابلا للاختبار. |
| سهولة الاختبار | يمكن اختبار services وrepositories منفصلين، وهذا واضح في `tests/Services` و`tests/Repositories`. |
| قابلية التوسعة | إضافة provider جديد أو search strategy جديد يتم غالبا داخل service محدد. |
| قابلية التوثيق | كل function لها طبقة ومسؤولية واضحة. |
| تقليل التكرار | queries المشتركة مثل coupon state أو product filters موجودة في repository واحد. |

## قواعد إضافة Feature جديدة

عند إضافة feature جديدة يفضل اتباع هذا التسلسل:

1. أضف route في `routes/api.php`.
2. أضف Form Request إذا كان input معقدا.
3. اجعل controller رفيعا قدر الإمكان.
4. ضع business rules في service.
5. ضع Eloquent queries في repository.
6. أرجع response من resource عند وجود model أو collection.
7. أضف API method في `ApiClient.ts`.
8. أضف tests تحت `tests/Services` أو `tests/Repositories` حسب الطبقة التي يتم اختبارها.

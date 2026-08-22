# المرحلة 1: Tech Stack

هذا القسم يوضح التقنيات الأساسية المستخدمة في المشروع، ودور كل تقنية داخل التطبيق.

## Backend

المشروع يستخدم Laravel كـ backend رئيسي:

| التقنية | الاستخدام |
| --- | --- |
| PHP `^8.2` | لغة backend الأساسية المطلوبة لتشغيل Laravel 12. |
| Laravel Framework `^12.0` | بناء API, routing, validation, Eloquent ORM, queues, jobs, events, middleware. |
| Laravel Sanctum `^4.0` | إصدار access tokens وrefresh tokens وحماية routes بـ `auth:sanctum`. |
| Eloquent ORM | التعامل مع الجداول والعلاقات مثل `Product`, `Order`, `User`, `Coupon`, `InventoryProduct`. |
| Form Requests | validation للطلبات المهمة مثل login, register, checkout, shipping, manual orders. |
| API Resources | توحيد شكل response للـ frontend مثل `ProductResource`, `UserResource`, `CouponResource`. |
| Jobs/Queues | تشغيل أعمال غير متزامنة مثل Recombee interactions وتحديث إنجازات الوكلاء. |

ملفات مرجعية:

- `composer.json`
- `routes/api.php`
- `app/Http/Controllers`
- `app/Http/Services`
- `app/Http/Repositories`
- `app/Jobs`

## Admin Panel

المشروع يستخدم Filament لإدارة البيانات من لوحة تحكم داخلية:

| الحزمة | الاستخدام |
| --- | --- |
| `filament/filament` | بناء admin resources/pages. |
| `filament/spatie-laravel-media-library-plugin` | إدارة صور ووسائط المنتجات داخل Filament. |
| `filament/spatie-laravel-translatable-plugin` | دعم الحقول متعددة اللغة في لوحة الإدارة. |
| `bezhansalleh/filament-language-switch` | تغيير لغة واجهة Filament. |

أمثلة resources:

- `ProductResource`
- `OrderResource`
- `InventoryResource`
- `CouponResource`
- `SmartBundleResource`
- `AdminAIChatbot`

## Frontend

الواجهة موجودة داخل `resources/frontend/src` وتستخدم React مع Vite:

| التقنية | الاستخدام |
| --- | --- |
| React `^19.1.0` | بناء صفحات ومكونات الواجهة. |
| React Router DOM `^7.6.0` | routing داخل الواجهة وحماية الصفحات. |
| Redux Toolkit `^2.8.2` | إدارة state مثل user, cart, filters, chatbot, coupons. |
| Redux Persist | حفظ أجزاء من state بين refresh sessions. |
| Axios `^1.9.0` | تنفيذ API requests ومعالجة auth interceptors. |
| Vite `^6.3.5` | build/dev server للواجهة. |
| TypeScript `~5.8.3` | typing للواجهة. |
| Tailwind CSS `^4.1.11` | styling. |
| Framer Motion | animations. |
| i18next/react-i18next | الترجمة وتحديد `Accept-Language`. |
| Recharts | dashboards/charts. |
| Swiper | carousels/sliders. |
| xlsx | التعامل مع spreadsheets في الواجهة. |

ملفات مرجعية:

- `package.json`
- `resources/frontend/src/ApiClient.ts`
- `resources/frontend/src/store`
- `resources/frontend/src/auth`
- `resources/frontend/src/pages`
- `resources/frontend/src/components`

## Database and Storage

المشروع يعتمد على Eloquent migrations لتعريف الكيانات. أهم الكيانات:

| المجال | Models |
| --- | --- |
| المستخدمون والصلاحيات | `User`, `Role`, `RoleUser`, `Agent`, `AgentMonthlyTarget`, `Bonus` |
| الكتالوج | `Product`, `ProductVariant`, `VariantType`, `IndustryCategory`, `Supplier` |
| المخزون | `Inventory`, `InventoryProduct` |
| الطلبات والدفع | `Order`, `OrderItem`, `Coupon`, `ShippingDestination` |
| التفاعل والذكاء الاصطناعي | `ProductEmbedding`, `ProductImageEmbedding`, `ChatConversation`, `ChatMessage`, `ChatAuditLog` |
| التجربة الشرائية | `WishlistItem`, `CustomProduct`, `SmartBundle`, `ProductRating` |

الـ testing environment يستخدم SQLite in-memory حسب `phpunit.xml`:

- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`

## Authentication

المصادقة مبنية على Laravel Sanctum، لكنها لا تعتمد فقط على access token عادي. النظام يصدر token pair:

| النوع | العمر | التخزين | الاستخدام |
| --- | --- | --- | --- |
| `User Access Token` | 15 دقيقة | memory داخل frontend | إرسال `Authorization: Bearer ...` مع الطلبات المحمية. |
| `User Refresh Token` | 30 يوم | HTTP-only cookie باسم `refresh_token` | تجديد access token بدون كشف refresh token في JavaScript. |

ملفات مرجعية:

- `AuthController`
- `AuthService`
- `UserRepository`
- `resources/frontend/src/ApiClient.ts`
- `resources/frontend/src/store/slices/userSlice.ts`

## Payments and Messaging

| الخدمة | الاستخدام |
| --- | --- |
| Stripe | إنشاء checkout sessions، callbacks، webhooks، وتحديث orders/inventory. |
| Brevo | إرسال رسائل contact form عندما يكون API key متاحا. |
| DomPDF | إنشاء invoices كـ PDF. |

ملفات مرجعية:

- `PaymentController`
- `PaymentService`
- `StripePaymentService`
- `TapPaymentService`
- `InvoiceService`
- `HomePageService`
- `config/services.php`

## AI and Recommendations

| المزود | الاستخدام |
| --- | --- |
| Groq | تشغيل customer chatbot وadmin chatbot. |
| Jina Embeddings | إنشاء text/image embeddings للبحث semantic/visual. |
| Recombee | توصيات للمستخدم، توصيات بناء على منتج، frequently bought together، وتتبع التفاعلات. |

ملفات مرجعية:

- `GroqService`
- `JinaEmbeddingService`
- `SemanticSearchService`
- `CatalogSearchService`
- `ChatbotController`
- `AdminChatbotController`
- `RecombeeService`
- `SendRecombeeInteractionJob`

## Testing

الاختبارات مقسمة حاليا حسب طبقة التطبيق:

| Test Suite | المسار | الهدف |
| --- | --- | --- |
| `Services` | `tests/Services` | اختبار service layer وfeature flows التي تعتمد على services. |
| `Repositories` | `tests/Repositories` | اختبار data access, model behavior, repository queries. |

تشغيل الاختبارات:

```bash
php artisan test
```

أو:

```bash
php artisan test --testsuite=Services
php artisan test --testsuite=Repositories
```

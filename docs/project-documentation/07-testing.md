# Testing Guide

هذا الملف يشرح نظام الاختبارات الحالي في المشروع: كيف تم تقسيمه، كيف يعمل `phpunit.xml`، متى نضيف اختبار تحت `tests/Services` أو `tests/Repositories`، وكيف نشغل الاختبارات أثناء التطوير.

## الصورة العامة

الاختبارات مقسمة حسب الطبقة التي يتم اختبارها، وليس حسب التقسيم التقليدي `Unit` و`Feature` في جذر `tests`.

المجلدات الحالية داخل `tests`:

| المجلد | الدور |
| --- | --- |
| `tests/Services` | كل الاختبارات التي تختبر service behavior أو flow يبدأ من controller/API لكن جوهره business logic/service. |
| `tests/Repositories` | كل الاختبارات التي تختبر repository queries أو model/data behavior. |
| `tests/Support` | test base classes وfixtures وSQLite helpers. ليس مكانا لملفات `*Test.php` الأساسية. |
| `tests/Concerns` | traits مشتركة للاختبارات. ليس مكانا لملفات test مباشرة. |

القاعدة العملية:

```text
أي ملف *Test.php يجب أن يكون تحت tests/Services أو tests/Repositories فقط.
```

## phpunit.xml

ملف `phpunit.xml` يعرف test suites بهذا الشكل:

| Suite | المسار | الاستخدام |
| --- | --- | --- |
| `Services` | `tests/Services` | تشغيل service tests وservice-backed feature flows. |
| `Repositories` | `tests/Repositories` | تشغيل repository/model/data-access tests. |

بيئة الاختبار تستخدم:

| الإعداد | القيمة |
| --- | --- |
| `APP_ENV` | `testing` |
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | `:memory:` |
| `CACHE_STORE` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `SESSION_DRIVER` | `array` |
| `MAIL_MAILER` | `array` |

هذا يجعل الاختبارات معزولة وسريعة نسبيا، ولا تعتمد على database خارجية.

## طريقة التشغيل

تشغيل كل الاختبارات:

```bash
php artisan test
```

تشغيل اختبارات services فقط:

```bash
php artisan test --testsuite=Services
```

تشغيل اختبارات repositories فقط:

```bash
php artisan test --testsuite=Repositories
```

تشغيل ملف معين:

```bash
php artisan test tests/Services/Unit/Auth/AuthServiceAuthenticationTest.php
```

تشغيل test باسم أو filter:

```bash
php artisan test --filter=AuthRefreshCookieFlowTest
```

## تقسيم tests/Services

`tests/Services` يحتوي على نوعين:

| المسار | الاستخدام |
| --- | --- |
| `tests/Services/Unit` | اختبار service class أو business rule مباشرة. |
| `tests/Services/Feature` | اختبار flow أكبر يستخدم HTTP/API أو أكثر من service، لكن الغرض الأساسي منه إثبات service behavior. |

أمثلة `Services/Unit`:

- `AuthServiceAuthenticationTest`: يختبر register/login token issuing.
- `PaymentServiceCouponTest`: يختبر قواعد الكوبون داخل service.
- `InventoryAllocationServiceTest`: يختبر خصم المخزون.
- `SemanticSearchServiceTest`: يختبر search بالـ embeddings.
- `ChatbotContextBuilderTest`: يختبر بناء prompt/context.

أمثلة `Services/Feature`:

- `AuthEndpointsTest`: يختبر auth endpoints لأن flow فعلي من controller إلى `AuthService`.
- `AuthRefreshCookieFlowTest`: يختبر access/refresh token flow مع cookies.
- `AgentManualOrderSubmissionTest`: يختبر manual order flow من API إلى service.
- `CatalogSearchServiceTest`: يختبر AI-aware catalog search behavior.
- `CouponApplyUsageCountTest`: يختبر coupon flow من endpoint إلى service/repository.

## متى أضع الاختبار تحت Services؟

ضع الاختبار تحت `tests/Services` عندما يكون محور الاختبار:

- business rule داخل service.
- flow من API إلى service.
- integration مع provider خارجي لكنه mockable مثل Groq, Jina, Stripe, Tap, Recombee.
- validation لسلوك endpoint يعتمد أساسا على service.
- prompt/context/search/ranking logic.
- auth token issuing/refresh/logout behavior.

أمثلة:

| الحالة | المكان المناسب |
| --- | --- |
| اختبار `AuthService@loginUser` | `tests/Services/Unit/Auth` |
| اختبار endpoint `/api/auth/refresh-token` | `tests/Services/Feature/Auth` |
| اختبار `InventoryAllocationService@allocate` | `tests/Services/Unit/Inventory` |
| اختبار chatbot response guard | `tests/Services/Unit/Chat` |
| اختبار visual search flow | `tests/Services/Feature/Search` أو `tests/Services/Unit/Search` حسب مستوى الاختبار |

## تقسيم tests/Repositories

`tests/Repositories` يحتوي على اختبارات data layer:

- repository queries.
- model scopes/accessors/mutators.
- aggregation logic.
- pagination/data formatting من repository.
- SQLite-specific query compatibility.

أمثلة:

- `ProductRepositoryCatalogTest`: يختبر product catalog queries.
- `ShopPageRepositoryFilterTest`: يختبر filters على products.
- `PaymentRepositoryCouponStateTest`: يختبر coupon pivot state.
- `AgentDashboardRepositoryDashboardTest`: يختبر dashboard aggregations.
- `OrderModelTest`: يختبر model behavior/scopes.

## متى أضع الاختبار تحت Repositories؟

ضع الاختبار تحت `tests/Repositories` عندما يكون محور الاختبار:

- query أو aggregation.
- model scope أو accessor أو mutator.
- repository method.
- database relationship loading.
- pagination أو sorting من database.
- coupon/user/order pivot state.

أمثلة:

| الحالة | المكان المناسب |
| --- | --- |
| اختبار `ProductRepository@getAllProducts` | `tests/Repositories/Products` |
| اختبار `ShopPageRepository@filterProducts` | `tests/Repositories/Shop` |
| اختبار `PaymentRepository@releaseExpiredPendingCoupons` | `tests/Repositories/Payments` |
| اختبار `Order::manualSales()` scope | `tests/Repositories/Models` أو `tests/Repositories/Orders` |
| اختبار agent dashboard totals | `tests/Repositories/Agent` |

## Support and Concerns

### `tests/Support/ServiceSmokeTestCase.php`

Base class لاختبارات services. يوفر:

- `test_service_can_be_instantiated`.
- استخدام `InstantiatesServices`.
- تكامل Mockery مع PHPUnit.

يستخدم عندما نريد التأكد أن service يمكن إنشاؤه مع dependencies mockable، ثم نضيف tests فعلية فوق ذلك.

### `tests/Concerns/InstantiatesServices.php`

Trait يساعد على إنشاء service تلقائيا من خلال reflection:

- يقرأ constructor dependencies.
- ينشئ Mockery mocks للـ dependencies.
- يسمح باستدعاء `mockDependency` لاستخدام نفس mock داخل test.

مفيد في service unit tests التي تريد عزل repository/provider dependencies.

### `tests/Support/RepositoryTestCase.php`

Base class لاختبارات repositories. يوفر:

- `RefreshDatabase`.
- تسجيل SQLite helper functions عبر `RegistersRepositorySqliteFunctions`.

يستخدم لأن بعض repository queries تعتمد على SQL functions قد لا تكون متوفرة افتراضيا في SQLite.

### Fixture Traits

مجلد `tests/Support/Repositories` و`tests/Support/Services` يحتوي traits لبناء fixtures مشتركة مثل:

- agent dashboard fixtures.
- product repository fixtures.
- profile orders fixtures.
- shop page products.
- admin data query fixtures.

استخدم fixture trait عندما تتكرر نفس setup data في أكثر من test داخل نفس المجال.

## Naming Convention

اتبع هذا النمط:

```text
<ClassOrFeature><Behavior>Test.php
```

أمثلة جيدة:

- `AuthServiceAuthenticationTest`
- `PaymentRepositoryCouponStateTest`
- `AgentManualOrderServiceStoreTest`
- `ShopPageRepositoryFilterTest`
- `ChatProductResponseGuardTest`

يفضل تقسيم class كبيرة إلى أكثر من ملف حسب behavior بدل ملف واحد ضخم.

## كتابة Test جديد

خطوات مقترحة:

1. حدد الطبقة التي تختبرها: service أم repository.
2. اختر المسار حسب domain: `Auth`, `Payments`, `Products`, `Search`, `Agent`, وهكذا.
3. استخدم base class المناسب:
   - `Tests\Support\ServiceSmokeTestCase` لاختبارات services المعزولة.
   - `Tests\Support\RepositoryTestCase` لاختبارات repositories.
   - `Tests\TestCase` للـ feature/API tests عند الحاجة.
4. استخدم `RefreshDatabase` إذا كان الاختبار يكتب أو يقرأ database.
5. استخدم factories لبناء البيانات.
6. اجعل اسم test يصف السلوك المتوقع بوضوح.
7. شغل الملف وحده، ثم suite المناسبة.

مثال مسار service:

```text
tests/Services/Unit/Payments/PaymentServiceCouponTest.php
```

مثال مسار repository:

```text
tests/Repositories/Payments/PaymentRepositoryCouponStateTest.php
```

## قواعد مهمة

- لا تضف ملفات `*Test.php` مباشرة تحت `tests/Feature` أو `tests/Unit`.
- لا تضف ملفات test خارج `tests/Services` و`tests/Repositories`.
- اترك `tests/Support` و`tests/Concerns` للـ helpers فقط.
- عند اختبار service، حاول mock dependencies الخارجية بدل الاتصال الحقيقي.
- عند اختبار repository، استخدم database حقيقية داخل test environment ولا mock الـ query نفسه.
- عند اختبار AI providers، اختبر behavior المتوقع مع mocks/fakes ولا تعتمد على مفاتيح API حقيقية.
- عند اختبار payments، لا ترسل طلبات فعلية إلى Stripe/Tap؛ استخدم mocks أو SDK fakes.
- بعد نقل أي test، حدث namespace ليطابق مساره الجديد.

## Acceptance Checklist

قبل اعتبار test change جاهز:

- الملف موجود تحت `tests/Services` أو `tests/Repositories`.
- namespace يبدأ بـ `Tests\Services` أو `Tests\Repositories`.
- لا توجد dependencies على API keys حقيقية.
- الاختبار ينجح منفردا.
- suite المناسبة تنجح.
- `php artisan test` ينجح إذا كان التغيير مؤثر أو واسع.

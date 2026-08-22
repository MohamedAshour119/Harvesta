# المرحلة 4: Auth Process

هذا القسم يشرح عملية المصادقة من البداية إلى النهاية، من Laravel backend إلى React frontend.

## المكونات الأساسية

| الملف | المسؤولية |
| --- | --- |
| `routes/api.php` | تعريف auth endpoints العامة والمحمية. |
| `AuthController` | استقبال الطلبات، إدارة refresh cookie، وإرجاع responses. |
| `AuthService` | تنفيذ register/login/logout/refresh/reset وإصدار token pair. |
| `UserRepository` | إنشاء المستخدم، التحقق من كلمة المرور، lookup/verify Sanctum tokens. |
| `RegisterUserRequest` و`LoginUserRequest` | validation للمدخلات. |
| `UserResource` | توحيد شكل بيانات المستخدم في responses. |
| `resources/frontend/src/ApiClient.ts` | Axios instance، access token memory، refresh handling. |
| `resources/frontend/src/store/slices/userSlice.ts` | حفظ user state وauth flags في Redux. |
| `resources/frontend/src/auth` | route guards وحماية dashboard/checkout. |

## Endpoints

| Endpoint | Controller | الهدف |
| --- | --- | --- |
| `POST /api/auth/register` | `AuthController@register` | إنشاء user جديد وإصدار access/refresh tokens. |
| `POST /api/auth/login` | `AuthController@login` | تسجيل الدخول وإصدار token pair. |
| `POST /api/auth/logout` | `AuthController@logout` | حذف tokens وإنهاء refresh cookie. |
| `POST /api/auth/refresh-token` | `AuthController@refreshToken` | تجديد access token باستخدام refresh token. |
| `POST /api/auth/verify-token` | `AuthController@verifyToken` | التحقق من access token مرسل في body. |
| `POST /api/auth/consume-payload` | `AuthController@consumePayload` | فك payload مشفر يحتوي tokens. |
| `POST /api/auth/consume-impersonation-payload` | `AuthController@consumeImpersonationPayload` | استقبال payload impersonation مشفر. |
| `POST /api/auth/forgot-password` | `AuthController@forgotPassword` | إرسال reset link. |
| `POST /api/auth/reset-password` | `AuthController@resetPassword` | تغيير password باستخدام token. |

## Token Pair

عند register أو login، `AuthService` يستدعي `issueTokenPair` ويصدر:

| Token | الاسم داخل Sanctum | العمر | مكان التخزين | الاستخدام |
| --- | --- | --- | --- | --- |
| Access token | `User Access Token` | 15 دقيقة | متغير memory داخل `ApiClient.ts` | إرسال `Authorization: Bearer ...`. |
| Refresh token | `User Refresh Token` | 30 يوم | HTTP-only cookie باسم `refresh_token` | تجديد access token. |

مهم: الـ response العام يخفي `refresh_token.plain_token` عن JavaScript. `AuthController@publicAuthPayload` يجعله `null` قبل إرسال JSON، بينما `makeRefreshCookie` يضع القيمة الحقيقية في cookie.

## Register Flow

1. الواجهة تستدعي `ApiClient.auth.register`.
2. الطلب يذهب إلى `POST /api/auth/register`.
3. `RegisterUserRequest` يتحقق من البيانات.
4. `AuthController@register` يمرر البيانات إلى `AuthService@registerUser`.
5. `AuthService` يستدعي `UserRepository@create`.
6. `UserRepository` ينشئ user ويعمل hash للـ password.
7. `AuthService` يصدر access/refresh token pair.
8. `AuthController` يرجع user وaccess token في JSON، ويضع refresh token في HTTP-only cookie.
9. الواجهة تحفظ access token في memory وتحدث Redux user state.

## Login Flow

1. الواجهة تستدعي `ApiClient.auth.login`.
2. `LoginUserRequest` يتحقق من email/password.
3. `UserRepository@loginUser` يبحث عن user بالـ email.
4. يتم التحقق من password باستخدام `Hash::check`.
5. يتم تحديث `last_login_at`.
6. `AuthService` يصدر token pair جديد.
7. refresh token يذهب إلى cookie، access token يعود في response.

## Protected Requests

عند وجود access token في memory:

1. Axios request interceptor يضيف header:

```text
Authorization: Bearer <access-token>
```

2. request آخر يضيف:

```text
Accept-Language: <current-i18n-language>
```

3. Laravel routes داخل `auth:sanctum` تستخدم Sanctum للتحقق من token.

أمثلة protected endpoints:

- `GET /api/wishlist`
- `POST /api/ratings`
- `POST /api/apply-coupon`
- `POST /api/checkout`
- `GET /api/profile`
- `GET /api/agents/dashboard-info`

## Refresh Flow

يحدث refresh بطريقتين:

- scheduled refresh قبل انتهاء access token بخمس دقائق تقريبا.
- automatic refresh عند استلام response status `401`.

التسلسل:

1. `ApiClient.ts` يستدعي `POST /api/auth/refresh-token` مع `withCredentials: true`.
2. المتصفح يرسل `refresh_token` cookie تلقائيا.
3. `AuthController@refreshToken` يقرأ refresh token من cookie أو body.
4. `AuthService@refreshToken` يستدعي `UserRepository@refreshToken`.
5. `UserRepository` يتحقق من:
   - token format يحتوي على `id|token`.
   - token موجود في `personal_access_tokens`.
   - name هو `User Refresh Token`.
   - token لم ينته.
   - hash مطابق.
6. refresh token القديم يتم حذفه.
7. access tokens القديمة للمستخدم يتم حذفها.
8. `AuthService` يصدر token pair جديد.
9. frontend يحدث access token في memory.
10. الطلب الذي فشل بـ `401` يعاد مرة واحدة بالـ access token الجديد.

## Logout Flow

`AuthController@logout` يدعم حالتين:

- إذا كان المستخدم authenticated بالـ access token: يحذف كل tokens الخاصة به.
- إذا لا يوجد access token لكن يوجد refresh cookie: يستخدم refresh token لحذف tokens.

بعد logout:

- response يرجع success.
- refresh cookie يتم حذفها باستخدام expired cookie.
- الواجهة تستدعي `clearAuthTokens`.
- Redux user state يرجع غير authenticated.

## Session Expiration في الواجهة

إذا فشل refresh:

1. `ApiClient.ts` يمسح access token والـ legacy localStorage keys.
2. يطلق browser event:

```text
auth:session-expired
```

3. components أو route guards يمكنها الاستجابة للحدث وإخراج المستخدم أو فتح login prompt.

## Password Reset

Forgot password:

1. `POST /api/auth/forgot-password`.
2. `AuthService@sendPasswordResetLink`.
3. Laravel Password broker يرسل reset link.

Reset password:

1. `POST /api/auth/reset-password`.
2. validation: token, email, password, password_confirmation.
3. `AuthService@resetPassword`.
4. password يتم hash.
5. remember token يتغير.
6. `PasswordReset` event يتم إطلاقه.

## Verify Token

`POST /api/auth/verify-token` يستقبل token في body. `UserRepository@verifyToken` يتحقق من:

- format.
- وجود token.
- name هو `User Access Token`.
- عدم انتهاء الصلاحية.
- hash مطابق.
- وجود user.

يرجع user وaccess token metadata وrefresh token expiry إذا كان موجودا.

## Consume Payload

`consumePayload` يستخدم private key في `storage/keys/private.pem` لفك payload مشفر يحتوي access/refresh tokens. بعد فك التشفير:

- يتحقق من access token.
- يضيف refresh token plaintext مؤقتا داخل result.
- يحدث `last_login_at`.
- يرجع public auth payload ويضع refresh cookie.

## Impersonation Payload

`consumeImpersonationPayload` يستخدم hybrid encryption:

- payload خارجي base64 JSON يحتوي `key`, `iv`, `data`.
- private key يفك symmetric key.
- AES-256-CBC يفك البيانات.
- يتم التحقق من `exp`.
- يتم تحميل user.
- ترجع tokens الموجودة في payload ويتم وضع refresh cookie.

هذا flow يستخدم مع endpoint:

```text
POST /api/auth/consume-impersonation-payload
```

ويوجد endpoint لإنشاء impersonation من جهة الوكيل:

```text
POST /api/agents/{user}/impersonate
```

## Frontend Route Guards

ملفات `resources/frontend/src/auth` تضيف حماية في الواجهة:

| الملف | الدور |
| --- | --- |
| `RequireAuthRoute.tsx` | يمنع دخول صفحات تحتاج login. |
| `RequireNotAuthRoute.tsx` | يمنع المستخدم المسجل من صفحات login/register. |
| `DashboardAuthRoute.tsx` | يحمي dashboard حسب حالة المستخدم. |
| `PreventAgentCheckoutRoute.tsx` | يمنع agents من checkout. |
| `AgentCheckoutBlocker.tsx` | يعرض/يدير منع checkout للوكيل. |

## نقاط تعديل auth

| المطلوب | مكان التعديل |
| --- | --- |
| تغيير مدة access token | `AuthService@issueTokenPair`. |
| تغيير مدة refresh token | `AuthService@issueTokenPair` و`AuthController@makeRefreshCookie`. |
| تغيير اسم cookie | ثابت `REFRESH_TOKEN_COOKIE` في `AuthController`. |
| تغيير SameSite/Secure/HTTP-only policy | `AuthController@makeRefreshCookie` و`forgetRefreshCookie`. |
| تغيير refresh retry behavior | `ApiClient.ts` response interceptor. |
| تغيير user state | `userSlice.ts`. |

# تقرير شامل: معالجة وتعريب رسالة نجاح إعادة تعيين كلمة المرور (Forgot Password Localization)

**التاريخ:** 04 سبتمبر 2026  
**المشروع:** منصة هارفستا (Harvesta)  
**المكونات المعنية:** `lang/ar/passwords.php`, `lang/en/passwords.php`, `LoginPrompt.tsx`, `AuthController.php`  

---

## 1. 💡 الشرح النظري وتجربة المستخدم (Theoretical & Conceptual Overview)

### 🎯 الهدف والدافع (Purpose & Motivation)
الهدف من هذا التعديل هو ضمان اكتمال تجربة المستخدم باللغة العربية بنسبة 100% دون ظهور أي نصوص إنجليزية شاذة أو غير مترجمة داخل الواجهات العربية. في الشاشة المنبثقة لإعادة تعيين كلمة المرور، بعد قيام المستخدم بإدخال بريده الإلكتروني والضغط على زر الإرسال، كانت تظهر رسالة النجاح في مربع أخضر بالنص الإنجليزي:
> `.We have emailed your password reset link`  
بينما الواجهة بالكامل عربية، مما يسبب تشويهاً بصرياً لاتجاه النص (RTL) ويخل بتناسق الهوية اللغوية للمنصة.

### 🌟 لماذا قمنا بهذا التعديل (Why This Change Matters)
1. **الاحترافية والتماسك البصري (Visual Cohesion & RTL Alignment):** ظهور جملة إنجليزية تبدأ بنقطة معكوسة بسبب محاذاة النص من اليمين لليسار يعطي انطباعاً بوجود نقص في جودة النظام. ظهور النص العربي الصريح: `"تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني"` يمنح المستخدم وضوحاً كاملاً وراحة نفسية.
2. **معالجة السبب الجذري في الباك إند والفرونت إند معاً (End-to-End Localization):** لم نكتفِ فقط بتعديل واجهة المستخدم (Client-side)، بل قمنا بإنشاء ملفات التعريب الرسمية الخاصة بلارافيل (`passwords.php`) في الباك إند، مما يضمن أن أي استدعاء للـ API من أي عميل (تطبيق جوال، خدمات خارجية، أو فرونت إند) سيعيد رسائل مترجمة بدقة وفق لغة الطلب (`Accept-Language`).

### 👁️ النتيجة المتوقعة وسيناريو الاستخدام (Expected Behavior & Outcomes)
- عندما يطلب المستخدم رابط استعادة كلمة المرور والواجهة باللغة العربية، يستقبل إشعار النجاح مباشرة باللغة العربية:
  > **تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني**
- إذا قام المستخدم بالتحويل إلى اللغة الإنجليزية، يستقبل تلقائياً:
  > **Password reset link sent to your email**
- تدعم الرسالة الوضعين الفاتح والمظلم بألوان متباينة ومريحة للعين.

---

## 2. 🔬 التشريح والشرح التقني العميق (In-Depth Technical Architecture & Code Breakdown)

### 🔍 تحليل السبب الجذري والفرضيات (Root Cause Investigation)

#### الفرضيات الخمس التي تم فحصها:
1. **[Hypothesis 1 - Payload/Contract Mismatch]:** خادم الـ API يعيد رسالة نجاح `response.data.message` باللغة الإنجليزية فيتجاوز الفرونت إند ترجمته الخاصة. `[✅ CONFIRMED - ROOT CAUSE 1]`
2. **[Hypothesis 2 - Missing Backend Localization Files]:** مجلد `lang/ar/` في لارافيل يفتقر إلى ملف `passwords.php`، مما يجبر دالة الترجمة `__($status)` على التراجع التلقائي (Fallback) للغة الإنجليزية المضمنة في إطار العمل `vendor/laravel/framework`. `[✅ CONFIRMED - ROOT CAUSE 2]`
3. **[Hypothesis 3 - Header Accept-Language Not Sent]:** الـ `ApiClient` لا يرسل ترويسة `Accept-Language` مع طلب الـ Axios. `[❌ FALSIFIED]` - الفحص أثبت أن `ApiClient.ts` يحقن `Accept-Language: ar` تلقائياً عبر الـ Request Interceptor.
4. **[Hypothesis 4 - Missing Frontend Translation Key]:** مفتاح `forgotPassword.successMessage` غير معرف في ملف `ar/auth.json`. `[❌ FALSIFIED]` - المفتاح موجود بالفعل بالنص: `"تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني"`.
5. **[Hypothesis 5 - Middleware SetLocale Failure]:** الـ Middleware المسئول عن قراءة اللغة في لارافيل `SetLocale.php` معطل. `[❌ FALSIFIED]` - الـ Middleware يعمل بشكل سليم ولكنه لم يجد ملف `passwords.php` العربي.

---

### 📁 تفنيد الملفات والتعديلات المنفذة

#### 1. [`lang/ar/passwords.php`](file:///e:/laragon/www/harvesta/lang/ar/passwords.php) `[NEW]`
- **السبب التقني:** في متحكم المصادقة [`AuthController.php`](file:///e:/laragon/www/harvesta/app/Http/Controllers/AuthController.php#L221-L222)، يتم استدعاء دالة الترجمة في لارافيل:
  ```php
  $status = $this->authService->sendPasswordResetLink($request->email);
  return $this->response_success(null, __($status));
  ```
  عندما تكون القيمة `$status = 'passwords.sent'`، يبحث لارافيل في مسار `lang/ar/passwords.php`. لعدم وجود الملف، كان لارافيل يتراجع إلى ملف الإنجليزية الداخلي في `vendor`.
- **الحل:** تم إنشاء ملف الترجمة القياسي:
  ```php
  return [
      'reset' => 'تمت إعادة تعيين كلمة المرور بنجاح.',
      'sent' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.',
      'throttled' => 'يرجى الانتظار قبل إعادة المحاولة.',
      'token' => 'رمز إعادة تعيين كلمة المرور هذا غير صالح.',
      'user' => 'تعذر العثور على مستخدم بهذا البريد الإلكتروني.',
  ];
  ```

#### 2. [`lang/en/passwords.php`](file:///e:/laragon/www/harvesta/lang/en/passwords.php) `[NEW]`
- تم إنشاء نظيره الإنجليزي في مسار `lang/en/` لضمان الاتساق الهيكلي وتجنب الاعتماد على ملفات الـ Vendor.

#### 3. [`LoginPrompt.tsx`](file:///e:/laragon/www/harvesta/resources/frontend/src/components/LoginPrompt.tsx)
- **السبب التقني:** كان السطر البرمجي مكتوباً بالصيغة:
  ```typescript
  setForgotPasswordSuccess(response.data?.message || t('auth:forgotPassword.successMessage', ...));
  ```
  وحيث أن `response.data.message` كان يعيد قيمة نصية حقيقية، فإن معامل التراجع `||` كان يلتقط رسالة الـ API دائماً ويتجاهل دالة الترجمة `t()`.
- **الحل الجذري:**
  تم عكس أولوية الربط ليتم استدعاء `t('auth:forgotPassword.successMessage')` أولاً كمرجع أساسي لواجهة المستخدم الحالية، مع استخدام `response.data?.message` كـ Fallback احتياطي فقط:
  ```typescript
  setForgotPasswordSuccess(t('auth:forgotPassword.successMessage', { defaultValue: response.data?.message || 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.' }));
  ```

---

### 🛡️ التحقق ومعالجة الحالات الحدية (Edge Cases & Safety)
1. **التحقق من لارافيل عبر سكريبت مؤقت:**
   - تم التحقق تجريبياً بأن `App::setLocale('ar'); __('passwords.sent')` أصبح يعيد بدقة:
     `تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.`
2. **التحقق من الـ Typescript:**
   - تم تشغيل `npx tsc --noEmit` واجتاز الفحص بدون أي خطأ.
3. **التنظيف الكامل:**
   - تم مسح كافة سكريبتات الفحص والملفات المؤقتة.

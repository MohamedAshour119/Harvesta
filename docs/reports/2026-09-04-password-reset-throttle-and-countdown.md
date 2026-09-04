# 📑 تقرير شامل: نظام معدل الطلبات (Rate Limiting) وإرسال الروابط والعداد التنازلي (Countdown UI)

---

## 💡 أولاً: الشرح النظري وتجربة المستخدم (Theoretical & Conceptual Overview)

### 1. الهدف والدافع (Purpose & Motivation)
تعتبر ميزة "نسيان كلمة المرور" (Forgot Password) واحدة من أكثر نقاط النهاية (Endpoints) حساسية في أي تطبيق ويب. عدم وجود حماية كافية عليها يفتح الباب أمام هجمات الحرمان من الخدمة (Denial of Service - DoS) وهجمات إغراق البريد الإلكتروني (Email Flooding / Mail Bombing)، واستنزاف حصص خوادم البريد (SMTP Quota).
في نفس الوقت، أثناء مرحلة التطوير والاختبار الداخلي (Development & Testing Phase)، يحتاج المطور إلى اختبار تدفق استعادة الحساب وإرسال الإيميلات مراراً وتكراراً دون أن يتم حظره لمدة 60 ثانية بعد كل محاولة.

لذلك، كان الدافع الأساسي هو:
1. **المرونة الهندسية**: تمكين وضع الاختبار (Testing Mode) بحيث يسمح بإرسال طلبات متتالية دون انتظار، مع إمكانية التبديل إلى وضع الإنتاج (Live/Production Mode) الذي يفرض قيود الأمان القياسية تلقائياً.
2. **الشفافية التفاعلية للمستخدم (UX Clarity)**: عندما يكون هناك حد زمني لإعادة المحاولة (Cooldown)، لا يجوز أن يترك المستخدم حائراً دون معرفة متى يمكنه إعادة المحاولة. ظهور **عداد تنازلي لحظي (Countdown Timer)** بدقة الثواني يرفع من جودة تجربة المستخدم ويمنع الضغط العشوائي والمتكرر على زر الإرسال.
3. **هوية بصرية نقية للبريد**: عرض شعار هارفستا الرسمي الفيكتور (SVG) بأعلى دقة عبر رابط علني، ودون إرفاق ملفات (Attachments) تجعل الإيميل يظهر بمظهر غير احترافي أو مشبوه في بريد Gmail.

---

### 2. لماذا قمنا بهذا التعديل (Why This Change Matters)
- **منع الإحباط أثناء الـ Testing**: ضبط المتغير البيئي `AUTH_PASSWORD_RESET_THROTTLE=0` أزال أي عائق أمام فريق العمل لاختبار التصاميم، الروابط، ونصوص الإيميل.
- **تطبيق أفضل الممارسات العالمية في الأمان**:
  - **المستوى الأول (Per-Email Cooldown)**: 60 ثانية لكل بريد إلكتروني فردي عبر وسيط الرموز الخاص بـ Laravel (`DatabaseTokenRepository`).
  - **المستوى الثاني (Per-IP Rate Limiting)**: 5 طلبات في الدقيقة كحد أقصى لكل عنوان IP عبر `RateLimiter::for('forgot-password')` لمنع البوتات وهجمات التخمين.
- **توجيه بصري متقدم (Visual Feedback)**: إضافة شارة تفاعلية تعرض أيقونة ساعة نابضة مع عدد الثواني المتبقية، وتعطيل زر الإرسال تلقائياً حتى انتهاء العد التنازلي، مع إتاحة زر "إعادة الإرسال" بمجرد انتهاء الوقت.

---

### 3. النتيجة المتوقعة وسيناريو الاستخدام (Expected Behavior & Outcomes)
1. **في وضع التطوير (`AUTH_PASSWORD_RESET_THROTTLE=0`)**:
   - يستطيع المطور إرسال طلب استعادة كلمة المرور لبريده في أي وقت ولأي عدد من المرات دون أن تظهر له رسالة "يرجى الانتظار قبل إعادة المحاولة".
2. **في وضع الإنتاج أو عند تفعيل الـ Throttle (`AUTH_PASSWORD_RESET_THROTTLE=60`)**:
   - عند إدخال البريد والضغط على "إرسال رابط إعادة التعيين"، يظهر صندوق النجاح مع العداد التنازلي: **"يمكنك طلب رابط جديد بعد: 60 ثانية"**.
   - يبدأ الرقم في التناقص ثانية بثانية (`59`, `58`, ...).
   - أثناء العد، يكون زر الإرسال معطلاً (`Disabled`) ومكتوباً عليه **"إعادة الإرسال بعد (X ث)"**.
   - بمجرد وصول العداد إلى `0`، يختفي العداد ويظهر زر واضح: **"لم يصلك البريد؟ إعادة إرسال الرابط مرة أخرى"**.
   - الضغط عليه يعيد إرسال الإيميل ويعيد تشغيل العداد من جديد.

---

## 🔬 ثانياً: التشريح والشرح التقني العميق (In-Depth Technical Architecture & Code Breakdown)

### 1. تفنيد الملفات المعدلة وتبرير الحل الهندسي

#### أ) إعدادات المصادقة: `config/auth.php` و `.env`
- **التعديل**:
  ```php
  'throttle' => (int) env('AUTH_PASSWORD_RESET_THROTTLE', env('APP_ENV') === 'production' ? 60 : 0),
  ```
- **السبب التقني**:
  في بنية Laravel الداخلية، يقوم `Illuminate\Auth\Passwords\DatabaseTokenRepository::recentlyCreatedToken()` بفحص:
  ```php
  if ($this->throttle <= 0) {
      return false;
  }
  ```
  عندما تكون القيمة `0`، يتم تجاوز فحص الانتظار الزمني فوراً. جعلنا القيمة تقرأ من المتغير البيئي `AUTH_PASSWORD_RESET_THROTTLE`، وإذا لم يُحدد، تعتمد على بيئة التشغيل (`production = 60`, أي بيئة أخرى = `0`).

---

#### ب) مزود الخدمة: `app/Providers/AppServiceProvider.php`
- **التعديل**:
  1. تعريف محدد معدل الطلبات للـ IP:
     ```php
     RateLimiter::for('forgot-password', function (Request $request) {
         if ((int) config('auth.passwords.users.throttle') === 0) {
             return Limit::none();
         }
         return Limit::perMinute(5)->by($request->ip())->response(function () {
             return response()->json([
                 'status' => 429,
                 'message' => app()->getLocale() === 'ar'
                     ? 'تم تجاوز عدد المحاولات المسموح بها. يرجى الانتظار قليلاً قبل إعادة المحاولة.'
                     : 'Too many requests. Please wait a moment before trying again.',
                 'errors' => []
             ], 429);
         });
     });
     ```
  2. تخصيص إشعار استعادة كلمة المرور عبر `ResetPassword::toMailUsing()` لربطه بقالب Blade الخاص بالنظام `emails.reset-password` مع الشعار الفيكتور وروابط ngrok.
- **السبب التقني**:
  حماية الطبقة الثانية (Network/IP layer) تعزل محاولات الهجوم الجماعي على مستوى الـ IP، وتتكامل في نفس الوقت مع مفتاح الـ Testing لعدم إعاقة المطورين.

---

#### ج) مسارات الـ API: `routes/api.php`
- **التعديل**:
  ```php
  Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
      ->middleware('throttle:forgot-password');
  ```
- **السبب التقني**:
  تطبيق الـ Middleware بشكل صريح ومستقل على المسار دون التأثير على باقي مسارات المصادقة.

---

#### د) ملفات اللغات والترجمة: `ar/auth.json` و `en/auth.json`
- **التعديل**:
  إضافة المفاتيح:
  - `resendCooldown`: `"يمكنك طلب رابط جديد بعد: {{seconds}} ثانية"` / `"You can request a new link in: {{seconds}}s"`
  - `resendIn`: `"إعادة الإرسال بعد ({{seconds}}ث)"` / `"Resend in ({{seconds}}s)"`
  - `resendButton`: `"إعادة إرسال الرابط مرة أخرى"` / `"Resend reset link"`
  - `didNotReceive`: `"لم يصلك البريد؟"` / `"Didn't receive the email?"`
- **السبب التقني**:
  الالتزام بالـ i18n المعياري للمشروع ومنع وجود أي نصوص ثابتة (Hardcoded strings) داخل واجهة المستخدم.

---

#### هـ) مكون واجهة المستخدم: `resources/frontend/src/components/LoginPrompt.tsx`
- **التعديل**:
  1. إضافة حالة العداد ومؤقت الـ Interval:
     ```tsx
     const [countdown, setCountdown] = useState<number>(0);

     useEffect(() => {
       if (countdown <= 0) return;
       const timer = setInterval(() => {
         setCountdown((prev) => (prev <= 1 ? (clearInterval(timer), 0) : prev - 1));
       }, 1000);
       return () => clearInterval(timer);
     }, [countdown]);
     ```
  2. تحديث `handleForgotPassword` لتقبل معامل حدث اختياري `e?: React.FormEvent`، وتفعيل العداد `setCountdown(60)` عند نجاح الإرسال أو عند اعتراض استجابة 429 أو رسائل الانتظار.
  3. دعم النمط المزدوج (`Dark Mode` و `Light Mode`):
     - الوضع الفاتح: `bg-amber-50 text-amber-800 border-amber-200/60`
     - الوضع المظلم: `dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800/40`
  4. منطق إظهار زر إعادة الإرسال والعداد:
     - إذا كان `countdown > 0`: يظهر شريط تنبيه به أيقونة ساعة متحركة بالنبض (`animate-pulse`) مع العد التنازلي.
     - إذا كان `countdown === 0`: يظهر قسم "لم يصلك البريد؟ إعادة إرسال الرابط مرة أخرى".
- **السبب التقني**:
  إدارة دورة حياة الـ Timer داخل `useEffect` مع دالة تنظيف `clearInterval` تضمن عدم حدوث تسريب في الذاكرة (Memory Leak) عند إغلاق النافذة المنبثقة أو تبديل النمط.

---

### 2. معالجة الحالات الحدية (Edge Cases & Safety)
1. **تكرار الضغط أثناء الإرسال (Double Submission)**:
   - تم تعطيل الزر بواسطة `disabled={loading || countdown > 0}`، مما يمنع إرسال طلبات متزامنة في نفس اللحظة.
2. **إغلاق النافذة المنبثقة وإعادة فتحها**:
   - يتم تنظيف الحالة عند تغيير نمط النافذة لضمان تجربة متسقة ونظيفة.
3. **الاتجاهات اللغوية (RTL vs LTR) وفئات التباعد (Flex Gap)**:
   - تم استخدام فئات التباعد الأصلية `gap-2.5` بدلاً من `space-x-*` لضمان وجود مسافة واضحة بين أيقونة الساعة والنص في كلا الاتجاهين.
4. **حل عرض الشعار عبر CDN عالمي موثوق (ImgBB CDN)**:
   - تم ربط الشعار العربي (`https://i.ibb.co/MkVtHBPN/logo-ar.png`) والإنجليزي (`https://i.ibb.co/HfVhHwqg/logo.png`) عبر متغيرات `.env` (`MAIL_LOGO_AR_URL` و `MAIL_LOGO_EN_URL`).
   - يتجاوز هذا الحل جدار حظر جوجل لـ ngrok، ويعرض الشعار في Gmail فوراً وبأعلى دقة ودون أي مرفقات.
5. **فحص الأنواع الصارم (TypeScript) واختبار البناء (Vite Build)**:
   - تم التحقق بنجاح عبر `npx tsc --noEmit` واكتمال بناء المشروع عبر `npx vite build` بعد تصفير كاش `node_modules/.vite` مع انعدام أي أخطاء برمجية (`0 errors`).
6. **تحسين وتوسيط عناصر قالب البريد (Email Card Layout & Centering)**:
   - تم نقل الشعار ليصبح داخل البطاقة البيضاء الرئيسية (`content-card`) في المنتصف بدلاً من وجوده خارجها.
   - تم توسيط خط التدرج اللوني الجمالي (`margin: 0 auto`).
   - تم إزالة أيقونة القفل الزائدة (`🔐`) لمنع التشتت البصري، وتوسيط عنوان الرسالة الرئيسي (`text-align: center`) لخلق توازن بصري راقٍ وعصري كبرى المنصات العالمية.
7. **اعتماد شعارات الـ PNG في المنصة ككل (Frontend Header & Footer)**:
   - تم تحديث كل من مكون الهيدر [`Header.tsx`](file:///e:/laragon/www/harvesta/resources/frontend/src/components/Header.tsx) ومكون الفوتر [`FooterSection.tsx`](file:///e:/laragon/www/harvesta/resources/frontend/src/components/FooterSection.tsx) لاستخدام شعارات الـ PNG (`/logo-ar.png` و `/logo.png`) بدلاً من الـ SVG.

# 📑 تقرير فني وتقني: دمج استعادة كلمة المرور داخل المودال وإلغاء الصفحة المنفصلة

**التاريخ**: 4 سبتمبر 2026  
**المشروع**: هارفيستا (Harvesta)  
**نطاق التعديل**: المصادقة وتجربة المستخدم (Authentication & User Experience Flow)

---

## 💡 الجزء الأول: الشرح النظري وتجربة المستخدم (Theoretical & UX Overview)

### 1. الدافع والمشكلة من منظور منطق الأعمال وتجربة المستخدم (The Why)
- **تشتت انتباه وسياق المستخدم (Context Fragmentation)**:
  عندما كان المستخدم يفتح نافذة تسجيل الدخول (Modal Popup) من أي مكان في المتجر، ثم يكتشف أنه نسي كلمة المرور ويضغط على رابط "نسيت كلمة المرور؟"، كان السلوك السابق يقوم بإغلاق النافذة المنبثقة قسراً وإعادة توجيهه إلى صفحة منفصلة بالكامل (`https://harvesta.test/forgot-password`).
- **المشكلة الناتجة**:
  1. خروج المستخدم من الصفحة أو المنتج الذي كان يتصفحه (Product Page أو Cart أو Checkout).
  2. اضطرار المتصفح لعمل Routing كامل يقطع التدفق البصري للمستخدم.
  3. تعارض هذا السلوك مع القرار الاستراتيجي للمنصة الذي تم تطبيقه سابقاً على صفحتي `Login` و `Register` (بإلغاء الصفحات المستقلة وجعل كل عمليات الدخول والتسجيل مركزية في نافذة منبثقة موحدة `LoginPrompt Modal`).

### 2. الحل النظري والنتيجة المتوقعة (Expected User Experience)
- **تجربة تفاعلية أحادية المصدر (Single-Modal Seamless Flow)**:
  الآن، عندما ينقر المستخدم على "نسيت كلمة المرور؟":
  - **لا يغادر صفحته الحالية مطلقاً**.
  - تتحول نفس النافذة المنبثقة بحركة انسيابية ناعمة (`Smooth Transition`) لتصبح واجهة استعادة كلمة المرور.
  - يتغير الهيدر ليعرض أيقونة المفتاح 🔑 وعنوان "إعادة تعيين كلمة المرور".
  - بمجرد إرسال الرابط، تظهر رسالة نجاح واضحة داخل النافذة مع زر صريح للعودة إلى تسجيل الدخول.
  - تم إلغاء الصفحة المنفصلة تماماً، وفي حال قام أي شخص بكتابة الرابط القديم يدوياً في المتصفح، يتم توجيهه إلى الصفحة الرئيسية مع فتح النافذة المنبثقة مباشرة على وضع استعادة كلمة المرور لضمان تجربة مستخدم خالية من أخطاء `404`.

---

## 🔬 الجزء الثاني: التشريح والشرح التقني المعمق (In-Depth Technical Architecture & Code Breakdown)

```mermaid
flowchart TD
    A["المستخدم في المتجر"] --> B["فتح مودال المصادقة (LoginPrompt)"]
    B -->|النقر على نسيت كلمة المرور| C["switchMode('forgot-password')"]
    C -->|انتقال سلس بالأنيميشن| D["واجهة استعادة كلمة المرور بالمودال"]
    D -->|إدخال الإيميل والإرسال| E["ApiClient.auth.forgotPassword(email)"]
    E -->|الاستجابة بالنجاح| F["عرض رسالة التأكيد الخضراء داخل المودال"]
    F -->|زر العودة| B
    
    G["دخول مباشر للرابط /forgot-password"] --> H["ForgotPasswordRedirect (App.tsx)"]
    H -->|dispatch(openForgotPasswordModal)| I["تحديث Redux State"]
    H -->|Navigate to='/' replace| A
    I --> D
```

---

### 1. تفصيل التعديلات التقنية ملفاً بملف مع الأسباب الهندسية:

#### أ. تعديل `resources/frontend/src/store/slices/isAuthPopupOpenSlice.ts`
- **التعديل البرمجي**:
  - توسيع الـ Union Type لحالة المودال من `mode: 'login' | 'register'` ليشمل الوضع الجديد: `'login' | 'register' | 'forgot-password'`.
  - تصدير Action جديد: `openForgotPasswordModal`.
- **السبب التقني**:
  - تجنب إدارة حالة المودال محلياً داخل المكون فقط، لأن فتح المودال قد يتم استدعاؤه من أماكن خارجية في التطبيق (مثل الروابط الخارجية أو الـ Header أو الـ Redirect Routes).
  - استخدام Redux Toolkit يضمن الحفاظ على `Single Source of Truth` لكافة حالات فتح المودال ونوعه.

#### ب. تعديل `resources/frontend/src/components/LoginPrompt.tsx`
- **التعديل البرمجي**:
  - استبدال عملية التوجيه الصلبة السابقة:
    ```tsx
    // الكود القديم:
    navigate('/forgot-password');
    ```
    بدالة انتقال داخلية موحدة `switchMode('forgot-password')`:
    ```tsx
    // الكود الجديد:
    const switchMode = (target: 'login' | 'register' | 'forgot-password') => {
      setIsFormTransitioning(true);
      setError(null);
      setFieldErrors({});
      setForgotPasswordSuccess(null);
      setTimeout(() => {
        setPopupType(target);
        setTimeout(() => {
          setIsFormTransitioning(false);
        }, 300);
      }, 150);
    };
    ```
  - إضافة دعم الأيقونات الديناميكية للهيدر:
    - تسجيل الدخول ⬅️ `<FaLock />`
    - إنشاء الحساب ⬅️ `<FaUser />`
    - نسيت كلمة المرور ⬅️ `<FaKey />`
  - بناء قسم `forgot-password` المخصص داخل `<AnimatePresence mode="wait">` ليتعامل مع حالتين:
    1. **حالة الفورم (Form View)**: حقل البريد الإلكتروني مع دعم الترجمة وعرض الأخطاء + زر الإرسال مع حالة الـ Loading والـ Disabled لمنع الـ Double Submit + زر "العودة إلى تسجيل الدخول".
    2. **حالة النجاح (Success View)**: عرض بطاقة خضراء ناعمة برسالة السيرفر مع زر العودة التلقائي لواجهة الدخول.
  - مشاركة حقل `form.email` بين تسجيل الدخول واستعادة كلمة المرور: إذا كتب المستخدم إيميله في شاشة الدخول ثم ضغط "نسيت كلمة المرور"، يجد الإيميل مكتوباً تلقائياً دون الحاجة لإعادة كتابته.

#### ج. تعديل مسارات التطبيق في `resources/frontend/src/App.tsx`
- **التعديل البرمجي**:
  - استبدال استدعاء المكون القديم `<MaxWidthWrapper><ForgotPassword /></MaxWidthWrapper>` بمكون إعادة التوجيه الذكي `ForgotPasswordRedirect`:
    ```tsx
    const ForgotPasswordRedirect: React.FC = () => {
      const dispatch = useDispatch();
      useEffect(() => {
        dispatch(openForgotPasswordModal());
      }, [dispatch]);
      return <Navigate to="/" replace />;
    };
    ```
  - حذف استيراد المكون القديم `import ForgotPassword from '...'`.
- **السبب التقني الهندسي للـ `ForgotPasswordRedirect Component`**:
  - **لماذا لم نكتفِ بـ `<Navigate to="/" replace />` فقط؟**  
    لو اكتفينا بتحويل المسار إلى `/` فقط، لكان المستخدم الذي يحفظ الرابط لديه أو ينقر عليه من متصفح خارجي سيُحول للصفحة الرئيسية دون أن يفتح له فورم استعادة كلمة المرور، مما يسبب إحباطاً للمستخدم.
  - **لماذا لم نضع الاستدعاء في الـ Route مباشرة؟**  
    في React Router v6، تنفيذ Side Effect (مثل `dispatch`) يجب أن يتم داخل دورة حياة مكوّن (`useEffect`) لمنع حدوث Warning في الكونسول يخص تحديث الـ State أثناء عملية الـ Render الأساسية.
  - استخدام `replace: true` يمنع تلويث تاريخ المتصفح (Browser History Stack) حتى إذا ضغط المستخدم على زر "رجوع" في المتصفح لا يعلق في حلقة إعادة توجيه لا نهائية.

#### د. حذف ملف ومجلد `resources/frontend/src/pages/Login/ForgotPassword/`
- **التعديل البرمجي**:
  - حذف ملف `ForgotPassword.tsx` ومجلده بالكامل من شجرة المشروع.
- **السبب التقني**:
  - **Dead Code Elimination**: التخلص تماماً من الأكواد المهجورة لمنع تراكم الملفات غير المستخدمة في الـ Bundle النهائي، مما يقلل من حجم ملفات الـ JavaScript الناتجة ويسرع زمن التحميل الأولي للموقع.

---

### 2. معالجة الحالات الخاصة وتوافق السمات (Edge Cases & Theming):
1. **دعم الثيمين الفاتح والداكن (Dual Theme Cairo3a Parity)**:
   - كافة العناصر الجديدة تدعم بشكل كامل `dark:bg-[#151D2C]`, `dark:text-slate-100`, `dark:border-slate-800`, وبطاقات النجاح `dark:bg-green-950/40 dark:border-green-900/50`.
2. **دعم الاتجاهين العربي والإنجليزي (RTL & LTR)**:
   - تم فحص وتوجيه أيقونة سهم الرجوع `<FaArrowRight />` في العربي و `<FaArrowLeft />` في الإنجليزي.
3. **تصفير الأخطاء عند التبديل**:
   - تفريغ مصفوفة الأخطاء `fieldErrors` و `error` و `forgotPasswordSuccess` عند التنقل بين الشاشات حتى لا تنتقل أخطاء واجهة إلى واجهة أخرى.

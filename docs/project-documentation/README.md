# توثيق المشروع

هذا المجلد يحتوي على توثيق عربي تقني للمشروع كما هو موجود في الكود الحالي. الهدف منه أن يعطي أي مطور جديد صورة واضحة عن التقنيات، المعمارية، فكرة المنتج، عملية المصادقة، ميزات الذكاء الاصطناعي، والدوال الأساسية التي تخدم التطبيق.

## ترتيب القراءة المقترح

1. [المرحلة 1: Tech Stack](01-tech-stack.md)
2. [المرحلة 2: Project Architecture](02-architecture.md)
3. [المرحلة 3: Project Idea](03-project-idea.md)
4. [المرحلة 4: Auth Process](04-auth-process.md)
5. [المرحلة 5: AI Features](05-ai-features.md)
6. [المرحلة 6: Function Reference](06-function-reference.md)
7. [المرحلة 7: Testing Guide](07-testing.md)

## نطاق التوثيق

التوثيق يركز على طبقات التطبيق التي تخدم المنتج مباشرة:

- Backend API المبني على Laravel.
- طبقة `Controllers`, `Services`, `Repositories`, `Requests`, `Resources`, و`Models`.
- Frontend المبني على React وVite، وخصوصا `resources/frontend/src/ApiClient.ts`.
- عمليات الدفع، الطلبات، المنتجات، الوكلاء، الكوبونات، المخزون، التوصيات، والـ chatbot.
- ميزات الذكاء الاصطناعي المبنية على Groq وJina وRecombee.

## ملفات مهمة في المشروع

- `routes/api.php`: نقطة البداية لمعظم API endpoints.
- `app/Http/Controllers`: استقبال الطلبات وتحويلها إلى services.
- `app/Http/Services`: business logic والتكاملات الخارجية.
- `app/Http/Repositories`: database queries وتجميع البيانات.
- `resources/frontend/src/ApiClient.ts`: عميل Axios وكل API methods المستخدمة في الواجهة.
- `config/services.php`: إعدادات Stripe وTap وGroq وJina وRecombee وBrevo.
- `phpunit.xml`: تقسيم الاختبارات الحالي إلى `Services` و`Repositories`.

## ملاحظات صيانة

- عند إضافة endpoint جديد، حدّث [Project Architecture](02-architecture.md) و[Function Reference](06-function-reference.md).
- عند تغيير auth flow، حدّث [Auth Process](04-auth-process.md) وراجع `ApiClient.ts`.
- عند تغيير prompts أو AI providers أو embeddings، حدّث [AI Features](05-ai-features.md).
- عند إضافة service أو repository جديد، أضفه إلى [Function Reference](06-function-reference.md).
- عند إضافة أو نقل اختبار، راجع [Testing Guide](07-testing.md) لتحديد مكانه وطريقة تشغيله.

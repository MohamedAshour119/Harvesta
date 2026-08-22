# المرحلة 3: Project Idea

المشروع عبارة عن منصة تجارة إلكترونية متقدمة تجمع بين كتالوج منتجات، نظام طلبات ودفع، إدارة مخزون، لوحة وكلاء sales agents، توصيات ذكية، وبوت محادثة يساعد العملاء والإدارة.

## الفكرة الأساسية

المنصة تسمح للعميل بتصفح المنتجات، البحث عنها، رؤية الأسعار والتفاصيل، إضافة المنتجات إلى wishlist/cart، تطبيق كوبونات، حساب الشحن، والدفع إلكترونيا. وفي نفس الوقت تدعم دور الوكيل الذي يبيع يدويا للعملاء ويحتاج dashboard للعمولات والأداء والطلبات.

النظام ليس مجرد متجر بسيط؛ هو يجمع بين:

- Product catalog متعدد الفئات والـ variants.
- Agent sales workflow.
- Inventory-aware checkout.
- Coupons and pending coupon state.
- Recommendations based on user/product interactions.
- AI chatbot للعميل.
- AI admin assistant للإدارة.
- Semantic and visual search.

## المستخدمون الرئيسيون

| المستخدم | الهدف |
| --- | --- |
| Guest user | تصفح المنتجات، البحث، استخدام chatbot، رؤية التفاصيل، بدء login/register عند الحاجة. |
| Customer | الشراء، wishlist، ratings، coupons، checkout، invoices، profile orders. |
| Agent | رؤية dashboard، إنشاء manual orders، البحث عن العملاء والمنتجات، متابعة المبيعات والعمولات. |
| Admin/Super Admin | إدارة الكتالوج والطلبات والمخزون والكوبونات والوكلاء من Filament، واستخدام admin chatbot. |

## كتالوج المنتجات

الكتالوج مبني حول:

- `Product`: المنتج الأساسي، الاسم والوصف والـ slug والفئة والحالة.
- `ProductVariant`: السعر، الخصم، السعر الخاص بالوكيل، stock availability، dimensions/weight، supplier.
- `IndustryCategory`: فئات المنتجات.
- `VariantType`: نوع variant.
- `Supplier`: المورد.
- Media collections لصور المنتجات.

أهم الوظائف:

- عرض منتجات home page.
- عرض كل المنتجات approved التي لها variants.
- عرض top products حسب المبيعات.
- عرض product details بالـ slug.
- فلترة shop حسب category, price, stock, discounts, today deals, semantic results.
- طلب منتج مخصص من خلال `request-custom-product`.

## الطلبات والدفع

عملية الطلب تشمل:

- استقبال products والـ variants والكمية.
- تطبيق smart bundle discounts عند توفرها.
- التأكد من minimum order value.
- تطبيق coupon إذا كان صالحا.
- حساب shipping cost حسب المدينة وأبعاد/وزن المنتجات.
- إنشاء `Order` و`OrderItem`.
- إنشاء payment session في Stripe.
- عند نجاح الدفع يتم تحديث:
  - `payment_status`
  - `status`
  - inventory quantities
  - coupon pivot status
  - Recombee purchase interactions

## المخزون

المخزون يعتمد على:

- `Inventory`
- `InventoryProduct`
- علاقة product variant بالمخزون.

`InventoryAllocationService` يستهلك المخزون عند نجاح الدفع أو اعتماد manual sale. طريقة الاستهلاك تفضل records ذات الكمية الأقل أولا، وتفشل إذا لم توجد كمية كافية.

## الكوبونات

الكوبونات مبنية حول:

- `Coupon`
- pivot بين المستخدم والكوبون.
- حالات مثل `pending` و`used`.

النظام يمنع:

- استخدام كوبون منتهي.
- استخدام كوبون inactive.
- تجاوز usage limit.
- استخدام نفس الكوبون مرة أخرى إذا تم استخدامه بالفعل.
- ترك pending coupons قديمة بدون release.

## الوكلاء والمبيعات اليدوية

الوكيل هو مستخدم لديه `is_agent`, `code`, `commission`, وmonthly targets. الوكيل يستطيع:

- رؤية dashboard metrics.
- رؤية sales performance.
- متابعة recent orders.
- متابعة commission and bonus.
- إنشاء manual orders.
- البحث عن عملاء ومنتجات لإنشاء order يدوي.

الطلبات اليدوية قد تكون:

- auto-approved إذا كانت أقل من threshold معين.
- submitted للمراجعة إذا كانت كبيرة.
- approved/rejected لاحقا من الإدارة.

## الملف الشخصي والفواتير

العميل يستطيع:

- رؤية بيانات profile.
- تعديل الاسم.
- حذف الحساب soft delete مع إلغاء tokens.
- رؤية الطلبات.
- تنزيل invoice PDF.

الوكيل يستطيع أيضا رؤية agent activity الخاصة به.

## Ratings and Wishlist

نظام ratings يسمح بـ:

- إرسال rating/comment.
- تحديث rating سابق لنفس المستخدم والمنتج.
- عرض average rating.
- عرض breakdown.
- عرض paginated ratings.

Wishlist تسمح بإضافة وإزالة products/variants، وترسل Recombee bookmark interactions.

## Recommendations

Recombee يستخدم لتقديم:

- توصيات للمستخدم بناء على تفاعلاته.
- توصيات مشابهة لمنتج.
- frequently bought together باستخدام logic مثل `ecommerce:cross-sell`.

التفاعلات التي تغذي Recombee:

- detail view.
- add/remove bookmark.
- cart addition.
- purchase.
- sync product item values.

## Chatbot and AI Search

المشروع يحتوي على:

- Customer chatbot يساعد في أسئلة المنتجات والطلبات.
- Admin chatbot يجيب على أسئلة الإدارة باستخدام بيانات حقيقية.
- Semantic search يحلل query ويستخدم embeddings.
- Visual search يبحث بصورة أو base64 image.
- Prompt injection detection وresponse sanitization لحماية chatbot.

## قيمة المشروع

القيمة الأساسية للمشروع أنه يجمع ecommerce operations مع sales-agent workflows وAI features في نفس النظام. هذا يسمح بتجربة شراء أفضل للعملاء، إدارة أقوى للوكلاء، وبحث وتوصيات أكثر ذكاء من البحث النصي التقليدي.

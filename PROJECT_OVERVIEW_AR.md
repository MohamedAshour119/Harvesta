# 🛍️ Cairo 3A - نظام التجارة الإلكترونية B2B

## 📋 نظرة عامة على المشروع

**Cairo 3A** هو نظام تجارة إلكترونية متكامل من نوع B2B (Business to Business) مصمم لبيع المنتجات الصناعية بالجملة. المشروع يجمع بين منصة ويب حديثة للعملاء ولوحة تحكم إدارية قوية لإدارة المنتجات والطلبات والوكلاء.

### المعلومات الأساسية
- **اسم المشروع**: Cairo 3A
- **النوع**: منصة تجارة إلكترونية B2B
- **Framework Backend**: Laravel 12
- **Framework Frontend**: React 19 + TypeScript
- **قاعدة البيانات**: MySQL
- **المصادقة**: Laravel Sanctum (JWT)
- **لوحة التحكم**: Filament 3.3
- **الدفع الإلكتروني**: Tap Payment Gateway
- **اللغات المدعومة**: العربية والإنجليزية (متعدد اللغات)

---

## 🎯 الفكرة الرئيسية للمشروع

المشروع عبارة عن منصة تجارة إلكترونية متخصصة في بيع المنتجات الصناعية بالجملة مع نظام وكلاء متقدم. يتيح النظام:

1. **للعملاء**: تصفح وشراء المنتجات الصناعية بكميات كبيرة
2. **للوكلاء**: الحصول على عمولات من المبيعات وتتبع أدائهم
3. **للإدارة**: إدارة شاملة للمنتجات، الطلبات، المخزون، والوكلاء

---

## 🏗️ البنية المعمارية

### Backend Architecture (Laravel)
```
app/
├── Console/Commands/          # أوامر Artisan المخصصة
├── Enums/                     # التعدادات (Enums)
├── Filament/                  # لوحة التحكم الإدارية
│   ├── Pages/                 # صفحات مخصصة
│   └── Resources/             # موارد CRUD
├── Helper/                    # دوال مساعدة
├── Http/
│   ├── Controllers/           # معالجات الطلبات
│   ├── Middleware/            # الوسطاء
│   ├── Repositories/          # طبقة الوصول للبيانات
│   ├── Requests/              # التحقق من المدخلات
│   ├── Resources/             # تحويل البيانات للـ API
│   └── Services/              # منطق الأعمال
├── Jobs/                      # المهام غير المتزامنة
├── Listeners/                 # مستمعي الأحداث
├── Mail/                      # قوالب البريد الإلكتروني
└── Models/                    # نماذج قاعدة البيانات
```

### Frontend Architecture (React + TypeScript)
```
resources/frontend/src/
├── assets/                    # الصور والملفات الثابتة
├── auth/                      # مكونات المصادقة والحماية
├── components/                # مكونات React قابلة لإعادة الاستخدام
│   ├── inputs/               # مكونات الإدخال
│   └── modals/               # النوافذ المنبثقة
├── helper/                    # دوال مساعدة
├── hooks/                     # React Hooks مخصصة
├── i18n/                      # ملفات الترجمة (i18next)
│   └── locales/
│       ├── ar/               # الترجمة العربية
│       └── en/               # الترجمة الإنجليزية
├── pages/                     # صفحات التطبيق
│   ├── Home/                 # الصفحة الرئيسية
│   ├── Shop/                 # صفحة المتجر
│   ├── Cart/                 # سلة التسوق
│   ├── Checkout/             # صفحة الدفع
│   ├── Dashboard/            # لوحة تحكم الوكيل
│   ├── ShowProduct/          # تفاصيل المنتج
│   ├── Login/                # تسجيل الدخول
│   └── SignUp/               # التسجيل
└── store/                     # Redux Store
    └── slices/               # Redux Slices
```

---

## 🗄️ قاعدة البيانات - الجداول الرئيسية

### 1. Users (المستخدمين)
- معلومات المستخدمين الأساسية
- الأدوار والصلاحيات
- معلومات الوكيل (agent_code, commission_rate, monthly_target)
- علاقات: Orders, Wishlist, Coupons, Roles, Bonus

### 2. Products (المنتجات)
- معلومات المنتج (name, description) - متعددة اللغات
- الفئة الصناعية (industry_category_id)
- الحد الأدنى للطلب (min_order_value, minimum_order_quantity)
- حالة الموافقة (is_approved)
- OEM (Original Equipment Manufacturer)
- الصور (Media Library)
- علاقات: ProductVariants, IndustryCategory, OrderItems, Ratings

### 3. Product Variants (أشكال المنتج)
- الأسعار (price, discount_price, discount_percentage)
- المواصفات (weight, volume)
- التوفر (availability, stock_availability)
- التكلفة اللوجستية (cost_plus_logistics)
- نوع الشكل (variant_type_id)
- المورد (supplier_id)
- علاقات: Product, VariantType, Supplier, Inventory

### 4. Orders (الطلبات)
- معلومات العميل (user_id)
- كود الوكيل (agent_code)
- الخصم (discount_amount)
- الحالة (pending, approved, shipped, delivered)
- معلومات الشحن (shipping details)
- الكوبون المستخدم (coupon_id)
- علاقات: User, Agent, OrderItems, Coupon

### 5. Order Items (عناصر الطلب)
- المنتج والشكل (product_id, product_variant_id)
- الكمية والسعر (quantity, price)
- علاقات: Order, Product, ProductVariant

### 6. Agents (الوكلاء)
- الاسم (متعدد اللغات)
- الكود الفريد (code)
- نسبة العمولة (commission_rate)
- علاقات: Orders

### 7. Agent Monthly Targets (أهداف الوكلاء الشهرية)
- الهدف الشهري (target_amount)
- المبلغ المحقق (achieved_amount)
- الشهر والسنة (month, year)
- المكافأة (bonus)

### 8. Bonuses (المكافآت)
- مكافآت الوكلاء
- المبلغ (amount)
- حالة الدفع (paid_at)
- علاقات: User

### 9. Coupons (كوبونات الخصم)
- الاسم والقيمة (name, value)
- الحالة (active, in-active)
- حد الاستخدام (usage_limit, used_count)
- تاريخ الانتهاء (expires_at)
- علاقات: Users (many-to-many)

### 10. Industry Categories (الفئات الصناعية)
- الاسم والوصف (متعدد اللغات)
- الصورة (Media Library)
- Slug للروابط
- علاقات: Products

### 11. Suppliers (الموردين)
- معلومات المورد
- علاقات: Products, ProductVariants

### 12. Inventories (المخازن)
- معلومات المخزن
- علاقات: InventoryProducts

### 13. Inventory Products (منتجات المخزن)
- ربط المنتجات بالمخازن
- الكمية المتوفرة
- تنبيه عند كمية معينة (show_alert_at_quantity)
- علاقات: Inventory, Product, ProductVariant

### 14. Wishlist Items (قائمة الأمنيات)
- المنتجات المفضلة للمستخدم
- علاقات: User, ProductVariant

### 15. Product Ratings (تقييمات المنتجات)
- التقييم (rating)
- التعليق (comment)
- علاقات: User, Product, ProductVariant

### 16. Custom Products (المنتجات المخصصة)
- طلبات المنتجات المخصصة من العملاء
- الحالة (pending, approved, rejected)

### 17. Shipping Destinations (وجهات الشحن)
- المدن والمناطق
- تكلفة الشحن

### 18. Roles (الأدوار)
- أدوار المستخدمين (Admin, Super Admin, etc.)
- علاقات: Users (many-to-many)

---

## 🔄 دورة حياة الطلب (Order Lifecycle)

### 1. العميل يتصفح المنتجات
```
GET /api/home → عرض المنتجات الرئيسية
GET /api/products → جميع المنتجات
GET /api/shop/filters → تصفية المنتجات
GET /api/products/{slug} → تفاصيل منتج معين
```

### 2. إضافة المنتجات للسلة
- يتم تخزين السلة في Redux Store (Frontend)
- يتم حفظها في localStorage للاستمرارية

### 3. تطبيق كوبون الخصم (اختياري)
```
POST /api/apply-coupon
- التحقق من صلاحية الكوبون
- التحقق من عدم استخدامه مسبقاً
- التحقق من حد الاستخدام
- ربط الكوبون بالمستخدم (pending status)
```

### 4. حساب تكلفة الشحن
```
POST /api/shipping-cost
- إرسال الوجهة والمنتجات
- حساب التكلفة بناءً على الوزن والحجم
```

### 5. إتمام الطلب والدفع
```
POST /api/checkout
- التحقق من الحد الأدنى للطلب (50 SAR)
- التحقق من الكميات المتوفرة
- إنشاء الطلب في قاعدة البيانات
- التكامل مع Tap Payment Gateway
- إعادة توجيه العميل لصفحة الدفع
```

### 6. معالجة الدفع
```
Tap Payment Callback:
GET /payment/callback → استقبال نتيجة الدفع
POST /payment/webhook → استقبال إشعارات Tap

إذا نجح الدفع:
- تحديث حالة الطلب
- تحديث استخدام الكوبون (used)
- إرسال إشعارات
```

### 7. معالجة الطلب من الإدارة
```
Filament Admin Panel:
- مراجعة الطلب
- تحديث الحالة: pending → approved → shipped → delivered
```

### 8. تحديث عمولة الوكيل
```
عند تحديث حالة الطلب إلى "delivered":
- Job: UpdateAgentMonthlyAchievedAmount
- تحديث المبلغ المحقق للوكيل
- حساب العمولة
- تحديث المكافآت إذا تحقق الهدف
```

---

## 🔐 نظام المصادقة والصلاحيات

### المصادقة (Authentication)
- **Backend**: Laravel Sanctum (Token-based)
- **Frontend**: Redux Store + localStorage
- **Token Refresh**: تلقائي كل فترة محددة

### نقاط النهاية (Endpoints)
```php
POST /api/auth/register        // التسجيل
POST /api/auth/login           // تسجيل الدخول
POST /api/auth/logout          // تسجيل الخروج
POST /api/auth/refresh-token   // تحديث التوكن
POST /api/auth/verify-token    // التحقق من التوكن
```

### الأدوار (Roles)
1. **Super Admin**: صلاحيات كاملة
2. **Admin**: إدارة المنتجات والطلبات
3. **Agent**: وكيل مبيعات (عمولات)
4. **Customer**: عميل عادي

### Middleware
- `auth:sanctum`: التحقق من المصادقة
- `EnsureAgent`: التأكد من أن المستخدم وكيل
- `PreventAgents`: منع الوكلاء من الوصول (مثل صفحة الدفع)
- `SetLocale`: تعيين اللغة

---

## 💰 نظام الوكلاء والعمولات

### كيف يعمل نظام الوكلاء؟

1. **إنشاء وكيل**
   - يتم إنشاء وكيل من لوحة التحكم
   - يحصل على كود فريد (Agent Code)
   - تحديد نسبة العمولة (commission_rate)

2. **ربط العميل بالوكيل**
   - عند التسجيل، يدخل العميل كود الوكيل
   - يتم حفظ الكود في جدول Users

3. **تتبع المبيعات**
   - كل طلب يحتوي على agent_code
   - عند تسليم الطلب (delivered)، يتم:
     - حساب العمولة = (قيمة الطلب × نسبة العمولة)
     - تحديث achieved_amount في جدول agent_monthly_targets

4. **الأهداف الشهرية**
   - يتم تعيين هدف شهري لكل وكيل
   - تتبع الإنجاز الشهري
   - إذا تحقق الهدف، يحصل على مكافأة إضافية

5. **لوحة تحكم الوكيل**
   ```
   GET /api/agents/dashboard-info
   - إجمالي المبيعات
   - العمولات المكتسبة
   - الأهداف الشهرية
   - الطلبات الأخيرة
   
   GET /api/agents/dashboard-info/sales-performance/{year}
   - أداء المبيعات السنوي (شهر بشهر)
   
   GET /api/agents/dashboard-info/commission-and-bonus
   - تفاصيل العمولات والمكافآت
   
   GET /api/agents/dashboard-info/commissions-timeline
   - الجدول الزمني للعمولات
   ```

6. **Impersonation (انتحال الشخصية)**
   - يمكن للـ Super Admin تسجيل الدخول كوكيل
   - لمساعدة الوكلاء أو حل المشاكل
   ```
   POST /api/agents/{user}/impersonate
   GET /admin/agents/{user}/impersonate (Web Route)
   ```

---

## 🛒 نظام السلة والدفع

### إدارة السلة (Cart Management)
- **Frontend**: Redux Slice (cartSlice)
- **Persistence**: localStorage
- **Features**:
  - إضافة/حذف المنتجات
  - تحديث الكميات
  - حساب الإجمالي تلقائياً
  - تطبيق الخصومات

### كوبونات الخصم (Coupons)
```typescript
// Redux Slice: couponSlice
- تخزين الكوبون المطبق
- التحقق من الصلاحية
- حساب الخصم
```

### الحد الأدنى للطلب
- الحد الأدنى: 50 SAR
- يتم التحقق في Backend قبل إنشاء الطلب
- رسالة تنبيه في Frontend إذا لم يتحقق

### بوابة الدفع (Tap Payment)
```php
// TapPaymentService.php
- إنشاء جلسة دفع
- إعادة توجيه العميل
- معالجة Callback
- معالجة Webhook
```

**Environment Variables**:
```
TAP_SECRET_KEY_TEST
TAP_SECRET_KEY_LIVE
TAP_PUBLISHABLE_KEY_TEST
TAP_PUBLISHABLE_KEY_LIVE
TAP_ENVIRONMENT=test
TAP_WEBHOOK_SECRET
```

---

## 🌐 نظام متعدد اللغات (i18n)

### Backend (Laravel)
- **Package**: spatie/laravel-translatable
- **الجداول المترجمة**:
  - products (name, description)
  - industry_categories (name, description)
  - agents (name)
- **التخزين**: JSON في قاعدة البيانات
  ```json
  {
    "en": "Product Name",
    "ar": "اسم المنتج"
  }
  ```

### Frontend (React)
- **Package**: i18next + react-i18next
- **الملفات**:
  ```
  resources/frontend/src/i18n/locales/
  ├── ar/
  │   └── translation.json
  └── en/
      └── translation.json
  ```
- **Features**:
  - تبديل اللغة تلقائياً
  - تغيير اتجاه الصفحة (RTL/LTR)
  - حفظ اللغة المختارة في localStorage

### Filament Admin Panel
- **Package**: bezhansalleh/filament-language-switch
- **اللغات**: العربية والإنجليزية
- **التبديل**: من شريط التنقل

---

## 📦 إدارة المخزون (Inventory Management)

### الجداول
1. **inventories**: المخازن
2. **inventory_product**: ربط المنتجات بالمخازن
   - الكمية المتوفرة
   - تنبيه عند كمية معينة (show_alert_at_quantity)
   - ربط بالـ variant_id

### Job: UpdateInventoryStatusJob
- تحديث حالة توفر المنتجات تلقائياً
- يعمل بشكل دوري أو عند تغيير الكميات

### في لوحة التحكم
- عرض المخزون الحالي
- تنبيهات عند انخفاض الكمية
- تحديث الكميات يدوياً

---

## 🎨 لوحة التحكم الإدارية (Filament)

### الموارد (Resources)
```
app/Filament/Resources/
├── ProductResource.php              // إدارة المنتجات
├── ProductVariantResource.php       // أشكال المنتجات
├── OrderResource.php                // الطلبات
├── UserResource.php                 // المستخدمين
├── AgentResource.php                // الوكلاء
├── AgentMonthlyTargetResource.php   // أهداف الوكلاء
├── BonusResource.php                // المكافآت
├── CouponResource.php               // الكوبونات
├── IndustryCategoryResource.php     // الفئات الصناعية
├── SupplierResource.php             // الموردين
├── InventoryResource.php            // المخازن
├── InventoryProductResource.php     // منتجات المخزن
├── CustomProductResource.php        // المنتجات المخصصة
├── ShippingDestinationResource.php  // وجهات الشحن
├── VariantTypeResource.php          // أنواع الأشكال
└── RoleResource.php                 // الأدوار
```

### الصفحات المخصصة
```
app/Filament/Pages/
└── Warnings.php                     // صفحة التنبيهات
```

### الميزات
- CRUD كامل لجميع الموارد
- تحميل الصور (Spatie Media Library)
- تصفية وبحث متقدم
- تصدير البيانات
- إحصائيات ورسوم بيانية
- إشعارات
- سجل الأنشطة

---

## 🔍 نظام البحث والتصفية

### البحث (Search)
```php
GET /api/search/{keyword}
- البحث في أسماء المنتجات
- البحث في الأوصاف
- البحث في الفئات

GET /api/search-keyword/{keyword}
- اقتراحات البحث السريع
```

### التصفية (Filters)
```php
GET /api/shop/filters
Query Parameters:
- category: الفئة
- min_price: السعر الأدنى
- max_price: السعر الأعلى
- sort: الترتيب (price_asc, price_desc, newest, popular)
- availability: التوفر
```

### Frontend Components
```typescript
// FilterSlice (Redux)
- حفظ حالة الفلاتر
- تطبيق الفلاتر على المنتجات
- إعادة تعيين الفلاتر
```

---

## ⭐ نظام التقييمات (Ratings)

### الميزات
- تقييم المنتجات (1-5 نجوم)
- كتابة تعليقات
- عرض متوسط التقييم
- عرض توزيع التقييمات

### API Endpoints
```php
// تقديم تقييم (يتطلب مصادقة)
POST /api/ratings
Body: {
  product_id,
  product_variant_id,
  rating,
  comment
}

// الحصول على تقييمات منتج
GET /api/ratings/product/{productId}

// الحصول على تقييمات مع pagination
GET /api/ratings/product/{productId}/paginated

// إحصائيات التقييمات
GET /api/ratings/product/{productId}/stats

// تقييم المستخدم لمنتج معين
GET /api/ratings/product/{productId}/user
```

---

## 💝 قائمة الأمنيات (Wishlist)

### الميزات
- إضافة/حذف المنتجات المفضلة
- عرض القائمة
- مزامنة مع قاعدة البيانات

### API Endpoints
```php
GET /api/wishlist                    // عرض القائمة
POST /api/wishlist/add               // إضافة منتج
DELETE /api/wishlist/remove          // حذف منتج
```

### Frontend
```typescript
// FavoritesSlice (Redux)
- إدارة المفضلات
- Sidebar للعرض السريع
```

---

## 📧 نظام الرسائل والإشعارات

### إرسال رسائل
```php
POST /api/send-message
- نموذج اتصل بنا
- إرسال بريد إلكتروني للإدارة
```

### البريد الإلكتروني
- **SMTP**: Gmail
- **Package**: Brevo (للتسويق)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
BREVO_API_KEY=...
```

### Mail Classes
```php
app/Mail/
└── ContactMessageMail.php
```

---

## 🎨 الصور والوسائط (Media Management)

### Package: Spatie Media Library
- تحميل الصور
- تحسين الصور تلقائياً
- تحويلات متعددة (sizes)
- WebP format للأداء

### Media Collections
```php
Products:
- main_images (صورة رئيسية واحدة)
- products_other_images (حتى 5 صور إضافية)

Industry Categories:
- industry_categories_images
```

### Image Conversions
```php
- slider: 300x230 (للسلايدر)
- product_page: 800x600 (صفحة المنتج)
- product_card: 260x160 (كارت المنتج)
```

### Listeners
```php
app/Listeners/
├── MediaOptimization.php                      // تحسين صور المنتجات
└── IndustryCategoriesMediaOptimization.php    // تحسين صور الفئات
```

---

## 🚀 الأوامر المخصصة (Artisan Commands)

```php
app/Console/Commands/
├── CheckAgentMonthlyTargets.php    // فحص أهداف الوكلاء الشهرية
└── GenerateProductSlugs.php        // توليد slugs للمنتجات
```

### الاستخدام
```bash
php artisan check:agent-monthly-targets
php artisan generate:product-slugs
```

---

## 🔧 التقنيات والحزم المستخدمة

### Backend (Laravel)
```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "laravel/sanctum": "^4.0",
  "filament/filament": "3.3",
  "spatie/laravel-translatable": "^6.11",
  "spatie/laravel-media-library": "^11.0",
  "guzzlehttp/guzzle": "^7.9",
  "propaganistas/laravel-phone": "^6.0"
}
```

### Frontend (React)
```json
{
  "react": "^19.1.0",
  "react-dom": "^19.1.0",
  "react-router-dom": "^7.6.0",
  "@reduxjs/toolkit": "^2.8.2",
  "react-redux": "^9.2.0",
  "redux-persist": "^6.0.0",
  "axios": "^1.9.0",
  "i18next": "^25.2.1",
  "react-i18next": "^15.5.2",
  "tailwindcss": "^4.1.11",
  "framer-motion": "^12.15.0",
  "swiper": "^11.2.8",
  "recharts": "^2.15.3",
  "react-phone-input-2": "^2.15.1"
}
```

### Build Tools
```json
{
  "vite": "^6.3.5",
  "laravel-vite-plugin": "^1.2.0",
  "@vitejs/plugin-react-swc": "^3.9.0",
  "typescript": "~5.8.3"
}
```

---

## 📱 الصفحات الرئيسية (Main Pages)

### للعملاء
1. **الصفحة الرئيسية** (`/`)
   - عرض المنتجات المميزة
   - الفئات الصناعية
   - العروض الخاصة

2. **المتجر** (`/shop`)
   - عرض جميع المنتجات
   - التصفية والبحث
   - سلة التسوق الجانبية

3. **تفاصيل المنتج** (`/products/{slug}`)
   - صور المنتج
   - الأشكال المتاحة
   - التقييمات
   - المنتجات المشابهة

4. **السلة** (`/cart`)
   - عرض المنتجات المضافة
   - تحديث الكميات
   - حساب الإجمالي

5. **الدفع** (`/checkout`)
   - معلومات الشحن
   - تطبيق الكوبون
   - إتمام الدفع

6. **نتائج البحث** (`/show-results`)
   - عرض نتائج البحث
   - التصفية

7. **طلب منتج مخصص** (`/custom-product-request`)
   - نموذج طلب منتج خاص

### للوكلاء
1. **لوحة التحكم** (`/dashboard`)
   - إحصائيات المبيعات
   - الأهداف الشهرية
   - الطلبات الأخيرة

2. **العمولات والمكافآت** (`/dashboard/commission-bonus`)
   - تفاصيل العمولات
   - المكافآت المكتسبة
   - الجدول الزمني

3. **العروض والترويجات** (`/dashboard/promotions-offers`)
   - الكوبونات المتاحة
   - العروض الخاصة

### صفحات أخرى
- `/login` - تسجيل الدخول
- `/signup` - التسجيل
- `/about` - من نحن
- `/privacy` - سياسة الخصوصية
- `/terms` - الشروط والأحكام
- `/returns` - سياسة الإرجاع

---

## 🔒 الأمان (Security)

### المصادقة
- Laravel Sanctum (Token-based)
- CSRF Protection
- Password Hashing (bcrypt)

### Middleware
- Rate Limiting
- CORS Configuration
- XSS Protection
- SQL Injection Prevention (Eloquent ORM)

### Environment Variables
- جميع المفاتيح السرية في `.env`
- عدم رفع `.env` إلى Git

### Validation
- Form Requests للتحقق من المدخلات
- Server-side validation
- Client-side validation (React)

---

## 🚀 التشغيل والإعداد

### المتطلبات
- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL >= 8.0
- npm أو yarn

### خطوات التثبيت

1. **استنساخ المشروع**
```bash
git clone <repository-url>
cd devno-d13
```

2. **تثبيت Dependencies**
```bash
# Backend
composer install

# Frontend
cd resources/frontend
npm install
cd ../..
```

3. **إعداد البيئة**
```bash
cp .env.example .env
php artisan key:generate
```

4. **إعداد قاعدة البيانات**
```bash
# تعديل .env بمعلومات قاعدة البيانات
php artisan migrate
php artisan db:seed
```

5. **ربط التخزين**
```bash
php artisan storage:link
```

6. **تشغيل المشروع**
```bash
# طريقة 1: استخدام composer script
composer dev
# يشغل: server + queue + vite معاً

# طريقة 2: يدوياً
php artisan serve          # Backend (port 8000)
php artisan queue:listen   # Queue Worker
npm run dev                # Frontend (Vite)
```

7. **الوصول للتطبيق**
- Frontend: `http://localhost:5173`
- Backend API: `http://localhost:8000/api`
- Admin Panel: `http://localhost:8000/admin`

---

## 📊 الإحصائيات والتقارير

### لوحة تحكم الوكيل
- إجمالي المبيعات
- عدد الطلبات
- العمولات المكتسبة
- الأهداف الشهرية
- رسم بياني للأداء السنوي (Recharts)

### لوحة التحكم الإدارية
- إحصائيات المنتجات
- إحصائيات الطلبات
- أداء الوكلاء
- المخزون
- التقييمات

---

## 🎯 الميزات الرئيسية

### ✅ للعملاء
- [x] تصفح المنتجات بسهولة
- [x] بحث وتصفية متقدم
- [x] سلة تسوق ذكية
- [x] كوبونات خصم
- [x] قائمة أمنيات
- [x] تقييم المنتجات
- [x] دفع آمن (Tap Payment)
- [x] تتبع الطلبات
- [x] طلب منتجات مخصصة
- [x] دعم متعدد اللغات

### ✅ للوكلاء
- [x] لوحة تحكم مخصصة
- [x] تتبع المبيعات والعمولات
- [x] أهداف شهرية
- [x] مكافآت عند تحقيق الأهداف
- [x] رسوم بيانية للأداء
- [x] عرض الطلبات
- [x] كوبونات خاصة

### ✅ للإدارة
- [x] لوحة تحكم شاملة (Filament)
- [x] إدارة المنتجات والأشكال
- [x] إدارة الطلبات
- [x] إدارة الوكلاء والعمولات
- [x] إدارة المخزون
- [x] إدارة الكوبونات
- [x] إدارة المستخدمين والأدوار
- [x] تقارير وإحصائيات
- [x] تحسين الصور تلقائياً
- [x] نظام إشعارات
- [x] سجل الأنشطة

---

## 🔄 الأنماط المعمارية (Design Patterns)

### 1. Repository Pattern
```
Controllers → Services → Repositories → Models
```
- فصل منطق الوصول للبيانات
- سهولة الاختبار
- قابلية إعادة الاستخدام

### 2. Service Layer Pattern
```php
app/Http/Services/
- منطق الأعمال المعقد
- معالجة البيانات
- التكامل مع خدمات خارجية
```

### 3. Resource Pattern (API Transformation)
```php
app/Http/Resources/
- تحويل البيانات للـ API
- إخفاء الحقول الحساسة
- تنسيق موحد للاستجابات
```

### 4. Observer Pattern (Events & Listeners)
```php
Events:
- MediaAdded
- OrderStatusChanged

Listeners:
- MediaOptimization
- UpdateAgentCommission
```

### 5. Job Pattern (Queue)
```php
app/Jobs/
- UpdateAgentMonthlyAchievedAmount
- UpdateInventoryStatusJob
```

### 6. Middleware Pattern
```php
- Authentication
- Authorization
- Localization
- Rate Limiting
```

---

## 🧪 الاختبار (Testing)

### Framework
- PHPUnit (Backend)
- Laravel Testing Utilities

### أنواع الاختبارات
```bash
# تشغيل الاختبارات
composer test
# أو
php artisan test
```

### ملفات الاختبار
```
tests/
├── Feature/          # اختبارات التكامل
└── Unit/            # اختبارات الوحدة
```

---

## 📝 الملاحظات الفنية

### Performance Optimization
1. **Database**
   - Indexes على الأعمدة المستخدمة في البحث
   - Eager Loading لتجنب N+1 Problem
   - Query Optimization

2. **Images**
   - WebP format
   - Multiple sizes (responsive)
   - Lazy loading

3. **Caching**
   - Redis (optional)
   - Query caching
   - Route caching

4. **Frontend**
   - Code splitting
   - Lazy loading components
   - Redux for state management
   - localStorage for persistence

### Scalability
- Queue system للمهام الثقيلة
- API-based architecture
- Microservices-ready
- Horizontal scaling capable

---

## 🔮 التطويرات المستقبلية المحتملة

### المقترحات
1. **نظام الإشعارات الفورية**
   - WebSockets (Laravel Echo + Pusher)
   - إشعارات الطلبات الجديدة
   - إشعارات تحديث الحالة

2. **تطبيق موبايل**
   - React Native
   - Flutter
   - استخدام نفس الـ API

3. **تحليلات متقدمة**
   - Google Analytics
   - تتبع سلوك المستخدمين
   - تقارير مخصصة

4. **نظام المراجعات المتقدم**
   - صور في التقييمات
   - فيديوهات
   - رد الإدارة على التقييمات

5. **نظام الولاء**
   - نقاط المكافآت
   - مستويات العضوية
   - خصومات حصرية

6. **تكامل مع أنظمة خارجية**
   - ERP Systems
   - Accounting Software
   - Shipping Companies APIs

7. **AI Features**
   - توصيات المنتجات الذكية
   - Chatbot للدعم
   - تحليل سلوك الشراء

---

## 📚 الموارد والمراجع

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [React Documentation](https://react.dev)
- [Redux Toolkit](https://redux-toolkit.js.org)
- [Tailwind CSS](https://tailwindcss.com)

### APIs
- [Tap Payment API](https://www.tap.company/developers)
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

---

## 🤝 المساهمة والدعم

### Git Workflow
```bash
# إنشاء فرع جديد
git checkout -b feature/feature-name

# Commit changes
git add .
git commit -m "Add feature description"

# Push to remote
git push origin feature/feature-name

# Create Pull Request
```

### Coding Standards
- PSR-12 (PHP)
- ESLint (JavaScript/TypeScript)
- Laravel Pint للتنسيق

### Code Review
- مراجعة الكود قبل الدمج
- اختبار الميزات الجديدة
- توثيق التغييرات

---

## 🐛 استكشاف الأخطاء (Troubleshooting)

### مشاكل شائعة

1. **خطأ في الاتصال بقاعدة البيانات**
```bash
# التحقق من إعدادات .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devno_d13
DB_USERNAME=root
DB_PASSWORD=

# إعادة تشغيل MySQL
```

2. **خطأ في تحميل الصور**
```bash
# التأكد من ربط التخزين
php artisan storage:link

# التحقق من الصلاحيات
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

3. **خطأ في Token**
```bash
# مسح الكاش
php artisan config:clear
php artisan cache:clear

# إعادة توليد المفتاح
php artisan key:generate
```

4. **خطأ في Vite**
```bash
# مسح node_modules وإعادة التثبيت
rm -rf node_modules
npm install

# مسح الكاش
npm run build
```

5. **خطأ في Queue**
```bash
# إعادة تشغيل Queue Worker
php artisan queue:restart
php artisan queue:listen
```

---

## 📞 معلومات الاتصال

### للدعم الفني
- Email: ashourmohamed123456@gmail.com
- WhatsApp: متاح في الموقع

### للإدارة
- Admin Panel: `/admin`
- Contact Email: fashonista@devtrail.org

---

## 📄 الترخيص

هذا المشروع مبني على Laravel Framework المرخص تحت [MIT License](https://opensource.org/licenses/MIT).

---

## 🎉 الخلاصة

**Fashonista** هو نظام تجارة إلكترونية B2B متكامل يجمع بين:
- منصة ويب حديثة للعملاء (React + TypeScript)
- API قوي ومرن (Laravel)
- لوحة تحكم إدارية شاملة (Filament)
- نظام وكلاء متقدم مع عمولات ومكافآت
- دعم متعدد اللغات (عربي/إنجليزي)
- دفع إلكتروني آمن (Tap Payment)
- إدارة مخزون ذكية
- نظام تقييمات وتعليقات

المشروع مصمم بمعايير عالية من الأمان والأداء وقابلية التوسع، مع اتباع أفضل الممارسات في تطوير البرمجيات.

---

**تاريخ التحديث**: مارس 2026
**الإصدار**: 1.0.0

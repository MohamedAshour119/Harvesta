# Smart Bundles Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** بناء نظام العروض الذكية لعرض رسائل تسويقية ديناميكية في سلة التسوق بناءً على الكميات لتشجيع زيادة المشتريات.
**Architecture:** إضافة العروض في قاعدة البيانات (Migrations, Models) وإدارتها في لوحة التحكم (Filament)، تطبيق المنطق في الـ Backend وتوليد رسائل تسويقية للـ Frontend وعرضها في السلة.
**Tech Stack:** Laravel 12, Filament 3.3, React 19, Redux.

---

### الخطوة 1: تجهيز قاعدة البيانات (Database & Migrations)

**1. إنشاء المايجريشن والنموذج (Migration & Model)**
- **الأمر:** `php artisan make:model SmartBundle -m`

**2. تعديل ملف المايجريشن المولد (`database/migrations/xxxx_xx_xx_xxxxxx_create_smart_bundles_table.php`)**
```php
public function up(): void
{
    Schema::create('smart_bundles', function (Blueprint $table) {
        $table->id();
        $table->json('name'); // اسم العرض مدعوم بتعدد اللغات
        $table->integer('target_quantity'); // الكمية المطلوبة لتطبيق الخصم
        $table->decimal('discount_percentage', 5, 2); // نسبة الخصم
        $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete(); // تخصيص العرض لمنتج معين
        $table->boolean('is_active')->default(true); // حالة تفعيل العرض
        $table->timestamps();
    });
}
```

**3. تعديل النموذج (`app/Models/SmartBundle.php`)**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartBundle extends Model
{
    protected $fillable = [
        'name',
        'target_quantity',
        'discount_percentage',
        'product_id',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'discount_percentage' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

### الخطوة 2: لوحة التحكم الإدارية (Filament Admin Panel)

**1. إنشاء الـ Resource**
- **الأمر:** `php artisan make:filament-resource SmartBundle`

**2. تعديل ملف (`app/Filament/Resources/SmartBundleResource.php`)**
- **في دالة `form(Form $form)`:**
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255), // أو استخدام حقل تعدد اللغات إذا كان مستخدماً في المشروع
            Forms\Components\TextInput::make('target_quantity')
                ->required()
                ->numeric()
                ->minValue(2),
            Forms\Components\TextInput::make('discount_percentage')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name') // استبدل 'name' بالعمود المناسب لو لزم
                ->searchable()
                ->nullable()
                ->helperText('اتركه فارغاً لتعميم العرض (اختياري)'),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
}
```

- **في دالة `table(Table $table)`:**
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('target_quantity'),
            Tables\Columns\TextColumn::make('discount_percentage')->suffix('%'),
            Tables\Columns\TextColumn::make('product.name'),
            Tables\Columns\ToggleColumn::make('is_active'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ]);
}
```

---

### الخطوة 3: طبقة الخدمات ومنطق السلة (Backend APIs & Services)

**1. إنشاء ملف خدمة العروض**
- **المسار:** `app/Services/Order/SmartBundleService.php`
- **الكود المطلوب حرفياً:**
```php
namespace App\Services\Order;

use App\Models\SmartBundle;

class SmartBundleService
{
    public function calculateCartCTAs(array $cartItems): array
    {
        // استخراج أرقام المنتجات من السلة
        $productIds = collect($cartItems)->pluck('product_id')->filter()->unique()->toArray();
        if (empty($productIds)) {
            return [];
        }

        // جلب العروض النشطة لهذه المنتجات
        $bundles = SmartBundle::whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('product_id');

        $ctas = [];

        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'] ?? 0;

            if ($bundles->has($productId)) {
                $bundle = $bundles->get($productId);
                
                if ($quantity < $bundle->target_quantity) {
                    $missingQuantity = $bundle->target_quantity - $quantity;
                    $ctas[] = [
                        'product_id' => $productId,
                        'type' => 'warning',
                        'missing_quantity' => $missingQuantity,
                        'discount_percentage' => (float) $bundle->discount_percentage,
                        'message' => "أضف {$missingQuantity} قطع إضافية لتحصل على خصم {$bundle->discount_percentage}%",
                    ];
                } else {
                    $ctas[] = [
                        'product_id' => $productId,
                        'type' => 'success',
                        'missing_quantity' => 0,
                        'discount_percentage' => (float) $bundle->discount_percentage,
                        'message' => "مرحى! لقد تم تفعيل خصم ({$bundle->discount_percentage}%) على هذا المنتج",
                    ];
                }
            }
        }

        return $ctas;
    }
}
```

**2. دمج الخدمة في الـ API الخاص بالسلة أو الدفع (مثلاً `app/Http/Controllers/Api/CartController.php`)**
- حقن خدمة `SmartBundleService` في الكونترولر لتعديل الاستجابة (Endpoint Response):
```php
$ctas = app(\App\Services\Order\SmartBundleService::class)->calculateCartCTAs($cartItemsArray);

return response()->json([
    // ... استجابتك السابقة (عناصر السلة، الإجمالي، إلخ)
    'smart_bundle_ctas' => $ctas,
]);
```

---

### الخطوة 4: واجهة المستخدم (React Frontend)

**1. تحديثات الـ Redux Store**
- **المسار:** `resources/frontend/src/store/slices/cartSlice.ts`
- **التعديلات الحرفية:**
```typescript
// 1. أضف الواجهة
export interface SmartBundleCTA {
  product_id: number;
  type: 'warning' | 'success';
  missing_quantity: number;
  discount_percentage: number;
  message: string;
}

// 2. تحديث الـ State interface والـ initialState
// في الـ State Interface أضف:
// smartBundleCTAs: SmartBundleCTA[];
// في initialState أضف:
// smartBundleCTAs: [],

// 3. أضف Action في reducers
setSmartBundleCTAs: (state, action: PayloadAction<SmartBundleCTA[]>) => {
  state.smartBundleCTAs = action.payload;
},
```

**2. إنشاء مكون العرض المخصص (React Component)**
- **المسار:** `resources/frontend/src/components/marketing/CartBundleCTA.tsx`
- **الهيكل الميكانيكي للمكون:**
```tsx
import React from 'react';

export interface CartBundleCTAProps {
  type: 'warning' | 'success';
  message: string;
}

export const CartBundleCTA: React.FC<CartBundleCTAProps> = ({ type, message }) => {
  const isWarning = type === 'warning';

  return (
    <div className={`mt-2 p-3 rounded-md flex items-center gap-2 text-sm font-medium ${isWarning ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-green-50 text-green-700 border border-green-200'}`}>
      {isWarning ? (
        <span className="text-xl">🔥</span> 
      ) : (
        <span className="text-xl">✅</span> 
      )}
      <span>{message}</span>
    </div>
  );
};
```

**3. استدعاء المكون في صفحة السلة (`resources/frontend/src/pages/Cart/Cart.tsx`)**
- الاستدعاء:
```tsx
import { CartBundleCTA } from '@/components/marketing/CartBundleCTA';
import { useSelector } from 'react-redux';
```
- جلب الـ State بداخل المكون:
```tsx
const smartBundleCTAs = useSelector((state: RootState) => state.cart.smartBundleCTAs);
```
- بداخل الـ Loop الخاص بالمنتجات (`cartItems.map(item => ...)`):
```tsx
const cta = smartBundleCTAs ? smartBundleCTAs.find(c => c.product_id === item.product_id) : null;

return (
  <div key={item.id} className="...">
    // ... تصميم المنتج العادي
    {cta && <CartBundleCTA type={cta.type} message={cta.message} />}
  </div>
);
```

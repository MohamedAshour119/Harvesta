<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RichSupermarketCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Cleaning old catalog, orders, and associated data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToClean = [
            'order_items',
            'orders',
            'wishlist_items',
            'product_ratings',
            'product_embeddings',
            'product_image_embeddings',
            'smart_bundles',
            'product_supplier',
            'product_variants',
            'products',
            'industry_categories',
        ];

        foreach ($tablesToClean as $tbl) {
            if (Schema::hasTable($tbl)) {
                DB::table($tbl)->truncate();
            }
        }

        // Clean media belonging to Products, ProductVariants, and IndustryCategories
        if (Schema::hasTable('media')) {
            DB::table('media')->whereIn('model_type', [
                Product::class,
                ProductVariant::class,
                IndustryCategory::class,
                'App\Models\Product',
                'App\Models\ProductVariant',
                'App\Models\IndustryCategory',
            ])->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command?->info('Old catalog and orders successfully cleaned!');

        // Ensure VariantTypes exist
        $weightType = VariantType::firstOrCreate(
            ['name' => 'Weight'],
            ['name' => 'Weight']
        );

        // 11 Comprehensive Categories
        $categoriesData = [
            [
                'slug' => 'fresh-poultry',
                'name' => ['en' => 'Fresh Poultry', 'ar' => 'دواجن طازجة'],
                'image_key' => 'chicken_whole',
            ],
            [
                'slug' => 'frozen-meals',
                'name' => ['en' => 'Frozen Ready Meals', 'ar' => 'مجمدات جاهزة للطهي'],
                'image_key' => 'chicken_pane',
            ],
            [
                'slug' => 'crispy-fried',
                'name' => ['en' => 'Crispy & Fried Chicken', 'ar' => 'دجاج مقلي ومقرمش'],
                'image_key' => 'chicken_strips',
            ],
            [
                'slug' => 'cooking-oils',
                'name' => ['en' => 'Oils & Pure Ghee', 'ar' => 'زيوت وسمن فاخر'],
                'image_key' => 'cooking_oil',
            ],
            [
                'slug' => 'frozen-vegetables',
                'name' => ['en' => 'Frozen Vegetables & Fries', 'ar' => 'خضروات وبطاطس مجمدة'],
                'image_key' => 'french_fries',
            ],
            [
                'slug' => 'dairy-cheese',
                'name' => ['en' => 'Dairy & Artisan Cheeses', 'ar' => 'ألبان وأجبان فاخرة'],
                'image_key' => 'dairy_cheese',
            ],
            [
                'slug' => 'rice-pasta-grains',
                'name' => ['en' => 'Rice, Pasta & Grains', 'ar' => 'أرز، مكرونة وبقوليات'],
                'image_key' => 'rice_grain',
            ],
            [
                'slug' => 'sauces-condiments',
                'name' => ['en' => 'Sauces & Gourmet Spices', 'ar' => 'صلصات وبهارات ممتازة'],
                'image_key' => 'sauce_ketchup',
            ],
            [
                'slug' => 'teas-beverages',
                'name' => ['en' => 'Teas & Hot Beverages', 'ar' => 'شاي ومشروبات ساخنة'],
                'image_key' => 'tea_box',
            ],
            [
                'slug' => 'bakery-pastries',
                'name' => ['en' => 'Bakery & Pastries', 'ar' => 'مخبوزات وفطائر'],
                'image_key' => 'bakery_bread',
            ],
            [
                'slug' => 'butchery-meat',
                'name' => ['en' => 'Fresh & Frozen Meats', 'ar' => 'لحوم طازجة ومجمدة'],
                'image_key' => 'meat_beef',
            ],
        ];

        $categoriesMap = [];
        foreach ($categoriesData as $c) {
            $cat = IndustryCategory::create([
                'slug' => $c['slug'],
                'name' => $c['name'],
            ]);
            $categoriesMap[$c['slug']] = $cat->id;

            $localImg = storage_path("app/seed_images/{$c['image_key']}.jpg");
            if (File::exists($localImg)) {
                try {
                    $cat->addMedia($localImg)
                        ->preservingOriginal()
                        ->toMediaCollection('industry_category_images');
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        $this->command?->info('Seeded ' . count($categoriesMap) . ' categories.');

        // 55 Realistic Supermarket & Food Products
        $productsCatalog = [
            // --- 1. FRESH POULTRY (5 Products) ---
            [
                'category' => 'fresh-poultry',
                'name' => ['en' => 'Shahd Fresh Whole Chicken', 'ar' => 'دجاج كامل طازج شهد'],
                'description' => [
                    'en' => 'Fresh premium farm-raised whole chicken, cleaned and ready to cook with superior tenderness and taste.',
                    'ar' => 'دجاج طازج كامل من مزارعنا الفاخرة، مغسول ومجهز للطهي بأعلى معايير الجودة والنظافة والطعم اللذيذ.',
                ],
                'image_key' => 'chicken_whole',
                'variants' => [
                    ['value' => '1000 gm', 'price' => 145.0, 'agent_price' => 130.0, 'discount' => 5],
                    ['value' => '1200 gm', 'price' => 170.0, 'agent_price' => 152.0, 'discount' => 0],
                    ['value' => '1400 gm', 'price' => 195.0, 'agent_price' => 175.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'fresh-poultry',
                'name' => ['en' => 'Shahd Fresh Chicken Breast Fillet', 'ar' => 'صدور دجاج فيليه طازجة شهد'],
                'description' => [
                    'en' => 'Tender, skinless and boneless fresh chicken breasts, perfectly trimmed for grills, curries, and pan-frying.',
                    'ar' => 'صدور دجاج طازجة مخلية من العظم والجلد بدون دهون، مثالية للشوي والبانية والطهي الصحي اليومي.',
                ],
                'image_key' => 'chicken_fillet',
                'variants' => [
                    ['value' => '500 gm', 'price' => 110.0, 'agent_price' => 98.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 210.0, 'agent_price' => 188.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'fresh-poultry',
                'name' => ['en' => 'Shahd Fresh Boneless Chicken Thighs (Shish Taouk)', 'ar' => 'شيش طاووق وراك مخلية طازجة'],
                'description' => [
                    'en' => 'Juicy and tender skinless boneless chicken thighs, ideal for skewered Shish Taouk and savory casseroles.',
                    'ar' => 'أوراك دجاج طازجة مخلية تماماً طرية وغنية بالنكهة، جاهزة لعمل ألذ شيش طاووق مشوي على الفحم أو في الفرن.',
                ],
                'image_key' => 'chicken_fillet',
                'variants' => [
                    ['value' => '500 gm', 'price' => 95.0, 'agent_price' => 85.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 185.0, 'agent_price' => 165.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'fresh-poultry',
                'name' => ['en' => 'Shahd Fresh Chicken Drumsticks', 'ar' => 'دبابيس دجاج طازجة شهد'],
                'description' => [
                    'en' => 'Fresh plump chicken drumsticks with skin-on for crispy roasting, air-frying, and family meals.',
                    'ar' => 'دبابيس دجاج طازجة ممتازة وطرية، مثالية للسلق والتحمير أو الطهي في القلاية الهوائية للأطفال.',
                ],
                'image_key' => 'chicken_whole',
                'variants' => [
                    ['value' => '1000 gm', 'price' => 160.0, 'agent_price' => 142.0, 'discount' => 0],
                    ['value' => '2000 gm (Family Pack)', 'price' => 305.0, 'agent_price' => 270.0, 'discount' => 12],
                ],
            ],
            [
                'category' => 'fresh-poultry',
                'name' => ['en' => 'Shahd Fresh Chicken Liver & Gizzard', 'ar' => 'كبد وقوانص دجاج طازجة شهد'],
                'description' => [
                    'en' => 'Freshly harvested, cleaned chicken livers and gizzards, packed with rich iron and distinct savory flavor.',
                    'ar' => 'كبد وقوانص دجاج طازجة ونظيفة تماماً، غنية بالحديد ومثالية لعمل طواجن الأرز بالخلطة والوجبات الشعبية الشهية.',
                ],
                'image_key' => 'chicken_whole',
                'variants' => [
                    ['value' => '500 gm', 'price' => 65.0, 'agent_price' => 58.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 125.0, 'agent_price' => 110.0, 'discount' => 0],
                ],
            ],

            // --- 2. FROZEN READY MEALS (5 Products) ---
            [
                'category' => 'frozen-meals',
                'name' => ['en' => 'Shahd Classic Breaded Chicken Pane', 'ar' => 'بانيه دجاج مقرمش كلاسيك شهد'],
                'description' => [
                    'en' => 'Crispy golden breaded 100% chicken breast fillets, ready in minutes in the oven or oil.',
                    'ar' => 'قطع بانيه دجاج صافي 100% متبلة بخلطة بقسماط ذهبية مقرمشة، سريعة التسوية ومحببة لجميع أفراد الأسرة.',
                ],
                'image_key' => 'chicken_pane',
                'variants' => [
                    ['value' => '400 gm (8 Pieces)', 'price' => 85.0, 'agent_price' => 75.0, 'discount' => 0],
                    ['value' => '1000 gm (20 Pieces)', 'price' => 195.0, 'agent_price' => 172.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'frozen-meals',
                'name' => ['en' => 'Shahd Golden Chicken Nuggets', 'ar' => 'ناجتس دجاج ذهبي للأطفال'],
                'description' => [
                    'en' => 'Bite-sized tender chicken nuggets with crunchy crust, fun and nutritious for quick lunches.',
                    'ar' => 'قطع ناجتس دجاج ذهبية شهية ومقرمشة من الخارج وطرية من الداخل، محبوبة للأطفال واللانش بوكس.',
                ],
                'image_key' => 'chicken_pane',
                'variants' => [
                    ['value' => '400 gm', 'price' => 75.0, 'agent_price' => 66.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 170.0, 'agent_price' => 150.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'frozen-meals',
                'name' => ['en' => 'Shahd Gourmet Chicken Burger Patties', 'ar' => 'برجر دجاج جامبو فاخر'],
                'description' => [
                    'en' => 'Thick and juicy chicken burger patties seasoned with aromatic herbs and oriental spices.',
                    'ar' => 'برجر دجاج سميك جامبو متبل بأشهى البهارات والتوابل الشرقية، جاهز للشواء والتحمير في 5 دقائق.',
                ],
                'image_key' => 'chicken_pane',
                'variants' => [
                    ['value' => '8 Pieces (800 gm)', 'price' => 135.0, 'agent_price' => 120.0, 'discount' => 5],
                    ['value' => '12 Pieces (1200 gm)', 'price' => 190.0, 'agent_price' => 168.0, 'discount' => 12],
                ],
            ],
            [
                'category' => 'frozen-meals',
                'name' => ['en' => 'Shahd Seasoned Chicken Kofta', 'ar' => 'كفتة دجاج متبلة على الفحم'],
                'description' => [
                    'en' => 'Pre-seasoned minced chicken kofta sticks infused with parsley, onions, and charcoal grill spices.',
                    'ar' => 'أصابع كفتة دجاج متبلة بالبقدونس والبصل وبهارات الشواء، جاهزة للشوي في الفرن أو على الجريل برائحة لا تقاوم.',
                ],
                'image_key' => 'chicken_fillet',
                'variants' => [
                    ['value' => '400 gm', 'price' => 80.0, 'agent_price' => 70.0, 'discount' => 0],
                    ['value' => '800 gm', 'price' => 150.0, 'agent_price' => 132.0, 'discount' => 7],
                ],
            ],
            [
                'category' => 'frozen-meals',
                'name' => ['en' => 'Shahd Crispy Chicken Cordon Bleu', 'ar' => 'كوردون بلو دجاج محشي جبن ومورتديلا'],
                'description' => [
                    'en' => 'Stuffed chicken fillets filled with rich melted cheddar cheese and smoked beef, coated in golden crumbs.',
                    'ar' => 'لفائف صدور دجاج محشوة بجبنة الشيدر السائحة واللحم البقري المدخن ومغطاة بطبقة كرانشي مقرمشة فاخرة.',
                ],
                'image_key' => 'chicken_pane',
                'variants' => [
                    ['value' => '500 gm (4 Pieces)', 'price' => 125.0, 'agent_price' => 110.0, 'discount' => 0],
                ],
            ],

            // --- 3. CRISPY & FRIED CHICKEN (5 Products) ---
            [
                'category' => 'crispy-fried',
                'name' => ['en' => 'Shahd Extra Crispy Chicken Strips (Original)', 'ar' => 'استربس دجاج كرانشي سوبر مقرمش (عادي)'],
                'description' => [
                    'en' => 'Tender whole muscle chicken tenderloins with ultra-crisp flaked coating. Zero preservatives.',
                    'ar' => 'أصابع استربس دجاج صافي بتتبيلة الأعشاب الكلاسيكية وطبقة قرمشة مضاعفة تدوم طويلاً.',
                ],
                'image_key' => 'chicken_strips',
                'variants' => [
                    ['value' => '400 gm', 'price' => 95.0, 'agent_price' => 84.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 220.0, 'agent_price' => 195.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'crispy-fried',
                'name' => ['en' => 'Shahd Hot & Spicy Chicken Strips (Flaming)', 'ar' => 'استربس دجاج حار نار سبايسي'],
                'description' => [
                    'en' => 'Fiery seasoned crispy chicken strips infused with red chili extract and cayenne pepper.',
                    'ar' => 'استربس دجاج سبايسي حار بمذاق الشطة الحارة والفلفل الأحمر لعشاق النكهات القوية المقرمشة.',
                ],
                'image_key' => 'chicken_strips',
                'variants' => [
                    ['value' => '400 gm', 'price' => 98.0, 'agent_price' => 86.0, 'discount' => 0],
                    ['value' => '1000 gm', 'price' => 225.0, 'agent_price' => 198.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'crispy-fried',
                'name' => ['en' => 'Shahd Crunchy Popcorn Chicken', 'ar' => 'بوب كورن دجاج كرانشي ميني'],
                'description' => [
                    'en' => 'Crispy bite-sized popcorn chicken snacks, great with honey mustard, BBQ dip, or party bowls.',
                    'ar' => 'حبات بوب كورن دجاج مقرمشة وشهية، وجبة سريعة وممتعة للتجمعات والباربيكيو والمباريات.',
                ],
                'image_key' => 'chicken_strips',
                'variants' => [
                    ['value' => '350 gm', 'price' => 70.0, 'agent_price' => 62.0, 'discount' => 0],
                    ['value' => '750 gm', 'price' => 140.0, 'agent_price' => 124.0, 'discount' => 7],
                ],
            ],
            [
                'category' => 'crispy-fried',
                'name' => ['en' => 'Shahd Breaded Crispy Chicken Wings (Zesty)', 'ar' => 'أجنحة دجاج مقرمشة متبلة زيستي'],
                'description' => [
                    'en' => 'Spicy crispy chicken wings with special rub and golden coating, perfect for buffalo sauces.',
                    'ar' => 'أجنحة دجاج مقرمشة بخلطة توابل البافلو الحارة، وجبة سريعة تنقلك لأجواء المطاعم العالمية.',
                ],
                'image_key' => 'chicken_wings',
                'variants' => [
                    ['value' => '700 gm', 'price' => 115.0, 'agent_price' => 102.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'crispy-fried',
                'name' => ['en' => 'Shahd Crunchy Chicken Mozzarella Bites', 'ar' => 'بايتس دجاج مقرمش محشو موزاريلا'],
                'description' => [
                    'en' => 'Crispy breaded chicken rounds with stretchy gooey melted mozzarella core.',
                    'ar' => 'كرات دجاج مقرمشة محشوة بقلب من جبنة الموزاريلا المطاطية اللذيذة سريعة الذوبان.',
                ],
                'image_key' => 'chicken_pane',
                'variants' => [
                    ['value' => '400 gm', 'price' => 90.0, 'agent_price' => 80.0, 'discount' => 0],
                ],
            ],

            // --- 4. COOKING OILS & GHEE (5 Products) ---
            [
                'category' => 'cooking-oils',
                'name' => ['en' => 'Rawabi Pure Sunflower Oil', 'ar' => 'زيت عباد الشمس النقي روابي'],
                'description' => [
                    'en' => '100% pure refined sunflower oil with vitamin E, ideal for frying, baking, and healthy light cooking.',
                    'ar' => 'زيت عباد شمس نقي 100% معزز بفيتامين هـ، خفيف على المعدة ومثالي للقلي والطهي الصحي اليومي.',
                ],
                'image_key' => 'cooking_oil',
                'variants' => [
                    ['value' => '800 ml', 'price' => 68.0, 'agent_price' => 60.0, 'discount' => 0],
                    ['value' => '1.5 Liter', 'price' => 125.0, 'agent_price' => 110.0, 'discount' => 6],
                    ['value' => '2.7 Liter (Family)', 'price' => 215.0, 'agent_price' => 190.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'cooking-oils',
                'name' => ['en' => 'Crystal 100% Pure Corn Oil', 'ar' => 'زيت ذرة نقي 100% كريستال'],
                'description' => [
                    'en' => 'Pure golden corn oil with high smoke point, rich in unsaturated fatty acids for heart-healthy meals.',
                    'ar' => 'زيت ذرة كريستال ذهبي نقي بدون كوليسترول، يتحمل درجات الحرارة العالية لطهي صحي ومثالي.',
                ],
                'image_key' => 'cooking_oil',
                'variants' => [
                    ['value' => '800 ml', 'price' => 74.0, 'agent_price' => 65.0, 'discount' => 0],
                    ['value' => '1.6 Liter', 'price' => 142.0, 'agent_price' => 126.0, 'discount' => 5],
                    ['value' => '3.5 Liter', 'price' => 295.0, 'agent_price' => 260.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'cooking-oils',
                'name' => ['en' => 'Wadi Food Extra Virgin Olive Oil (Cold Pressed)', 'ar' => 'زيت زيتون بكر ممتاز وادي فود (عصرة أولى على البارد)'],
                'description' => [
                    'en' => 'Cold pressed extra virgin olive oil made from hand-picked olives, full of antioxidants and earthy aroma.',
                    'ar' => 'زيت زيتون بكر ممتاز معصور على البارد من أفضل مزارع الزيتون، حموضة أقل من 0.8% ونكهة غنية وفاخرة للسلطات.',
                ],
                'image_key' => 'cooking_oil',
                'variants' => [
                    ['value' => '500 ml Glass Bottle', 'price' => 195.0, 'agent_price' => 175.0, 'discount' => 0],
                    ['value' => '1 Liter Glass Bottle', 'price' => 360.0, 'agent_price' => 320.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'cooking-oils',
                'name' => ['en' => 'El Hanem Pure Natural Cow Ghee (Samna Baladi)', 'ar' => 'سمن بلدي بقري طبيعي 100% الهانم'],
                'description' => [
                    'en' => 'Authentic Egyptian clarified butter ghee from pure fresh cow milk, delivering unmatched rich flavor.',
                    'ar' => 'سمن بلدي بقري طبيعي مرمل برائحة وطعم زمان الأصيل، يمنح أطباقك والحلويات الشرقية طعماً لا ينسى.',
                ],
                'image_key' => 'cooking_oil',
                'variants' => [
                    ['value' => '700 gm Tin', 'price' => 230.0, 'agent_price' => 205.0, 'discount' => 0],
                    ['value' => '1400 gm Tin', 'price' => 435.0, 'agent_price' => 390.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'cooking-oils',
                'name' => ['en' => 'Shahd Sesame Oil (Roasted Aromatics)', 'ar' => 'زيت سمسم محمص نقي'],
                'description' => [
                    'en' => 'Aromatic toasted sesame oil for Asian cuisines, marinades, dressings, and special poultry glazes.',
                    'ar' => 'زيت سمسم محمص نقي بنكهة شرقية عطرية قوية لإعداد تتبيلات المشويات والوجبات الآسيوية والسلطات.',
                ],
                'image_key' => 'cooking_oil',
                'variants' => [
                    ['value' => '250 ml', 'price' => 85.0, 'agent_price' => 75.0, 'discount' => 0],
                ],
            ],

            // --- 5. FROZEN VEGETABLES & FRIES (5 Products) ---
            [
                'category' => 'frozen-vegetables',
                'name' => ['en' => 'Shahd Farm Fresh Golden French Fries (9mm)', 'ar' => 'بطاطس فارم فريتس نصف مقلية ممتازة شهد'],
                'description' => [
                    'en' => 'Grade A crispy cut golden french fries, fluffy on the inside and crunchy outside. Zero trans fat.',
                    'ar' => 'بطاطس مقطعة نصف مقلية درجة أولى سريعة التحضير، مقرمشة جداً من الخارج وهشة ولذيذة من الداخل.',
                ],
                'image_key' => 'french_fries',
                'variants' => [
                    ['value' => '1000 gm', 'price' => 70.0, 'agent_price' => 62.0, 'discount' => 0],
                    ['value' => '2500 gm (Jumbo)', 'price' => 155.0, 'agent_price' => 138.0, 'discount' => 12],
                ],
            ],
            [
                'category' => 'frozen-vegetables',
                'name' => ['en' => 'Shahd Fresh Minced Molokhia (Frozen Green)', 'ar' => 'ملوخية خضراء مخروطة طازجة مجمدة'],
                'description' => [
                    'en' => 'Carefully picked and finely minced green Molokhia leaves, flash-frozen to retain vibrant color and flavor.',
                    'ar' => 'ملوخية فلاحي خضراء قطفة أولى مخروطة بعناية فائقة ومجمدة بتقنية سريعة للحفاظ على اللون الأخضر الزاهي والقوام.',
                ],
                'image_key' => 'vegetables',
                'variants' => [
                    ['value' => '400 gm', 'price' => 18.0, 'agent_price' => 15.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'frozen-vegetables',
                'name' => ['en' => 'Shahd Green Peas with Diced Carrots', 'ar' => 'بسلة بالجزر خضار مشكل مجمد'],
                'description' => [
                    'en' => 'Sweet garden green peas combined with tender diced carrots, ready to simmer into hearty stews.',
                    'ar' => 'بسلة سكرية طازجة مع قطع الجزر البرتقالي المجهزة للطهي الفوري في ألذ طواجن الخضار والشوربات.',
                ],
                'image_key' => 'vegetables',
                'variants' => [
                    ['value' => '400 gm', 'price' => 24.0, 'agent_price' => 20.0, 'discount' => 0],
                    ['value' => '800 gm', 'price' => 44.0, 'agent_price' => 38.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'frozen-vegetables',
                'name' => ['en' => 'Shahd Extra Green Okra (Bamia Extra)', 'ar' => 'بامية إكسترا صغيرة نمرة 1'],
                'description' => [
                    'en' => 'Hand-selected tender mini okra pods, perfect for authentic Egyptian Bamia stews and tagines.',
                    'ar' => 'أصابع بامية صغيرة إكسترا مقموعة باليد ومجمدة على الفور، لعمل أحلى طاجن بامية باللحمة في الفرن.',
                ],
                'image_key' => 'vegetables',
                'variants' => [
                    ['value' => '400 gm', 'price' => 35.0, 'agent_price' => 30.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'frozen-vegetables',
                'name' => ['en' => 'Shahd Chopped Garden Spinach (Sabanekh)', 'ar' => 'سبانخ خضراء مفرومة طازجة'],
                'description' => [
                    'en' => 'Nutrient-rich chopped spinach leaves, washed multiple times and quick-frozen for rich homemade stews.',
                    'ar' => 'سبانخ خضراء طازجة مغسولة ومفرومة بعناية، غنية بالحديد والفيتامينات وجاهزة للطهي الصحي.',
                ],
                'image_key' => 'vegetables',
                'variants' => [
                    ['value' => '400 gm', 'price' => 20.0, 'agent_price' => 17.0, 'discount' => 0],
                ],
            ],

            // --- 6. DAIRY & CHEESE (5 Products) ---
            [
                'category' => 'dairy-cheese',
                'name' => ['en' => 'Domty Shredded Mozzarella Cheese (100% Pure Milk)', 'ar' => 'جبنة موزاريلا مبشورة طبيعية 100% دومتي'],
                'description' => [
                    'en' => 'Premium shredded mozzarella from pure cow milk with superior stretch and golden browning for pizzas and pastas.',
                    'ar' => 'جبنة موزاريلا مبشورة طبيعية بمطة غنية ومذاق رائع وتحمير ذهبي مثالي للبيتزا والمكرونة البشاميل.',
                ],
                'image_key' => 'dairy_cheese',
                'variants' => [
                    ['value' => '300 gm', 'price' => 65.0, 'agent_price' => 56.0, 'discount' => 0],
                    ['value' => '1000 gm (Jumbo)', 'price' => 185.0, 'agent_price' => 162.0, 'discount' => 10],
                ],
            ],
            [
                'category' => 'dairy-cheese',
                'name' => ['en' => 'President Vintage Aged Cheddar Cheese Block', 'ar' => 'جبنة شيدر أحمر معتقة بريزيدون'],
                'description' => [
                    'en' => 'Aged sharp red cheddar cheese with a rich, buttery depth of flavor for sandwiches and gourmet platters.',
                    'ar' => 'قالب جبنة شيدر معتقة ناعمة الملمس وغنية النكهة، مثالية للسندويتشات الفاخرة وأطباق الضيافة.',
                ],
                'image_key' => 'dairy_cheese',
                'variants' => [
                    ['value' => '250 gm Block', 'price' => 95.0, 'agent_price' => 84.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'dairy-cheese',
                'name' => ['en' => 'Lactel Full Cream Fresh Pasteurized Milk', 'ar' => 'حليب كامل الدسم طازج لاكتيل'],
                'description' => [
                    'en' => '100% natural farm milk, homogenized and pasteurized with full vitamins and calcium for the family.',
                    'ar' => 'حليب طازج معقم كامل الدسم غني بالكالسيوم والفيتامينات، بمذاق الحليب الصافي الطبيعي.',
                ],
                'image_key' => 'milk_bottle',
                'variants' => [
                    ['value' => '1 Liter Carton', 'price' => 45.0, 'agent_price' => 39.0, 'discount' => 0],
                    ['value' => '6x 1 Liter (Pack)', 'price' => 255.0, 'agent_price' => 225.0, 'discount' => 6],
                ],
            ],
            [
                'category' => 'dairy-cheese',
                'name' => ['en' => 'Lurpak Pure Natural Unsalted Butter', 'ar' => 'زبدة طبيعية لورباك غير مملحة'],
                'description' => [
                    'en' => 'World-renowned Danish unsalted butter crafted from pure fresh cream for unmatched baking and cooking.',
                    'ar' => 'زبدة دنماركية طبيعية فاخرة مصنوعة من القشطة الطازجة، تمنح الحلويات والمخبوزات طعماً غنياً لا يضاهى.',
                ],
                'image_key' => 'dairy_cheese',
                'variants' => [
                    ['value' => '200 gm Block', 'price' => 110.0, 'agent_price' => 98.0, 'discount' => 0],
                    ['value' => '400 gm Block', 'price' => 210.0, 'agent_price' => 188.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'dairy-cheese',
                'name' => ['en' => 'Traditional Aged Roumi Cheese (Goubnah Roumy Batarekh)', 'ar' => 'جبن رومي قديم بطارخ فاخر'],
                'description' => [
                    'en' => 'Authentic Egyptian aged Roumi cheese with piquant savory crystals and deep nutty notes.',
                    'ar' => 'جبن رومي مصري أصيل معتق على مدار شهور طويلة، مبطرخ بنكهة قوية ومذاق لا يقاوم لمحبي الجبن الأصيل.',
                ],
                'image_key' => 'dairy_cheese',
                'variants' => [
                    ['value' => '250 gm Slices', 'price' => 85.0, 'agent_price' => 76.0, 'discount' => 0],
                    ['value' => '500 gm Block', 'price' => 165.0, 'agent_price' => 146.0, 'discount' => 5],
                ],
            ],

            // --- 7. RICE, PASTA & GRAINS (5 Products) ---
            [
                'category' => 'rice-pasta-grains',
                'name' => ['en' => 'El Saa Premium Extra White Egyptian Rice', 'ar' => 'أرز مصري فاخر عريض الحبة الساعة'],
                'description' => [
                    'en' => '100% clean and polished Camolino Egyptian round grain rice, sorted for fluffy cooking every time.',
                    'ar' => 'أرز مصري فاخر عريض الحبة مغسول ومنقى آلياً بنسبة 100%، يمنحك أرزاً مفلفلاً وشهياً لكل وجبة.',
                ],
                'image_key' => 'rice_grain',
                'variants' => [
                    ['value' => '1 Kg Bag', 'price' => 38.0, 'agent_price' => 33.0, 'discount' => 0],
                    ['value' => '5 Kg Bag', 'price' => 180.0, 'agent_price' => 158.0, 'discount' => 6],
                ],
            ],
            [
                'category' => 'rice-pasta-grains',
                'name' => ['en' => 'Abu Kass Royal Long Grain Indian Basmati Rice (Golden Sella)', 'ar' => 'أرز بسمتي هندي ملكي طويل الحبة أبو كاس'],
                'description' => [
                    'en' => 'Extra long grain aged Indian aromatic Basmati rice, expands to twice its size with fragrant nutty notes.',
                    'ar' => 'أرز بسمتي هندي أصيل طويل الحبة درجة أولى، يتضاعف حجم الحبة مع الطهي برائحة زكية للكبسة والبرياني.',
                ],
                'image_key' => 'rice_grain',
                'variants' => [
                    ['value' => '1 Kg Bag', 'price' => 95.0, 'agent_price' => 84.0, 'discount' => 0],
                    ['value' => '5 Kg Bag', 'price' => 450.0, 'agent_price' => 395.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'rice-pasta-grains',
                'name' => ['en' => 'Italiano Premium Penne Rigate Pasta (100% Durum Semolina)', 'ar' => 'مكرونة قلم بيني ريجاتي إيطاليانو (سيمولينا قمح صلب)'],
                'description' => [
                    'en' => 'Crafted from 100% golden durum wheat semolina for perfect al dente texture that holds sauce beautifully.',
                    'ar' => 'مكرونة إيطاليانو من أجود أنواع قمح السيمولينا الصلب، تحافظ على قوامها المتماسك وتمتص الصوص بامتياز.',
                ],
                'image_key' => 'rice_grain',
                'variants' => [
                    ['value' => '400 gm Bag', 'price' => 22.0, 'agent_price' => 19.0, 'discount' => 0],
                    ['value' => '1000 gm Bag', 'price' => 52.0, 'agent_price' => 45.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'rice-pasta-grains',
                'name' => ['en' => 'Italiano Authentic Long Spaghetti Pasta', 'ar' => 'مكرونة اسباجيتي طويلة إيطاليانو'],
                'description' => [
                    'en' => 'Classic long spaghetti pasta ribbons for traditional Bolognese, carbonara, and tomato basil dishes.',
                    'ar' => 'مكرونة اسباجيتي كلاسيكية فاخرة سريعة الطهي، متناسقة القوام لتحضير أشهى وجبات البولونيز والصلصة الحمراء.',
                ],
                'image_key' => 'rice_grain',
                'variants' => [
                    ['value' => '400 gm Bag', 'price' => 22.0, 'agent_price' => 19.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'rice-pasta-grains',
                'name' => ['en' => 'Doha Golden Yellow Lentils (Premium Cleaned)', 'ar' => 'عدس أصفر نقي فاخر الضحى'],
                'description' => [
                    'en' => 'Clean, sorted golden yellow lentils, cooks rapidly into soothing creamy soups and Egyptian Koshari.',
                    'ar' => 'عدس أصفر منقى ومفروز آلياً بدون أي شوائب، سريع النضج لتحضير أدفأ وألذ شوربة عدس في الشتاء.',
                ],
                'image_key' => 'rice_grain',
                'variants' => [
                    ['value' => '500 gm Pack', 'price' => 36.0, 'agent_price' => 31.0, 'discount' => 0],
                ],
            ],

            // --- 8. SAUCES & CONDIMENTS (5 Products) ---
            [
                'category' => 'sauces-condiments',
                'name' => ['en' => 'Heinz Classic Rich Tomato Ketchup (Glass / Squeeze)', 'ar' => 'كاتشب طماطم كلاسيك هاينز'],
                'description' => [
                    'en' => 'Grown not made - world famous thick tomato ketchup with vibrant balance of sweet and tangy taste.',
                    'ar' => 'كاتشب هاينز الأصلي المصنوع من أجود حبات الطماطم الطبيعية النضرة، بدون ألوان صناعية أو مواد حافظة.',
                ],
                'image_key' => 'sauce_ketchup',
                'variants' => [
                    ['value' => '300 gm Squeeze', 'price' => 38.0, 'agent_price' => 33.0, 'discount' => 0],
                    ['value' => '850 gm Family Squeeze', 'price' => 85.0, 'agent_price' => 74.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'sauces-condiments',
                'name' => ['en' => 'Heinz Real Creamy Mayonnaise', 'ar' => 'مايونيز ريل كريمي فاخر هاينز'],
                'description' => [
                    'en' => 'Velvety smooth real mayonnaise made with pasteurized eggs for gourmet burgers, wraps, and salads.',
                    'ar' => 'مايونيز هاينز الكريمي الغني بالبيض الطبيعي والليمون، يمنح السندويتشات والبرجر مذاقاً ناعماً وفاخراً.',
                ],
                'image_key' => 'sauce_ketchup',
                'variants' => [
                    ['value' => '310 gm Bottle', 'price' => 48.0, 'agent_price' => 41.0, 'discount' => 0],
                    ['value' => '540 gm Jar', 'price' => 78.0, 'agent_price' => 68.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'sauces-condiments',
                'name' => ['en' => 'Sweet Baby Ray\'s Hickory Smoked BBQ Sauce', 'ar' => 'صوص باربيكيو مدخن فاخر سويت بيبي'],
                'description' => [
                    'en' => 'Authentic American hickory smoked barbecue sauce with rich molasses and secret spice blend.',
                    'ar' => 'صلصة باربيكيو مدخنة برائحة خشب الهيكوري الطبيعي والتوابل السرية لشواء الدجاج والبرجر.',
                ],
                'image_key' => 'sauce_ketchup',
                'variants' => [
                    ['value' => '510 gm Bottle', 'price' => 120.0, 'agent_price' => 105.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'sauces-condiments',
                'name' => ['en' => 'Shahd Secret Herb & Poultry Rub Seasoning', 'ar' => 'بهارات دجاج وتتبيلة شواء خاصة شهد'],
                'description' => [
                    'en' => 'Handcrafted blend of 14 aromatic spices, garlic, onion, sumac, and dried Mediterranean herbs.',
                    'ar' => 'توليفة توابل سرية من 14 نوع بهارات طبيعية مع الثوم والسماق والأعشاب العطرية لتتبيل الدجاج المشوي والمقلي.',
                ],
                'image_key' => 'sauce_ketchup',
                'variants' => [
                    ['value' => '120 gm Shaker Jar', 'price' => 45.0, 'agent_price' => 39.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'sauces-condiments',
                'name' => ['en' => 'Hidden Valley Original Creamy Ranch Dressing', 'ar' => 'دريسنج رانش كريمي بالأعشاب'],
                'description' => [
                    'en' => 'Rich creamy buttermilk ranch dip infused with fresh dill, parsley, and garlic for wings and greens.',
                    'ar' => 'صوص رانش كريمي غني بالأعشاب واللبن الرائب والثوم، الغموس المفضل لأجنحة الدجاج والبطاطس المقرمشة.',
                ],
                'image_key' => 'sauce_ketchup',
                'variants' => [
                    ['value' => '473 ml Bottle', 'price' => 140.0, 'agent_price' => 124.0, 'discount' => 0],
                ],
            ],

            // --- 9. TEAS & BEVERAGES (5 Products) ---
            [
                'category' => 'teas-beverages',
                'name' => ['en' => 'Ahmad Tea Pure Ceylon Special Blend Tea', 'ar' => 'شاي سيلاني أسود فاخر أحمد تي سبيشال'],
                'description' => [
                    'en' => 'Finest hand-plucked loose leaf Ceylon black tea with golden color and subtle bergamot finish.',
                    'ar' => 'شاي سيلاني أسود نقي منتقى بعناية من مرتفعات سريلانكا، بلون كهرماني صافي ومذاق أصيل لا يقاوم.',
                ],
                'image_key' => 'tea_box',
                'variants' => [
                    ['value' => '250 gm Tin', 'price' => 95.0, 'agent_price' => 84.0, 'discount' => 0],
                    ['value' => '100 Tea Bags Pack', 'price' => 115.0, 'agent_price' => 100.0, 'discount' => 6],
                ],
            ],
            [
                'category' => 'teas-beverages',
                'name' => ['en' => 'Twinings Pure Green Tea with Spearmint Leaves', 'ar' => 'شاي أخضر نقي بالنعناع الفريش تويننجز'],
                'description' => [
                    'en' => 'Soothing blend of steamed green tea leaves and cool aromatic spearmint for refreshing detox.',
                    'ar' => 'توليفة مهدئة ومنعشة من الشاي الأخضر النقي مع أوراق النعناع الفواحة، غني بمضادات الأكسدة.',
                ],
                'image_key' => 'tea_box',
                'variants' => [
                    ['value' => '25 Tea Bags Box', 'price' => 65.0, 'agent_price' => 57.0, 'discount' => 0],
                    ['value' => '50 Tea Bags Box', 'price' => 118.0, 'agent_price' => 104.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'teas-beverages',
                'name' => ['en' => 'Twinings Royal Earl Grey Tea with Bergamot', 'ar' => 'شاي إيرل جراي ملكي بالبرجموت تويننجز'],
                'description' => [
                    'en' => 'Classic British royal black tea elegantly scented with cold-pressed natural citrus bergamot.',
                    'ar' => 'شاي إنجليزي ملكي فاخر برائحة زيت البرجموت الحمضي المنعش، تجربة استرخاء بريطانية راقية.',
                ],
                'image_key' => 'tea_box',
                'variants' => [
                    ['value' => '25 Tea Bags Box', 'price' => 70.0, 'agent_price' => 62.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'teas-beverages',
                'name' => ['en' => 'Isis Organic Pure Dried Hibiscus Flowers (Karkadeh)', 'ar' => 'كركديه أسواني عضوي مجفف إيزيس'],
                'description' => [
                    'en' => '100% natural organic whole hibiscus calyces from Aswan, yields deep ruby red iced or warm punch.',
                    'ar' => 'أزهار كركديه أسواني نقي 100% درجة أولى بلون ياقوتي مميز، منعش ومرطب صيفاً ومفيد لصحة القلب.',
                ],
                'image_key' => 'tea_box',
                'variants' => [
                    ['value' => '250 gm Pouch', 'price' => 55.0, 'agent_price' => 48.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'teas-beverages',
                'name' => ['en' => 'Isis Soothing Pure Chamomile Herbal Infusion', 'ar' => 'بابونج كاموميل طبيعي مهدئ إيزيس'],
                'description' => [
                    'en' => 'Gentle organic chamomile flower infusion for restful sleep and natural digestive comfort.',
                    'ar' => 'شاي أعشاب البابونج الطبيعي المهدئ للأعصاب والمساعد على الاسترخاء والنوم الهادئ والمريح.',
                ],
                'image_key' => 'tea_box',
                'variants' => [
                    ['value' => '20 Filter Bags', 'price' => 42.0, 'agent_price' => 36.0, 'discount' => 0],
                ],
            ],

            // --- 10. BAKERY & PASTRIES (5 Products) ---
            [
                'category' => 'bakery-pastries',
                'name' => ['en' => 'Rich Bake 100% Whole Wheat Sliced Toast Bread', 'ar' => 'توست حبوب كاملة بني ريتش بيك'],
                'description' => [
                    'en' => 'Soft freshly baked whole grain brown sliced toast, packed with natural dietary fiber for healthy breakfast.',
                    'ar' => 'خبز توست بني مصنوع من دقيق القمح الكامل 100% غني بالألياف الطبيعية لوجبة إفطار صحية ومشبعة.',
                ],
                'image_key' => 'bakery_bread',
                'variants' => [
                    ['value' => '500 gm Loaf', 'price' => 45.0, 'agent_price' => 39.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'bakery-pastries',
                'name' => ['en' => 'Mission Soft White Flour Tortilla Wraps (8 Pieces)', 'ar' => 'خبز تورتيلا أبيض طري مكسيكي ميشن'],
                'description' => [
                    'en' => 'Ultra soft and pliable white flour tortillas for burritos, shawarma, fajitas, and cheesy quesadillas.',
                    'ar' => 'رقائق خبز التورتيلا المكسيكي الطري سهل اللف لتحضير ألذ ساندوتشات الشاورما والفاهيتا والكاساديا.',
                ],
                'image_key' => 'bakery_bread',
                'variants' => [
                    ['value' => '8 Wraps (25cm)', 'price' => 60.0, 'agent_price' => 52.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'bakery-pastries',
                'name' => ['en' => 'Shahd Traditional Egyptian Feteer Meshaltet (Butter Infused)', 'ar' => 'فطير مشلتت بلدي بالسمن الطبيعي جاهز للتسوية'],
                'description' => [
                    'en' => 'Authentic flaky layered Egyptian pastry made with pure butter, frozen raw to bake hot at home.',
                    'ar' => 'فطير مشلتت فلاحي مورق ومخبوز بالسمن الطبيعي، مجمد وجاهز للخبز في الفرن ليقدم ساخناً مع العسل والجبن.',
                ],
                'image_key' => 'bakery_bread',
                'variants' => [
                    ['value' => '1 Medium Pie (600 gm)', 'price' => 75.0, 'agent_price' => 66.0, 'discount' => 0],
                    ['value' => '1 Jumbo Pie (1100 gm)', 'price' => 130.0, 'agent_price' => 114.0, 'discount' => 8],
                ],
            ],
            [
                'category' => 'bakery-pastries',
                'name' => ['en' => 'Oetker Ristorante Stonebaked Mini Mozzarella Pizzas', 'ar' => 'ميني بيتزا مارجريتا نابوليتان مجمدة'],
                'description' => [
                    'en' => 'Crisp Italian stonebaked pizza rounds topped with aromatic sun-ripened tomato coulis and mozzarella.',
                    'ar' => 'ميني بيتزا مجمدة مخبوزة على الحجر بجبنة الموزاريلا وصلصة الطماطم الإيطالية بالأعشاب، سريعة التسوية.',
                ],
                'image_key' => 'bakery_bread',
                'variants' => [
                    ['value' => '4 Pieces Pack (400 gm)', 'price' => 95.0, 'agent_price' => 84.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'bakery-pastries',
                'name' => ['en' => 'Gourmet French Butter Croissants (Ready to Bake)', 'ar' => 'كرواسون فرنسي بالزبدة الطبيعية جاهز للخبز'],
                'description' => [
                    'en' => 'Flaky, buttery layered French croissants prepared with premium butter, rise and bake golden in oven.',
                    'ar' => 'كرواسون فرنسي فاخر مورق بالزبدة الطبيعية، يخبز في الفرن ليمنحك رائحة المخابز الفرنسية في منزلك.',
                ],
                'image_key' => 'bakery_bread',
                'variants' => [
                    ['value' => '6 Pieces Pack', 'price' => 110.0, 'agent_price' => 96.0, 'discount' => 0],
                ],
            ],

            // --- 11. FRESH & FROZEN MEATS (5 Products) ---
            [
                'category' => 'butchery-meat',
                'name' => ['en' => 'Premium Fresh Minced Beef (85/15 Lean)', 'ar' => 'لحم بقري مفروم بلدي طازج'],
                'description' => [
                    'en' => 'Freshly ground lean beef from prime cattle with ideal 15% fat ratio for juicy meatballs, pasta and burgers.',
                    'ar' => 'لحم بقري مفروم طازج خالي من الدهون الزائدة بنسبة لحم هبر 85%، مثالي لعمل الكفتة والباشاميل والحواوشي.',
                ],
                'image_key' => 'meat_beef',
                'variants' => [
                    ['value' => '500 gm Tray', 'price' => 195.0, 'agent_price' => 175.0, 'discount' => 0],
                    ['value' => '1000 gm Tray', 'price' => 380.0, 'agent_price' => 340.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'butchery-meat',
                'name' => ['en' => 'Prime Beef Ribeye Steak Cuts', 'ar' => 'ستيك ريب آي بقري فاخر'],
                'description' => [
                    'en' => 'Well-marbled juicy ribeye steak cut, exceptionally flavorful when pan-seared with rosemary butter.',
                    'ar' => 'قطع ستيك ريب آي بقري فاخر معرق بالدهن الطبيعي، يعطيك طراوة ونكهة شواء لا تضاهى على الجريل.',
                ],
                'image_key' => 'meat_beef',
                'variants' => [
                    ['value' => '500 gm (2 Thick Steaks)', 'price' => 260.0, 'agent_price' => 230.0, 'discount' => 0],
                ],
            ],
            [
                'category' => 'butchery-meat',
                'name' => ['en' => 'Fresh Boneless Beef Cubes (Stew Cuts)', 'ar' => 'مكعبات لحم بقري أحمر للخضار والطواجن'],
                'description' => [
                    'en' => 'Tender trimmed beef cubes ready for rich braised stews, curries, and vegetable casseroles.',
                    'ar' => 'مكعبات لحم بقري أحمر صافي مقطع بحجم مثالي لطواجن البامية والبطاطس والشوربات اللذيذة.',
                ],
                'image_key' => 'meat_beef',
                'variants' => [
                    ['value' => '500 gm Tray', 'price' => 210.0, 'agent_price' => 188.0, 'discount' => 0],
                    ['value' => '1000 gm Tray', 'price' => 410.0, 'agent_price' => 368.0, 'discount' => 6],
                ],
            ],
            [
                'category' => 'butchery-meat',
                'name' => ['en' => 'Egyptian Baladi Oriental Sausage (Sogok)', 'ar' => 'سجق بلدي شرقي متبل بالخلطة الإسكندراني'],
                'description' => [
                    'en' => 'Authentic Egyptian spiced beef sausage in natural casing, seasoned with garlic, sweet pepper, and cumin.',
                    'ar' => 'سجق بلدي إسكندراني محشو لحم بقري متبل بأسرار التوابل الشرقية والطماطم والثوم لوجبات الشارع الشهيرة.',
                ],
                'image_key' => 'meat_beef',
                'variants' => [
                    ['value' => '500 gm Pack', 'price' => 175.0, 'agent_price' => 155.0, 'discount' => 0],
                    ['value' => '1000 gm Pack', 'price' => 340.0, 'agent_price' => 305.0, 'discount' => 5],
                ],
            ],
            [
                'category' => 'butchery-meat',
                'name' => ['en' => 'Gourmet Beef Burger Patties (Jumbo 150g)', 'ar' => 'برجر لحم بقري جامبو فاخر'],
                'description' => [
                    'en' => '100% prime beef burger patties with pinch of sea salt and cracked black pepper, ready to sear.',
                    'ar' => 'أقراص برجر لحم بقري جامبو صافي 100% متبل بالفلفل الأسود والملح البحري بدون إضافات صويا.',
                ],
                'image_key' => 'meat_beef',
                'variants' => [
                    ['value' => '4 Patties (600 gm)', 'price' => 180.0, 'agent_price' => 160.0, 'discount' => 0],
                    ['value' => '8 Patties (1200 gm)', 'price' => 340.0, 'agent_price' => 300.0, 'discount' => 8],
                ],
            ],
        ];

        $productCount = 0;
        $variantCount = 0;

        foreach ($productsCatalog as $p) {
            $catSlug = $p['category'];
            $categoryId = $categoriesMap[$catSlug] ?? null;

            if (! $categoryId) {
                continue;
            }

            $slug = Str::slug($p['name']['en']);
            
            // Create Product
            $product = Product::create([
                'industry_category_id' => $categoryId,
                'slug' => $slug,
                'name' => $p['name'],
                'description' => $p['description'],
                'status' => 'approved',
                'min_order_value' => 0,
                'agent_min_order_value' => 0,
            ]);

            // Attach image via local cached file
            $localImg = storage_path("app/seed_images/{$p['image_key']}.jpg");
            if (File::exists($localImg)) {
                try {
                    $product->addMedia($localImg)
                        ->preservingOriginal()
                        ->toMediaCollection('main_images');
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            $productCount++;

            // Create Variants
            foreach ($p['variants'] as $v) {
                $variantValue = $v['value'];
                $price = $v['price'];
                $agentPrice = $v['agent_price'];
                $discount = $v['discount'] ?? 0;

                $priceAfterDiscount = $discount > 0 ? round($price * (1 - ($discount / 100)), 2) : $price;

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_type_id' => $weightType->id,
                    'variant_value' => $variantValue,
                    'unit_price_usd' => $price,
                    'agent_price' => $agentPrice,
                    'price_after_discount' => $priceAfterDiscount,
                    'discount_percentage' => $discount,
                    'availability' => 'in-stock',
                ]);

                // Attach variant image
                if (File::exists($localImg)) {
                    try {
                        $variant->addMedia($localImg)
                            ->preservingOriginal()
                            ->toMediaCollection('variants_images');
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }

                $variantCount++;
            }
        }

        $this->command?->info("Catalog successfully seeded with {$productCount} products and {$variantCount} variants across " . count($categoriesMap) . " categories!");
    }
}

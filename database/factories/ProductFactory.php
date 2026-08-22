<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Product::class;

    public function definition(): array
    {
        // Product names and descriptions in both Arabic and English
        $productDetails = [
            [
                'ar' => ['name' => 'كرسي مكتب فاخر', 'desc' => 'كرسي مكتب مريح مصمم للجلوس لفترات طويلة مع دعم قطني ممتاز ومسند ذراع قابل للتعديل وتصميم عصري.'],
                'en' => ['name' => 'Luxury Office Chair', 'desc' => 'Comfortable office chair designed for long sitting periods with excellent lumbar support, adjustable armrests, and modern design.']
            ],
            [
                'ar' => ['name' => 'طاولة اجتماعات خشبية', 'desc' => 'طاولة اجتماعات خشبية فاخرة مصنوعة من خشب البلوط الصلب، تتسع لـ 12 شخصًا وتتميز بسطح مقاوم للخدوش.'],
                'en' => ['name' => 'Wooden Meeting Table', 'desc' => 'Premium wooden meeting table made from solid oak, accommodates 12 people and features a scratch-resistant surface.']
            ],
            [
                'ar' => ['name' => 'خزانة ملفات معدنية', 'desc' => 'خزانة ملفات معدنية متينة مع أقفال آمنة، مقاومة للحريق ومضادة للتآكل، مثالية لتخزين المستندات المهمة.'],
                'en' => ['name' => 'Metal File Cabinet', 'desc' => 'Sturdy metal file cabinet with secure locks, fire-resistant and anti-corrosion, perfect for storing important documents.']
            ],
            [
                'ar' => ['name' => 'جهاز كمبيوتر محمول', 'desc' => 'حاسوب محمول خفيف الوزن مع معالج قوي وذاكرة كبيرة وشاشة عالية الدقة، مثالي للعمل والترفيه.'],
                'en' => ['name' => 'Laptop Computer', 'desc' => 'Lightweight laptop with powerful processor, large memory, and high-resolution display, ideal for work and entertainment.']
            ],
            [
                'ar' => ['name' => 'شاشة عرض عالية الدقة', 'desc' => 'شاشة عرض 4K بحجم 27 بوصة مع ألوان دقيقة ومعدل تحديث عالي، مثالية للتصميم والألعاب والوسائط المتعددة.'],
                'en' => ['name' => 'High-Definition Display', 'desc' => '27-inch 4K display with accurate colors and high refresh rate, perfect for design, gaming, and multimedia.']
            ],
            [
                'ar' => ['name' => 'هاتف ذكي متطور', 'desc' => 'هاتف ذكي بكاميرا متطورة وشاشة AMOLED وبطارية تدوم طويلاً، مع أحدث نظام تشغيل وتقنيات الأمان.'],
                'en' => ['name' => 'Advanced Smartphone', 'desc' => 'Smartphone with advanced camera, AMOLED display, and long-lasting battery, featuring the latest operating system and security technologies.']
            ],
            [
                'ar' => ['name' => 'آلة تصوير مستندات', 'desc' => 'ماسح ضوئي عالي السرعة للمستندات مع تقنية التعرف الضوئي على الحروف وميزات متقدمة لمعالجة الصور والحفظ السحابي.'],
                'en' => ['name' => 'Document Scanner', 'desc' => 'High-speed document scanner with OCR technology and advanced features for image processing and cloud storage.']
            ],
            [
                'ar' => ['name' => 'مكيف هواء صحراوي', 'desc' => 'مكيف هواء مصمم خصيصًا للمناطق الصحراوية، يعمل بكفاءة في درجات الحرارة العالية مع استهلاك منخفض للطاقة.'],
                'en' => ['name' => 'Desert Air Conditioner', 'desc' => 'Air conditioner specifically designed for desert regions, operating efficiently in high temperatures with low energy consumption.']
            ],
            [
                'ar' => ['name' => 'ثلاجة منزلية كبيرة', 'desc' => 'ثلاجة عائلية كبيرة بسعة 700 لتر مع تقنية التبريد المزدوج وميزات الحفاظ على الطازج وموفرة للطاقة.'],
                'en' => ['name' => 'Large Home Refrigerator', 'desc' => '700-liter family refrigerator with dual cooling technology, freshness preservation features, and energy-efficient operation.']
            ],
            [
                'ar' => ['name' => 'غسالة ملابس أوتوماتيكية', 'desc' => 'غسالة ملابس أوتوماتيكية سعة 10 كجم مع برامج متعددة، تقنية البخار، وكفاءة عالية في استهلاك الماء والطاقة.'],
                'en' => ['name' => 'Automatic Washing Machine', 'desc' => '10kg automatic washing machine with multiple programs, steam technology, and high water and energy efficiency.']
            ],
            [
                'ar' => ['name' => 'فرن كهربائي حديث', 'desc' => 'فرن كهربائي متعدد الوظائف مع شاشة لمس وتسخين سريع وتوزيع حراري متساوٍ وسهولة في التنظيف.'],
                'en' => ['name' => 'Modern Electric Oven', 'desc' => 'Multifunctional electric oven with touchscreen, rapid heating, even heat distribution, and easy cleaning.']
            ],
            [
                'ar' => ['name' => 'مكنسة كهربائية قوية', 'desc' => 'مكنسة كهربائية بقوة شفط عالية ونظام تصفية متعدد المراحل، مثالية للمنازل مع الحيوانات الأليفة والحساسية.'],
                'en' => ['name' => 'Powerful Vacuum Cleaner', 'desc' => 'Vacuum cleaner with high suction power and multi-stage filtration system, ideal for homes with pets and allergies.']
            ],
            [
                'ar' => ['name' => 'مروحة سقف هادئة', 'desc' => 'مروحة سقف هادئة مع ستة أجنحة وسرعات متعددة وإضاءة LED مدمجة وتحكم عن بعد لراحة قصوى.'],
                'en' => ['name' => 'Quiet Ceiling Fan', 'desc' => 'Silent ceiling fan with six blades, multiple speeds, integrated LED lighting, and remote control for maximum comfort.']
            ],
            [
                'ar' => ['name' => 'سخان مياه كهربائي', 'desc' => 'سخان مياه كهربائي سريع ذو كفاءة عالية في الطاقة مع خزان مقاوم للصدأ وتحكم دقيق في درجة الحرارة.'],
                'en' => ['name' => 'Electric Water Heater', 'desc' => 'Fast, energy-efficient electric water heater with rust-resistant tank and precise temperature control.']
            ],
            [
                'ar' => ['name' => 'جهاز تنقية الهواء', 'desc' => 'جهاز تنقية هواء مع فلتر HEPA ومستشعر جودة الهواء ووضع السكون الليلي، مثالي للمنازل والمكاتب.'],
                'en' => ['name' => 'Air Purifier', 'desc' => 'Air purifier with HEPA filter, air quality sensor, and night silent mode, perfect for homes and offices.']
            ],
            [
                'ar' => ['name' => 'مكبر صوت بلوتوث', 'desc' => 'مكبر صوت بلوتوث محمول مع صوت استريو عالي الجودة، بطارية تدوم 20 ساعة، ومقاوم للماء، مثالي للاستخدام الخارجي.'],
                'en' => ['name' => 'Bluetooth Speaker', 'desc' => 'Portable Bluetooth speaker with high-quality stereo sound, 20-hour battery life, and water resistance, ideal for outdoor use.']
            ],
            [
                'ar' => ['name' => 'مصباح مكتب قابل للتعديل', 'desc' => 'مصباح مكتب LED قابل للتعديل مع مستويات سطوع متعددة، منفذ USB للشحن، وتصميم أنيق يناسب أي ديكور.'],
                'en' => ['name' => 'Adjustable Desk Lamp', 'desc' => 'Adjustable LED desk lamp with multiple brightness levels, USB charging port, and elegant design that complements any decor.']
            ],
            [
                'ar' => ['name' => 'سماعة رأس لاسلكية', 'desc' => 'سماعة رأس لاسلكية مع تقنية إلغاء الضوضاء، وجودة صوت فائقة، وبطارية طويلة العمر، مريحة للارتداء لفترات طويلة.'],
                'en' => ['name' => 'Wireless Headphones', 'desc' => 'Wireless headphones with noise-cancellation technology, superior sound quality, and long-lasting battery, comfortable for extended wear.']
            ],
            [
                'ar' => ['name' => 'لوحة مفاتيح ارجونومية', 'desc' => 'لوحة مفاتيح مصممة هندسيًا لتقليل إجهاد اليد والرسغ، مع إضاءة خلفية قابلة للتخصيص ومفاتيح مخصصة للإنتاجية.'],
                'en' => ['name' => 'Ergonomic Keyboard', 'desc' => 'Keyboard engineered to reduce hand and wrist strain, with customizable backlighting and dedicated productivity keys.']
            ],
            [
                'ar' => ['name' => 'فأرة حاسوب لاسلكية', 'desc' => 'فأرة حاسوب لاسلكية دقيقة مع حساسية قابلة للتعديل، أزرار قابلة للبرمجة، وتصميم مريح لليد اليمنى واليسرى.'],
                'en' => ['name' => 'Wireless Computer Mouse', 'desc' => 'Precise wireless mouse with adjustable sensitivity, programmable buttons, and comfortable design for both right and left hands.']
            ],
            [
                'ar' => ['name' => 'شاحن هاتف سريع', 'desc' => 'شاحن هاتف ذكي مع تقنية الشحن السريع، منافذ متعددة، وحماية من الحرارة الزائدة وارتفاع التيار الكهربائي.'],
                'en' => ['name' => 'Fast Phone Charger', 'desc' => 'Smart phone charger with fast charging technology, multiple ports, and protection against overheating and current surges.']
            ],
            [
                'ar' => ['name' => 'بطارية احتياطية محمولة', 'desc' => 'بطارية احتياطية بسعة 20000 مللي أمبير، تدعم الشحن السريع لأجهزة متعددة في وقت واحد، مع شاشة رقمية لعرض مستوى الطاقة.'],
                'en' => ['name' => 'Portable Power Bank', 'desc' => '20,000mAh power bank supporting fast charging for multiple devices simultaneously, with digital display showing power level.']
            ],
            [
                'ar' => ['name' => 'كاميرا مراقبة منزلية', 'desc' => 'كاميرا مراقبة منزلية ذكية مع دقة 4K، رؤية ليلية، كشف الحركة، وتخزين سحابي، يمكن التحكم بها عبر تطبيق الهاتف.'],
                'en' => ['name' => 'Home Security Camera', 'desc' => 'Smart home security camera with 4K resolution, night vision, motion detection, and cloud storage, controllable via smartphone app.']
            ],
            [
                'ar' => ['name' => 'جهاز تحكم عن بعد', 'desc' => 'جهاز تحكم عن بعد شامل متوافق مع معظم الأجهزة المنزلية، مع شاشة تعمل باللمس وإمكانية برمجة مهام متعددة بلمسة واحدة.'],
                'en' => ['name' => 'Remote Control Device', 'desc' => 'Universal remote control compatible with most home devices, featuring touchscreen and the ability to program multiple tasks with one touch.']
            ],
            [
                'ar' => ['name' => 'مكبر صوت ذكي', 'desc' => 'مكبر صوت ذكي مع مساعد صوتي مدمج، يمكنه التحكم في الأجهزة المنزلية الذكية، وتشغيل الموسيقى، وتقديم معلومات بناءً على الأوامر الصوتية.'],
                'en' => ['name' => 'Smart Speaker', 'desc' => 'Smart speaker with built-in voice assistant, capable of controlling smart home devices, playing music, and providing information based on voice commands.']
            ],
            [
                'ar' => ['name' => 'ساعة ذكية رياضية', 'desc' => 'ساعة ذكية متخصصة للرياضيين مع مراقبة معدل ضربات القلب، تتبع النوم، مقاومة للماء، وبطارية تدوم لمدة أسبوع.'],
                'en' => ['name' => 'Sports Smartwatch', 'desc' => 'Specialized smartwatch for athletes with heart rate monitoring, sleep tracking, water resistance, and week-long battery life.']
            ],
            [
                'ar' => ['name' => 'قارئ كتب إلكترونية', 'desc' => 'قارئ كتب إلكترونية مع شاشة مضادة للوهج، إضاءة قابلة للتعديل، سعة تخزين كبيرة، وبطارية تدوم لأسابيع بشحنة واحدة.'],
                'en' => ['name' => 'E-Book Reader', 'desc' => 'E-book reader with anti-glare screen, adjustable lighting, large storage capacity, and battery lasting weeks on a single charge.']
            ],
            [
                'ar' => ['name' => 'طابعة ليزر ملونة', 'desc' => 'طابعة ليزر ملونة عالية السرعة مع طباعة على الوجهين، اتصال لاسلكي، وجودة طباعة احترافية للمستندات والصور.'],
                'en' => ['name' => 'Color Laser Printer', 'desc' => 'High-speed color laser printer with duplex printing, wireless connectivity, and professional print quality for documents and photos.']
            ],
            [
                'ar' => ['name' => 'جهاز عرض فيديو', 'desc' => 'جهاز عرض فيديو 4K مع سطوع عالي، تصحيح تلقائي للانحراف، ومكبرات صوت مدمجة، مثالي للعروض التقديمية والترفيه المنزلي.'],
                'en' => ['name' => 'Video Projector', 'desc' => '4K video projector with high brightness, automatic keystone correction, and built-in speakers, perfect for presentations and home entertainment.']
            ],
            [
                'ar' => ['name' => 'راوتر انترنت عالي السرعة', 'desc' => 'راوتر انترنت مع تقنية WiFi 6، تغطية واسعة، ميزات أمان متقدمة، وإدارة سهلة عبر تطبيق الهاتف.'],
                'en' => ['name' => 'High-Speed Internet Router', 'desc' => 'Internet router with WiFi 6 technology, wide coverage, advanced security features, and easy management via smartphone app.']
            ]
        ];

        // Select a random product details
        $randomProduct = $this->faker->randomElement($productDetails);

        return [
            'industry_category_id' => null,
            'supplier_id' => null, // Will be set in the seeder
            'name' => [
                'en' => $randomProduct['en']['name'],
                'ar' => $randomProduct['ar']['name'],
            ],
            'description' => [
                'en' => $randomProduct['en']['desc'],
                'ar' => $randomProduct['ar']['desc'],
            ],
            'min_order_value' => 50,
            'agent_min_order_value' => 50,
            'is_approved' => $this->faker->boolean(80),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'custom_product_pending']),
            'oem' => $this->faker->boolean(50),
            'notes' => $this->faker->sentence(),
        ];
    }
}

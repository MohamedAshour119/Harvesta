<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentDashboardScenarioSeeder extends Seeder
{
    /**
     * Seed a deterministic Agent Dashboard scenario used across multiple cards.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();
            $startThisMonth = $now->copy()->startOfMonth();
            $startLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $endLastMonth = $startLastMonth->copy()->endOfMonth();
            $faker = \Faker\Factory::create();

            // 1) Ensure Agent user exists with a known code and commission
            $agent = User::updateOrCreate(
                ['email' => 'agent1@example.com'],
                [
                    'full_name'      => 'Agent One',
                    'password'       => Hash::make('Pa$$w0rd!'),
                    'is_agent'       => true,
                    'code'           => 'PKLTE7MG',
                    'commission'     => 10, // 10%
                    'monthly_target' => 500.00, // target expressed in commission currency
                ]
            );

            // Clean up any previous test orders for this agent to keep results deterministic
            Order::where('agent_code', 'PKLTE7MG')->delete();

            // 2) Create test customers
            $alice   = User::updateOrCreate(['email' => 'alice.client@example.com'],  ['full_name' => 'Alice Client',   'password' => Hash::make('Pa$$w0rd!')]);
            $bob     = User::updateOrCreate(['email' => 'bob.repeat@example.com'],    ['full_name' => 'Bob Repeat',     'password' => Hash::make('Pa$$w0rd!')]);
            $charlie = User::updateOrCreate(['email' => 'charlie.repeat@example.com'],['full_name' => 'Charlie Repeat', 'password' => Hash::make('Pa$$w0rd!')]);
            $david   = User::updateOrCreate(['email' => 'david.client@example.com'],  ['full_name' => 'David Client',   'password' => Hash::make('Pa$$w0rd!')]);
            // Extra random customers to diversify orders
            $extraUsers = [];
            for ($i = 0; $i < 5; $i++) {
                $email = $faker->unique()->safeEmail();
                $extraUsers[] = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'full_name' => $faker->name(),
                        'password'  => Hash::make('Pa$$w0rd!'),
                    ]
                );
            }

            // 3) Categories (translatable JSON 'name')
            $electronics = IndustryCategory::firstOrCreate(
                ['name->en' => 'Electronics'],
                ['name' => ['en' => 'Electronics', 'ar' => 'إلكترونيات']]
            );
            $furniture = IndustryCategory::firstOrCreate(
                ['name->en' => 'Furniture'],
                ['name' => ['en' => 'Furniture', 'ar' => 'أثاث']]
            );

            // 4) Products assigned to categories
            $laptop = Product::firstOrCreate(
                ['name->en' => 'Test Laptop'],
                [
                    'industry_category_id' => $electronics->id,
                    'name'        => ['en' => 'Test Laptop', 'ar' => 'حاسوب محمول اختبار'],
                    'description' => ['en' => 'Deterministic laptop', 'ar' => 'حاسوب محدد للاختبار'],
                    'min_order_value' => 50,
                    'is_approved' => true,
                    'status' => 'approved',
                    'oem' => false,
                    'notes' => 'Seeded for agent dashboard tests'
                ]
            );
            $chair = Product::firstOrCreate(
                ['name->en' => 'Office Chair'],
                [
                    'industry_category_id' => $furniture->id,
                    'name'        => ['en' => 'Office Chair', 'ar' => 'كرسي مكتب'],
                    'description' => ['en' => 'Deterministic chair', 'ar' => 'كرسي محدد للاختبار'],
                    'min_order_value' => 50,
                    'is_approved' => true,
                    'status' => 'approved',
                    'oem' => false,
                    'notes' => 'Seeded for agent dashboard tests'
                ]
            );

            // Helper to create an Order with all required fields (per latest migration)
            $createOrder = function (array $attrs) use ($faker): Order {
                $status = $attrs['status'] ?? 'delivered';
                $total  = $attrs['total'] ?? $faker->randomFloat(2, 50, 2000);
                $base = [
                    'first_name'     => $faker->firstName(),
                    'last_name'      => $faker->lastName(),
                    'email'          => $faker->unique()->safeEmail(),
                    'phone_number'   => $faker->e164PhoneNumber(),
                    'address'        => $faker->streetAddress(),
                    'city'           => $faker->city(),
                    'province'       => $faker->state(),
                    'postal_code'    => $faker->postcode(),
                    'payment_status' => $status === 'delivered' ? 'completed' : ($attrs['payment_status'] ?? 'pending'),
                    'subtotal'       => $total,
                    'total'          => $total,
                    'discount_amount'=> 0,
                    'tap_charge_id'  => null,
                    'agent_code'     => 'PKLTE7MG',
                ];
                $data = array_merge($base, $attrs);
                return Order::create($data);
            };

            // 5) Delivered orders THIS month (totals: 1000, 500, 1500)
            $o1 = $createOrder([
                'user_id'   => $alice->id,
                'status'    => 'delivered',
                'total'     => 1000.00,
                'created_at'=> $startThisMonth->copy()->addDays(1)->setTime(10, 0), // 2nd of month 10:00
                'updated_at'=> $startThisMonth->copy()->addDays(1)->setTime(10, 0),
            ]);
            $o2 = $createOrder([
                'user_id'   => $bob->id,
                'status'    => 'delivered',
                'total'     => 500.00,
                'created_at'=> $startThisMonth->copy()->addDays(9)->setTime(11, 0), // 10th 11:00
                'updated_at'=> $startThisMonth->copy()->addDays(9)->setTime(11, 0),
            ]);
            $o3 = $createOrder([
                'user_id'   => $charlie->id,
                'status'    => 'delivered',
                'total'     => 1500.00,
                'created_at'=> $startThisMonth->copy()->addDays(13)->setTime(12, 0), // 14th 12:00
                'updated_at'=> $startThisMonth->copy()->addDays(13)->setTime(12, 0),
            ]);

            // Order Items for THIS month shaping category revenue and commission source
            OrderItem::create(['order_id' => $o1->id, 'product_id' => $laptop->id, 'quantity' => 1, 'unit_price' => 700.00, 'total' => 700.00]);
            OrderItem::create(['order_id' => $o1->id, 'product_id' => $chair->id,  'quantity' => 1, 'unit_price' => 300.00, 'total' => 300.00]);

            OrderItem::create(['order_id' => $o2->id, 'product_id' => $laptop->id, 'quantity' => 1, 'unit_price' => 500.00, 'total' => 500.00]);

            OrderItem::create(['order_id' => $o3->id, 'product_id' => $laptop->id, 'quantity' => 1, 'unit_price' => 900.00, 'total' => 900.00]);
            OrderItem::create(['order_id' => $o3->id, 'product_id' => $chair->id,  'quantity' => 1, 'unit_price' => 600.00, 'total' => 600.00]);

            // 6) Delivered order LAST month for Bob (2000 total)
            $oPrev = $createOrder([
                'user_id'   => $bob->id,
                'status'    => 'delivered',
                'total'     => 2000.00,
                'created_at'=> $startLastMonth->copy()->addDays(4)->setTime(9, 0),  // 5th of last month
                'updated_at'=> $startLastMonth->copy()->addDays(4)->setTime(9, 0),
            ]);
            // Items split across categories (not used in current-month metrics but fine)
            OrderItem::create(['order_id' => $oPrev->id, 'product_id' => $laptop->id, 'quantity' => 1, 'unit_price' => 1000.00, 'total' => 1000.00]);
            OrderItem::create(['order_id' => $oPrev->id, 'product_id' => $chair->id,  'quantity' => 1, 'unit_price' => 1000.00, 'total' => 1000.00]);

            // 7) Delivered order THREE months ago for Charlie to mark him as repeat (300 total)
            $oOld = $createOrder([
                'user_id'   => $charlie->id,
                'status'    => 'delivered',
                'total'     => 300.00,
                'created_at'=> $now->copy()->subMonths(3)->startOfMonth()->addDays(9)->setTime(13, 30), // around 10th three months ago
                'updated_at'=> $now->copy()->subMonths(3)->startOfMonth()->addDays(9)->setTime(13, 30),
            ]);
            OrderItem::create(['order_id' => $oOld->id, 'product_id' => $chair->id, 'quantity' => 1, 'unit_price' => 300.00, 'total' => 300.00]);

            // 8) Pending and shipped orders THIS month to populate pending_commissions (800 + 200)
            $createOrder([
                'user_id'   => $david->id,
                'status'    => 'pending',
                'total'     => 800.00,
                'payment_status' => 'pending',
                'created_at'=> $startThisMonth->copy()->addDays(11)->setTime(15, 0), // 12th
                'updated_at'=> $startThisMonth->copy()->addDays(11)->setTime(15, 0),
            ]);
            $createOrder([
                'user_id'   => null, // can be guest/unidentified; won't appear in recentOrders join
                'status'    => 'shipped',
                'total'     => 200.00,
                'payment_status' => 'pending',
                'created_at'=> $startThisMonth->copy()->addDays(12)->setTime(16, 0), // 13th
                'updated_at'=> $startThisMonth->copy()->addDays(12)->setTime(16, 0),
            ]);

            // 9) Add many extra orders (delivered in previous months) to reach 20+ total orders
            $userPool = array_merge([$alice, $bob, $charlie, $david], $extraUsers);

            // 10 random delivered orders last month
            for ($i = 0; $i < 10; $i++) {
                $user = $userPool[array_rand($userPool)];
                $date = $startLastMonth->copy()
                    ->addDays(rand(0, $endLastMonth->day - 1))
                    ->setTime(rand(9, 18), rand(0, 59));
                $total = $faker->randomFloat(2, 80, 1800);
                $order = $createOrder([
                    'user_id'   => $user->id,
                    'status'    => 'delivered',
                    'total'     => $total,
                    'created_at'=> $date,
                    'updated_at'=> $date,
                ]);
                // Single item that matches the order total
                $productId = rand(0, 1) === 1 ? $laptop->id : $chair->id;
                OrderItem::create(['order_id' => $order->id, 'product_id' => $productId, 'quantity' => 1, 'unit_price' => $total, 'total' => $total]);
            }

            // 5 random delivered orders between 2 and 6 months ago
            for ($i = 0; $i < 5; $i++) {
                $user = $userPool[array_rand($userPool)];
                $monthsAgo = rand(2, 6);
                $date = $now->copy()->subMonths($monthsAgo)->startOfMonth()
                    ->addDays(rand(0, 27))
                    ->setTime(rand(9, 18), rand(0, 59));
                $total = $faker->randomFloat(2, 60, 1500);
                $order = $createOrder([
                    'user_id'   => $user->id,
                    'status'    => 'delivered',
                    'total'     => $total,
                    'created_at'=> $date,
                    'updated_at'=> $date,
                ]);
                $productId = rand(0, 1) === 1 ? $laptop->id : $chair->id;
                OrderItem::create(['order_id' => $order->id, 'product_id' => $productId, 'quantity' => 1, 'unit_price' => $total, 'total' => $total]);
            }
        });
    }
}
<?php

namespace Database\Seeders;

use App\Jobs\SendRecombeeInteractionJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FrequentBoughtTogetherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::where('status', '=', 'approved', 'and')->inRandomOrder('')->take(9)->get(['*']);

        if ($products->count() < 9) {
            $this->command->error('Not enough approved products found. Found ' . $products->count() . ', need 9.');
            return;
        }

        $groups = [
            ['products' => $products->slice(0, 3), 'orders' => 50],
            ['products' => $products->slice(3, 3), 'orders' => 100],
            ['products' => $products->slice(6, 3), 'orders' => 70],
        ];

        foreach ($groups as $index => $group) {
            $groupProducts = $group['products'];
            $orderCount = $group['orders'];

            $this->command->info("Seeding Group " . ($index + 1) . " with " . $orderCount . " orders for products: " . $groupProducts->pluck('id')->implode(', '));

            for ($i = 0; $i < $orderCount; $i++) {
                $this->createBundleOrder($groupProducts);
            }
        }

        $this->command->info('Seeding finished successfully.');
    }

    private function createBundleOrder($products)
    {
        // Use a generic user or create a temporary unique ID for Recombee tracking if needed
        $userId = User::inRandomOrder('')->first(['id'])?->id ?? 1;
        
        $order = Order::create([
            'user_id' => $userId,
            'first_name' => 'FBT',
            'last_name' => 'Tester',
            'email' => 'fbt@example.com',
            'phone_number' => '123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'postal_code' => '12345',
            'payment_status' => 'completed',
            'status' => 'delivered',
            'subtotal' => $products->sum(function($p) {
                return $p->variants->first()?->unit_price_usd ?? 0;
            }),
            'total' => $products->sum(function($p) {
                return $p->variants->first()?->unit_price_usd ?? 0;
            }),
            'agent_code' => 'SYSTEM',
        ]);

        foreach ($products as $product) {
            $variant = $product->variants->first();
            $price = $variant?->unit_price_usd ?? 0;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'supplier_id' => $variant?->supplier_id,
                'quantity' => 1,
                'unit_price' => $price,
                'total' => $price,
            ]);

            // Dispatch interaction to Recombee
            // Recombee expects user's email or ID as user identifier usually.
            // Let's check how the system does it.
            // In the controller it usually passes auth()->id() or email.
            // SendRecombeeInteractionJob expects 'user_id' in data.
            
            SendRecombeeInteractionJob::dispatch('purchase', [
                'user_id' => $userId,
                'product_id' => $product->id,
                'price' => $price,
                'quantity' => 1
            ]);
        }
    }
}

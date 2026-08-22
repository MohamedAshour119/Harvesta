<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();
        $agentCodes = ['PKLTE7MG', 'RXTFBYQE', 'HTQSCEI4'];
        $year = Carbon::now()->year;

        foreach (range(1, 12) as $month) {
            $orderCount = rand(1, 10);
            Order::factory($orderCount)
                ->state(function () use ($faker, $agentCodes, $month, $year) {
                    $start = Carbon::create($year, $month, 1)->startOfMonth();
                    $end = Carbon::create($year, $month, 1)->endOfMonth();
                    $date = $faker->dateTimeBetween($start, $end);
                    return [
                        'agent_code' => $faker->randomElement($agentCodes),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                })
                ->create()
                ->each(function ($order) use ($faker) {
                    OrderItem::factory(rand(1, 5))->create([
                        'order_id' => $order->id,
                        'product_id' => $faker->randomElement(Product::pluck('id')->toArray()),
                        'product_variant_id' => $faker->randomElement(ProductVariant::pluck('id')->toArray()),
                    ]);
                });
        }

        // ensure at least one approved order for previous month per agent
        $now = Carbon::now();
        $prev = $now->copy()->subMonthNoOverflow();
        $startPrev = Carbon::create($prev->year, $prev->month, 1)->startOfMonth();
        $endPrev = Carbon::create($prev->year, $prev->month, 1)->endOfMonth();
        foreach ($agentCodes as $code) {
            $order = Order::factory()
                ->state(function () use ($faker, $code, $startPrev, $endPrev) {
                    $date = $faker->dateTimeBetween($startPrev, $endPrev);
                    return [
                        'agent_code' => $code,
                        'status' => 'approved',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                })
                ->create();
            OrderItem::factory(rand(1, 5))->create([
                'order_id' => $order->id,
                'product_id' => $faker->randomElement(Product::pluck('id')->toArray()),
                'product_variant_id' => $faker->randomElement(ProductVariant::pluck('id')->toArray()),
            ]);
        }
    }
}

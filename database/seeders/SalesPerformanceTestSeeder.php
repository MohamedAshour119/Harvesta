<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class SalesPerformanceTestSeeder extends Seeder
{
    /**
     * Seed dummy orders for testing sales performance.
     */
    public function run()
    {
        // Ensure agent exists with code PKLTE7MG
        $agent = User::firstOrCreate(
            ['code' => 'PKLTE7MG'],
            [
                'full_name'           => 'Test Agent',
                'email'          => 'agent_sales_test@example.com',
                'password'       => bcrypt('password'),
                'commission'     => 10,
                'monthly_target' => 500,
                'is_agent'       => true,
            ]
        );

        $agentCode = 'PKLTE7MG';
        $years = [2023, 2024];


        foreach ($years as $year) {
            // Create random approved orders for each month for $year
            for ($month = 1; $month <= 12; $month++) {
                $ordersCount = rand(5, 15);
                for ($i = 0; $i < $ordersCount; $i++) {
                    $date = Carbon::create($year, $month, rand(1, 28), rand(0, 23), rand(0, 59), rand(0, 59));
                    Order::factory()->state([
                        'agent_code'  => $agentCode,
                        'status'      => 'approved',
                        'created_at'  => $date,
                        'updated_at'  => $date,
                    ])->create();
                }
            }
        }
    }
}

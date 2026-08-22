<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerAcquisitionMonthlyTestSeeder extends Seeder
{
    /**
     * Seed a focused scenario to test New Clients, Repeat Buyers, and Conversion Rate
     * as used in AgentDashboardRepository::dashboardInfo() [L69-L96].
     *
     * Target outcome this month:
     * - New Clients: 1 (Alice)
     * - Repeat Buyers: 2 (Bob, Charlie)
     * - Conversion Rate: 33.33%
     */
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();
            $startThisMonth = $now->copy()->startOfMonth();

            // Ensure agent exists
            $agent = User::updateOrCreate(
                ['email' => 'agent1@example.com'],
                [
                    'full_name'      => 'Agent One',
                    'password'       => Hash::make('Pa$$w0rd!'),
                    'is_agent'       => true,
                    'code'           => 'PKLTE7MG',
                    'commission'     => 10,
                    'monthly_target' => 500.00,
                ]
            );

            // Clean any previous orders by agent to isolate this test
            Order::where('agent_code', 'PKLTE7MG')->delete();

            // Create three customers
            $alice   = User::updateOrCreate(['email' => 'alice.ca@example.com'],   ['full_name' => 'Alice CA',   'password' => Hash::make('Pa$$w0rd!')]);
            $bob     = User::updateOrCreate(['email' => 'bob.ca@example.com'],     ['full_name' => 'Bob CA',     'password' => Hash::make('Pa$$w0rd!')]);
            $charlie = User::updateOrCreate(['email' => 'charlie.ca@example.com'], ['full_name' => 'Charlie CA', 'password' => Hash::make('Pa$$w0rd!')]);

            // Helper to create minimal valid orders (no items needed for this metric)
            $createOrder = function (array $attrs): Order {
                $base = [
                    'first_name'     => 'John',
                    'last_name'      => 'Doe',
                    'email'          => 'order@example.com',
                    'phone_number'   => '+201000000000',
                    'address'        => '123 Test St',
                    'city'           => 'Cairo',
                    'province'       => 'Cairo',
                    'postal_code'    => '12345',
                    'payment_status' => 'completed',
                    'subtotal'       => $attrs['total'] ?? 0,
                    'tap_charge_id'  => null,
                    'agent_code'     => 'PKLTE7MG',
                    'status'         => 'delivered',
                    'total'          => $attrs['total'] ?? 100,
                ];
                $data = array_merge($base, $attrs);
                return Order::create($data);
            };

            // Seed history: make Bob and Charlie have previous delivered orders BEFORE this month
            $createOrder([
                'user_id'    => $bob->id,
                'created_at' => $startThisMonth->copy()->subDay(), // last day previous month
                'updated_at' => $startThisMonth->copy()->subDay(),
            ]);
            $createOrder([
                'user_id'    => $charlie->id,
                'created_at' => $startThisMonth->copy()->subDays(2),
                'updated_at' => $startThisMonth->copy()->subDays(2),
            ]);

            // This month: one new client (Alice's first order ever), and two repeat buyers
            $createOrder([
                'user_id'    => $alice->id,
                'created_at' => $startThisMonth->copy()->addDays(1)->setTime(10, 0),
                'updated_at' => $startThisMonth->copy()->addDays(1)->setTime(10, 0),
            ]);
            $createOrder([
                'user_id'    => $bob->id,
                'created_at' => $startThisMonth->copy()->addDays(2)->setTime(11, 0),
                'updated_at' => $startThisMonth->copy()->addDays(2)->setTime(11, 0),
            ]);
            $createOrder([
                'user_id'    => $charlie->id,
                'created_at' => $startThisMonth->copy()->addDays(3)->setTime(12, 0),
                'updated_at' => $startThisMonth->copy()->addDays(3)->setTime(12, 0),
            ]);
        });
    }
}

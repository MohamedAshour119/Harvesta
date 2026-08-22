<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userIds      = User::pluck('id')->toArray();
        $agentCodes   = User::where('is_agent', true)->pluck('code')->toArray();
        $total        = $this->faker->randomFloat(2, 100, 1000);
        $discount     = $this->faker->optional(0.3)->randomFloat(2, 5, $total * 0.5);
        $afterDiscount = $discount ? $total - $discount : $total;

        return [
            'user_id'               => $userIds ? $this->faker->randomElement($userIds) : null,
            'first_name'=> $this->faker->firstName,
            'last_name'=> $this->faker->lastName,
            'email'=> $this->faker->email,
            'phone_number'=> $this->faker->phoneNumber,
            'company_name'=> $this->faker->company,
            'address'=> $this->faker->address,
            'city'=> $this->faker->city,
            'province'=> $this->faker->state,
            'postal_code'=> $this->faker->postcode,
            'payment_status'=> $this->faker->randomElement(['pending','completed','failed']),
            'status'                => $this->faker->randomElement(['pending','approved','shipped']),
            'subtotal'          => $total,
            'discount_amount'       => $discount,
            'total'  => $afterDiscount,
            'agent_code'            => $agentCodes ? $this->faker->randomElement($agentCodes) : 'SYSTEM',
            'created_at'            => now(),
            'updated_at'            => now(),
        ];
    }
}

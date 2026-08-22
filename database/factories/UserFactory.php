<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isAgent = fake()->boolean();

        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_agent' => $isAgent,
            'code' => $isAgent ? fake()->uuid() : null,
            'commission' => $isAgent ? fake()->randomFloat(2, 5, 20) : null,
            'monthly_target' => $isAgent ? fake()->randomFloat(2, 500, 5000) : null,
            'phone_number' => fake()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NormalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'Customer']);

        \App\Models\User::factory()
            ->count(10)
            ->create([
                'password' => \Illuminate\Support\Facades\Hash::make('Pa$$w0rd!'),
                'is_agent' => false,
            ])
            ->each(function ($user) use ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            });
    }
}

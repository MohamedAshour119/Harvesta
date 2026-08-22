<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use Faker\Factory as Faker;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fakerEn = Faker::create();
        $fakerAr = Faker::create('ar_SA');

        foreach (range(1, 10) as $i) {
            Agent::create([
                'name' => [
                    'en' => $fakerEn->name,
                    'ar' => $fakerAr->name,
                ],
                'commission_rate' => $fakerEn->randomFloat(2, 0, 30),
            ]);
        }
    }
}

<?php

use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use App\Models\Role;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$faker = Faker::create();
$slug = 'handheld-thermal-imaging-camera';
$product = Product::where('slug', $slug)->first();

if (!$product) {
    echo "Product with slug '{$slug}' not found.\n";
    exit(1);
}

echo "Found product: {$product->name} (ID: {$product->id})\n";

$role = Role::where('name', 'Customer')->first();

for ($i = 0; $i < 10; $i++) {
    $user = User::create([
        'full_name' => $faker->name,
        'email' => "testuser_" . Str::random(8) . "@example.com",
        'password' => bcrypt('password'),
    ]);

    if ($role) {
        $user->roles()->attach($role->id);
    }

    ProductRating::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'rating' => $faker->numberBetween(3, 5),
        'comment' => $faker->paragraph,
    ]);

    echo "Created rating {$i} by {$user->full_name}\n";
}

echo "Done seeding 10 ratings and reviews.\n";

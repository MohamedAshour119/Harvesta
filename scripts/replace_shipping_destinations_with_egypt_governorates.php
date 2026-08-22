<?php

declare(strict_types=1);

use App\Models\ShippingDestination;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = array_slice($argv, 1);

$dryRun = in_array('--dry-run', $arguments, true);
$ratePerKg = null;
$ratePerVolumeUnit = null;

foreach ($arguments as $argument) {
    if (str_starts_with($argument, '--rate-per-kg=')) {
        $ratePerKg = (float) substr($argument, 14);
    }

    if (str_starts_with($argument, '--rate-per-volume-unit=')) {
        $ratePerVolumeUnit = (float) substr($argument, 24);
    }
}

if (($ratePerKg !== null && $ratePerKg < 0) || ($ratePerVolumeUnit !== null && $ratePerVolumeUnit < 0)) {
    fwrite(STDERR, "Rate values must be zero or greater.\n");
    exit(1);
}

$governorates = [
    ['en' => 'Cairo', 'ar' => 'القاهرة'],
    ['en' => 'Giza', 'ar' => 'الجيزة'],
    ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
    ['en' => 'Dakahlia', 'ar' => 'الدقهلية'],
    ['en' => 'Red Sea', 'ar' => 'البحر الأحمر'],
    ['en' => 'Beheira', 'ar' => 'البحيرة'],
    ['en' => 'Fayoum', 'ar' => 'الفيوم'],
    ['en' => 'Gharbia', 'ar' => 'الغربية'],
    ['en' => 'Ismailia', 'ar' => 'الإسماعيلية'],
    ['en' => 'Menofia', 'ar' => 'المنوفية'],
    ['en' => 'Minya', 'ar' => 'المنيا'],
    ['en' => 'Qaliubiya', 'ar' => 'القليوبية'],
    ['en' => 'New Valley', 'ar' => 'الوادي الجديد'],
    ['en' => 'Suez', 'ar' => 'السويس'],
    ['en' => 'Aswan', 'ar' => 'أسوان'],
    ['en' => 'Assiut', 'ar' => 'أسيوط'],
    ['en' => 'Beni Suef', 'ar' => 'بني سويف'],
    ['en' => 'Port Said', 'ar' => 'بورسعيد'],
    ['en' => 'Damietta', 'ar' => 'دمياط'],
    ['en' => 'Sharkia', 'ar' => 'الشرقية'],
    ['en' => 'South Sinai', 'ar' => 'جنوب سيناء'],
    ['en' => 'Kafr El Sheikh', 'ar' => 'كفر الشيخ'],
    ['en' => 'Matrouh', 'ar' => 'مطروح'],
    ['en' => 'Luxor', 'ar' => 'الأقصر'],
    ['en' => 'Qena', 'ar' => 'قنا'],
    ['en' => 'North Sinai', 'ar' => 'شمال سيناء'],
    ['en' => 'Sohag', 'ar' => 'سوهاج'],
];

$rows = array_map(
    static function (array $governorate) use ($ratePerKg, $ratePerVolumeUnit): array {
        $resolvedRatePerKg = $ratePerKg ?? fake()->randomFloat(2, 1, 10);
        $resolvedRatePerVolumeUnit = $ratePerVolumeUnit ?? fake()->randomFloat(2, 1, 10);

        return [
            'city' => json_encode($governorate, JSON_UNESCAPED_UNICODE),
            'rate_per_kg' => $resolvedRatePerKg,
            'rate_per_volume_unit' => $resolvedRatePerVolumeUnit,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    },
    $governorates
);

if ($dryRun) {
    fwrite(STDOUT, "Would delete all current shipping destinations and insert the following governorates:\n");

    foreach ($rows as $index => $row) {
        $city = json_decode($row['city'], true, 512, JSON_THROW_ON_ERROR);
        fwrite(
            STDOUT,
            sprintf(
                "%02d. %s / %s | rate_per_kg=%.2f | rate_per_volume_unit=%.2f\n",
                $index + 1,
                $city['en'],
                $city['ar'],
                $row['rate_per_kg'],
                $row['rate_per_volume_unit'],
            )
        );
    }

    exit(0);
}

DB::transaction(static function () use ($rows): void {
    ShippingDestination::query()->delete();
    ShippingDestination::query()->insert($rows);
});

fwrite(
    STDOUT,
    sprintf(
        "Replaced shipping destinations with %d Egyptian governorates.\n",
        count($rows),
    )
);

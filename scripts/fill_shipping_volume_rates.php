<?php

declare(strict_types=1);

use App\Models\ShippingDestination;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = array_slice($argv, 1);

$overwriteAll = in_array('--all', $arguments, true);
$dryRun = in_array('--dry-run', $arguments, true);
$copyFromKg = ! in_array('--no-copy-from-kg', $arguments, true);
$fixedValue = null;

foreach ($arguments as $argument) {
    if (str_starts_with($argument, '--value=')) {
        $fixedValue = (float) substr($argument, 8);
        break;
    }
}

if ($fixedValue !== null && $fixedValue < 0) {
    fwrite(STDERR, "The --value option must be zero or greater.\n");
    exit(1);
}

$query = ShippingDestination::query()->orderBy('id');

if (! $overwriteAll) {
    $query->where(function ($builder) {
        $builder
            ->whereNull('rate_per_volume_unit')
            ->orWhere('rate_per_volume_unit', 0);
    });
}

$destinations = $query->get();

if ($destinations->isEmpty()) {
    fwrite(STDOUT, "No shipping destinations matched the update criteria.\n");
    exit(0);
}

$updatedCount = 0;

foreach ($destinations as $destination) {
    $targetValue = $fixedValue;

    if ($targetValue === null && $copyFromKg) {
        $targetValue = (float) $destination->rate_per_kg;
    }

    if ($targetValue === null) {
        fwrite(STDERR, "No target value could be resolved for destination #{$destination->id}.\n");
        exit(1);
    }

    if ($dryRun) {
        $city = is_array($destination->city) ? ($destination->city['en'] ?? json_encode($destination->city)) : (string) $destination->city;
        fwrite(
            STDOUT,
            sprintf(
                "[DRY RUN] #%d %s: %s -> %.2f\n",
                $destination->id,
                $city,
                $destination->rate_per_volume_unit ?? 'null',
                $targetValue,
            )
        );

        $updatedCount++;
        continue;
    }

    $destination->forceFill([
        'rate_per_volume_unit' => $targetValue,
    ])->save();

    $updatedCount++;
}

fwrite(
    STDOUT,
    sprintf(
        "%s %d shipping destination(s).\n",
        $dryRun ? 'Would update' : 'Updated',
        $updatedCount,
    )
);


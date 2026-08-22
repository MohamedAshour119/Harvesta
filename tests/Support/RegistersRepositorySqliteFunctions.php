<?php

namespace Tests\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait RegistersRepositorySqliteFunctions
{
    protected function registerRepositorySqliteFunctions(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();

        if (! method_exists($pdo, 'sqliteCreateFunction')) {
            return;
        }

        $pdo->sqliteCreateFunction('CONCAT', function (...$values): string {
            return implode('', array_map(static fn ($value) => $value ?? '', $values));
        }, -1);

        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($value, string $format): ?string {
            if ($value === null) {
                return null;
            }

            return Carbon::parse($value)->format($this->mysqlDateFormatToPhp($format));
        }, 2);

        $pdo->sqliteCreateFunction('JSON_EXTRACT', function ($json, string $path) {
            if ($json === null) {
                return null;
            }

            $decoded = is_array($json) ? $json : json_decode((string) $json, true);

            if (! is_array($decoded)) {
                return null;
            }

            if (preg_match('/^\$\."(.+)"$/', $path, $matches) === 1) {
                return $decoded[$matches[1]] ?? null;
            }

            if (preg_match('/^\$\.(.+)$/', $path, $matches) === 1) {
                return $decoded[$matches[1]] ?? null;
            }

            return null;
        }, 2);

        $pdo->sqliteCreateFunction('JSON_UNQUOTE', static function ($value) {
            if (! is_string($value)) {
                return $value;
            }

            if (preg_match('/^".*"$/s', $value) === 1) {
                return json_decode($value, true) ?? trim($value, '"');
            }

            return $value;
        }, 1);
    }

    private function mysqlDateFormatToPhp(string $format): string
    {
        return strtr($format, [
            '%Y' => 'Y',
            '%M' => 'F',
            '%m' => 'm',
            '%d' => 'd',
            '%h' => 'h',
            '%i' => 'i',
            '%p' => 'A',
        ]);
    }
}

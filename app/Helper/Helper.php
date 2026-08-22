<?php

namespace App\Helper;
use App\Models\CurrencyRate;
use League\ISO3166\ISO3166;

class Helper {
    public static function preparePagination ($data): array
    {
        return [
            'total_pages' => $data->lastPage(),
            'current_page' => $data->currentPage(),
            'next_page_url' => self::forceHttps($data->nextPageUrl()),
            'previous_page_url' => self::forceHttps($data->previousPageUrl()),
            'last_page' => $data->lastPage(),
            'path' => self::forceHttps($data->path()),
        ];
    }
    private static function forceHttps($url): array|string
    {
        return str_replace('http://', 'https://', $url);
    }

    public static function getCountryNames(): array
    {
        return collect((new ISO3166())->all())->pluck('name')->toArray();
    }
    public static function getCountryCodes(): array
    {
        return collect((new ISO3166())->all())->pluck('alpha2')->toArray();
    }
}

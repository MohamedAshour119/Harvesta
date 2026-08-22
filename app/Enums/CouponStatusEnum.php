<?php

namespace App\Enums;

enum CouponStatusEnum: string
{
    case ACTIVE = 'active';
    case IN_ACTIVE = 'in-active';

    public static function labels(): array
    {
        // $locale = app()->getLocale();
        return [
            self::ACTIVE->value => __('filament-resources.resources.CouponResource.active'),
            self::IN_ACTIVE->value => __('filament-resources.resources.CouponResource.in-active'),
        ];
    }
}

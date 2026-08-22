<?php

namespace App\Filament\Resources\SmartBundleResource\Pages;

use App\Filament\Resources\SmartBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSmartBundle extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = SmartBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}

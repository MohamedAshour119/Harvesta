<?php

namespace App\Filament\Resources\SmartBundleResource\Pages;

use App\Filament\Resources\SmartBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmartBundles extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = SmartBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}

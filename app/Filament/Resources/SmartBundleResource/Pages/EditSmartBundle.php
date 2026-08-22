<?php

namespace App\Filament\Resources\SmartBundleResource\Pages;

use App\Filament\Resources\SmartBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmartBundle extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = SmartBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

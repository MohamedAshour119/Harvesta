<?php

namespace App\Filament\Resources\VariantTypeResource\Pages;

use App\Filament\Resources\VariantTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVariantType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = VariantTypeResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}

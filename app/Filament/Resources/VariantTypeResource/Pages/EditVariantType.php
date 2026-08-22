<?php

namespace App\Filament\Resources\VariantTypeResource\Pages;

use App\Filament\Resources\VariantTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVariantType extends EditRecord
{
    protected static string $resource = VariantTypeResource::class;
    use EditRecord\Concerns\Translatable;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

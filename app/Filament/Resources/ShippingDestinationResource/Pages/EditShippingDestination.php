<?php

namespace App\Filament\Resources\ShippingDestinationResource\Pages;

use App\Filament\Resources\ShippingDestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShippingDestination extends EditRecord
{
    protected static string $resource = ShippingDestinationResource::class;
    use EditRecord\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ShippingDestinationResource\Pages;

use App\Filament\Resources\ShippingDestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingDestination extends CreateRecord
{
    protected static string $resource = ShippingDestinationResource::class;
    use CreateRecord\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}

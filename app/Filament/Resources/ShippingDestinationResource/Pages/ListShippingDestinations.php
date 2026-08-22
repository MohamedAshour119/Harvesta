<?php

namespace App\Filament\Resources\ShippingDestinationResource\Pages;

use App\Filament\Resources\ShippingDestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingDestinations extends ListRecords
{
    protected static string $resource = ShippingDestinationResource::class;
    use ListRecords\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\VariantTypeResource\Pages;

use App\Filament\Resources\VariantTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVariantTypes extends ListRecords
{
    protected static string $resource = VariantTypeResource::class;
    use ListRecords\Concerns\Translatable;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

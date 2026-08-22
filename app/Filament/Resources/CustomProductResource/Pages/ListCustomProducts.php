<?php

namespace App\Filament\Resources\CustomProductResource\Pages;

use App\Filament\Resources\CustomProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomProducts extends ListRecords
{
    protected static string $resource = CustomProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

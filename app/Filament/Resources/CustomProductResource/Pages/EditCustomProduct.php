<?php

namespace App\Filament\Resources\CustomProductResource\Pages;

use App\Filament\Resources\CustomProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomProduct extends EditRecord
{
    protected static string $resource = CustomProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

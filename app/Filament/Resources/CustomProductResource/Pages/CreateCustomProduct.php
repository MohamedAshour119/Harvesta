<?php

namespace App\Filament\Resources\CustomProductResource\Pages;

use App\Filament\Resources\CustomProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomProduct extends CreateRecord
{
    protected static string $resource = CustomProductResource::class;
}

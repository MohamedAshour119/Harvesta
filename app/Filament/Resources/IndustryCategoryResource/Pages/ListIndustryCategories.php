<?php

namespace App\Filament\Resources\IndustryCategoryResource\Pages;

use App\Filament\Resources\IndustryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIndustryCategories extends ListRecords
{
    protected static string $resource = IndustryCategoryResource::class;

    use ListRecords\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}

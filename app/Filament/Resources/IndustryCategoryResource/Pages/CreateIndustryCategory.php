<?php

namespace App\Filament\Resources\IndustryCategoryResource\Pages;

use App\Filament\Resources\IndustryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateIndustryCategory extends CreateRecord
{
    protected static string $resource = IndustryCategoryResource::class;
    use CreateRecord\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }

    protected function afterCreate(): void
    {
        // Get the English name from the record
        $englishName = trim($this->record->getTranslation('name', 'en'));
        $slug = '';

        if ($englishName) {
            // Check if the English name contains spaces
            if (strpos($englishName, ' ') === false) {
                // Single word: use lowercase name + id as slug
                $slug = strtolower($englishName) . '-' . $this->record->id;
            } else {
                // Multiple words: use Str::slug()
                $slug = Str::slug($englishName);
            }
        }

        // Update the record with the slug and save it
        if ($this->record->slug !== $slug) {
            $this->record->slug = $slug;
            $this->record->save();
        }
    }
}

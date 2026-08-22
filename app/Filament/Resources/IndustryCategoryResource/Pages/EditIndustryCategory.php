<?php

namespace App\Filament\Resources\IndustryCategoryResource\Pages;

use App\Filament\Resources\IndustryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditIndustryCategory extends EditRecord
{
    protected static string $resource = IndustryCategoryResource::class;
    use EditRecord\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
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
        // Only save if the slug has changed or is newly generated to avoid unnecessary writes
        if ($this->record->slug !== $slug) {
            $this->record->slug = $slug;
            $this->record->save();
        }
    }
}

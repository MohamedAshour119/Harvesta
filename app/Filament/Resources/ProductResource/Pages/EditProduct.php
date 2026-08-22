<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    use EditRecord\Concerns\Translatable;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // IMPORTANT: Configure this to your actual image form field name.
        // This is also assumed to be your Spatie Media Library collection name.
        $imagesFormFieldName = 'main_images'; // Example: 'images', 'gallery', 'product_photos'
        $otherImagesFormFieldName = 'products_other_images';

        // Check if the image field is part of the submitted data.
        // This indicates the user might have interacted with the image field (e.g., uploaded new images, or cleared existing ones).
        if (array_key_exists($imagesFormFieldName, $data)) {
            /**
             * @var \Illuminate\Database\Eloquent\Model|\Spatie\MediaLibrary\HasMedia $product
             * Type hint for the product record, ensuring it's a model and uses HasMedia.
             */
            $product = $this->getRecord();

            // Ensure the record exists and has the clearMediaCollection method (from Spatie Media Library).
            if ($product && method_exists($product, 'clearMediaCollection')) {
                // Clear all existing media from the specified collection.
                // This happens *before* Filament processes the $data[$imagesFormFieldName]
                // to add any new images or reflect cleared images.
                $product->clearMediaCollection($imagesFormFieldName);
            }
        }

        if (array_key_exists($otherImagesFormFieldName, $data)) {
            /**
             * @var \Illuminate\Database\Eloquent\Model|\Spatie\MediaLibrary\HasMedia $product
             * Type hint for the product record, ensuring it's a model and uses HasMedia.
             */
            $product = $this->getRecord();

            // Ensure the record exists and has the clearMediaCollection method (from Spatie Media Library).
            if ($product && method_exists($product, 'clearMediaCollection')) {
                // Clear all existing media from the specified collection.
                // This happens *before* Filament processes the $data[$imagesFormFieldName]
                // to add any new images or reflect cleared images.
                $product->clearMediaCollection($otherImagesFormFieldName);
            }
        }

        return $data;
    }
}

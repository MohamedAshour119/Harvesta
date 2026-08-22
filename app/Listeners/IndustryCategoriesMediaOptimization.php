<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class IndustryCategoriesMediaOptimization
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if ($media->collection_name !== 'industry_category_images') {
            return;
        }

        $pathToImage = $media->getPath();

        // Load the image and optimize it
        $optimizedImage = Image::load($pathToImage)->resize(260, 260)->optimize()->quality(75)->format('webp');
        // Save the optimized image
        $optimizedImage->save();

        // Get the new file name with .webp extension
        $newFileName = pathinfo($pathToImage, PATHINFO_FILENAME) . '.webp';

        // Get the directory of the original file
        $directory = pathinfo($pathToImage, PATHINFO_DIRNAME);

        // Full path to the new file
        $newFilePath = $directory . '/' . $newFileName;

        // Rename the file to .webp
        rename($pathToImage, $newFilePath);

        // Get the new file size
        $newFileSize = filesize($newFilePath);

        // Update the media model with the new file name, mime type, and name
        $media->name = pathinfo($newFileName, PATHINFO_FILENAME); // Updating the name field
        $media->file_name = $newFileName; // Updating the file name field
        $media->mime_type = 'image/webp'; // Updating the mime type field
        $media->size = $newFileSize; // Updating the size field
        $media->save();
    }
}

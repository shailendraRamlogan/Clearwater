<?php

namespace App\Filament\Resources\GalleryPhotoResource\Pages;

use App\Filament\Resources\GalleryPhotoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryPhoto extends CreateRecord
{
    protected static string $resource = GalleryPhotoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure at least one source (image upload or URL) is present
        if (empty($data['image']) && empty($data['url'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image' => 'You must provide either an uploaded image or an external URL.',
            ]);
        }

        return $data;
    }
}

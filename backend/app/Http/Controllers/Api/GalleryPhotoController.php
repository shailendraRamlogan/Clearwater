<?php

namespace App\Http\Controllers\Api;

use App\Models\GalleryPhoto;
use Illuminate\Http\JsonResponse;

class GalleryPhotoController
{
    public function index(): JsonResponse
    {
        $photos = GalleryPhoto::query()
            ->active()
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn (GalleryPhoto $photo) => [
                'id' => $photo->id,
                'src' => $photo->src,
                'alt' => $photo->alt_text,
                'sort_order' => $photo->sort_order,
            ]);

        return response()->json($photos);
    }
}

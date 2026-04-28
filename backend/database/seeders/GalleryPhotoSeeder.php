<?php

namespace Database\Seeders;

use App\Models\GalleryPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GalleryPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $photos = [
            ['url' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=400&fit=crop', 'alt' => 'Crystal clear turquoise waters from above', 'sort_order' => 1],
            ['url' => 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600&h=400&fit=crop', 'alt' => 'Luxury yacht cruising at sunset', 'sort_order' => 2],
            ['url' => 'https://images.unsplash.com/photo-1548574505-5e239809ee19?w=600&h=400&fit=crop', 'alt' => 'White boat anchored in shallow water', 'sort_order' => 3],
            ['url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop', 'alt' => 'Aerial view of tropical coastline', 'sort_order' => 4],
            ['url' => 'https://images.unsplash.com/photo-1540202404-a2f29016b523?w=600&h=400&fit=crop', 'alt' => 'Catamaran sailing in blue waters', 'sort_order' => 5],
            ['url' => 'https://images.unsplash.com/photo-1559599238-308793637427?w=600&h=400&fit=crop', 'alt' => 'Snorkeler exploring coral reef', 'sort_order' => 6],
            ['url' => 'https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&h=400&fit=crop', 'alt' => 'Tropical island with white sand beach', 'sort_order' => 7],
            ['url' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&h=400&fit=crop', 'alt' => 'Underwater view of boat hull', 'sort_order' => 8],
            ['url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop', 'alt' => 'Pristine beach with turquoise water', 'sort_order' => 9],
            ['url' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=600&h=400&fit=crop', 'alt' => 'Kayaking in crystal clear water', 'sort_order' => 10],
        ];

        foreach ($photos as $photo) {
            GalleryPhoto::create([
                'id' => (string) Str::uuid(),
                'url' => $photo['url'],
                'alt_text' => $photo['alt'],
                'sort_order' => $photo['sort_order'],
                'is_active' => true,
            ]);
        }
    }
}

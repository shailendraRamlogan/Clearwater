<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        $addons = [
            [
                'id' => (string) Str::uuid(),
                'title' => 'Photo Package Upgrade',
                'description' => 'All edited digital photos plus printed copies delivered to you.',
                'price_cents' => 7500,
                'is_active' => true,
                'sort_order' => 1,
                'max_quantity' => 1,
                'icon_name' => 'Camera',
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Special Occasion',
                'description' => 'Birthday, anniversary, proposal? Let us know and we will make it extra special!',
                'price_cents' => 0,
                'is_active' => true,
                'sort_order' => 2,
                'max_quantity' => 1,
                'icon_name' => 'PartyPopper',
            ],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}

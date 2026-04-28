<?php

namespace Database\Seeders;

use App\Models\TicketType;
use App\Models\TicketTypeFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Adult',
                'description' => 'Ages 13+',
                'price_cents' => 20000,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Child',
                'description' => 'Ages 3-12',
                'price_cents' => 15000,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        $created = [];
        foreach ($types as $type) {
            $created[$type['name']] = TicketType::create($type);
        }

        // Adult features
        $adultFeatures = [
            ['icon' => 'Camera', 'label' => 'Multiple action photos included', 'sort_order' => 1],
            ['icon' => 'Citrus', 'label' => 'Homemade island lemonade (2 choices)', 'sort_order' => 2],
            ['icon' => 'Beer', 'label' => 'Bahamian beers (up to 3)', 'sort_order' => 3],
            ['icon' => 'Grape', 'label' => 'Fruit-flavored Raddlers (up to 3)', 'sort_order' => 4],
            ['icon' => 'Wine', 'label' => 'Caribbean rum tasting', 'sort_order' => 5],
            ['icon' => 'Cookie', 'label' => 'Light snacks provided', 'sort_order' => 6],
        ];
        foreach ($adultFeatures as $feature) {
            $created['Adult']->features()->create($feature);
        }

        // Child features
        $childFeatures = [
            ['icon' => 'Camera', 'label' => 'Photos with the family', 'sort_order' => 1],
            ['icon' => 'Juice', 'label' => 'Kid-friendly drinks included', 'sort_order' => 2],
            ['icon' => 'Cookie', 'label' => 'Snacks provided', 'sort_order' => 3],
        ];
        foreach ($childFeatures as $feature) {
            $created['Child']->features()->create($feature);
        }
    }
}

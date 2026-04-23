<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BoatSeeder extends Seeder
{
    public function run(): void
    {
        $boats = [
            ['name' => 'SS Clear Seas', 'slug' => 'crystal-clear', 'capacity' => 10, 'description' => 'Our flagship vessel with crystal clear views.'],
            ['name' => 'Skys', 'slug' => 'skys', 'capacity' => 10, 'description' => 'Perfect for intimate sunset tours.'],
        ];

        $slots = [
            ['monday', '08:30', '11:00'],
            ['monday', '10:45', '13:15'],
            ['tuesday', '12:15', '14:45'],
            ['tuesday', '13:15', '15:45'],
        ];

        foreach ($boats as $boatData) {
            $boat = Boat::create([
                'id' => (string) Str::uuid(),
                ...$boatData,
                'is_active' => true,
            ]);

            foreach ($slots as [$day, $start, $end]) {
                TimeSlot::create([
                    'id' => (string) Str::uuid(),
                    'boat_id' => $boat->id,
                    'day' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'max_capacity' => $boat->capacity,
                    'is_blocked' => false,
                ]);
            }
        }
    }
}

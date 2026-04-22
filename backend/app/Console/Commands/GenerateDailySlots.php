<?php

namespace App\Console\Commands;

use App\Models\Boat;
use App\Models\TimeSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateDailySlots extends Command
{
    protected $signature = 'slots:generate {--date= : Specific date (Y-m-d)}';
    protected $description = 'Generate daily time slots for all active boats';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->toDateString();
        $boats = Boat::where('is_active', true)->get();

        $defaultSlots = [
            ['08:30', '11:00'],
            ['10:45', '13:15'],
            ['12:15', '14:45'],
            ['13:15', '15:45'],
        ];

        foreach ($boats as $boat) {
            foreach ($defaultSlots as [$start, $end]) {
                // Only create if no slot exists for this boat+time combo
                $exists = TimeSlot::where('boat_id', $boat->id)
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->exists();

                if (!$exists) {
                    TimeSlot::create([
                        'id' => (string) Str::uuid(),
                        'boat_id' => $boat->id,
                        'start_time' => $start,
                        'end_time' => $end,
                        'max_capacity' => $boat->capacity,
                        'is_blocked' => false,
                    ]);
                }
            }
        }

        $this->info("Time slots generated for {$date}");
        return self::SUCCESS;
    }
}

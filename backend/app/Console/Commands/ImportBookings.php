<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingGuest;
use App\Models\TimeSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportBookings extends Command
{
    protected $signature = 'import:bookings {--url=http://72.61.70.20:8000/api/bookings} {--token=clearboat-admin-token-2026}';
    protected $description = 'Import bookings from old Clearwater instance';

    public function handle(): int
    {
        $response = Http::get($this->option('url'), [
            'token' => $this->option('token'),
        ]);

        if (!$response->successful()) {
            $this->error("Failed to fetch bookings: " . $response->status());
            return 1;
        }

        $oldBookings = $response->json('bookings', []);
        $this->info("Found " . count($oldBookings) . " bookings to import");

        // Map old boat IDs to new boat IDs
        $boatMap = [];
        $oldBoatIds = ['23abfb69-8c27-47c2-8522-83e8d610e1a7', '66c3ca42-5107-4abd-baf2-9247920d0496'];
        $newBoats = \App\Models\Boat::all();
        foreach ($oldBoatIds as $i => $oldId) {
            if ($newBoats->has($i)) {
                $boatMap[$oldId] = $newBoats[$i]->id;
            }
        }

        // Map old slot IDs to new slot IDs (by day+start_time+boat)
        $oldSlots = Http::get('http://72.61.70.20:8000/api/availability?date=2026-04-28', [
            'token' => $this->option('token'),
        ])->json('slots', []);

        // We need to find matching slots on new server
        // For now, let's just use the first available slot per day
        $imported = 0;
        $skipped = 0;

        foreach ($oldBookings as $old) {
            $this->info("Processing booking {$old['id']}...");

            // Find matching time slot on new server
            $slot = TimeSlot::where('day', strtolower(date('l', strtotime($old['tour_date']))))
                ->where('start_time', $old['time_slot']['start_time'])
                ->first();

            if (!$slot) {
                $this->warn("  No matching slot for day=" . strtolower(date('l', strtotime($old['tour_date']))) . " time={$old['time_slot']['start_time']}, skipping");
                $skipped++;
                continue;
            }

            // Check if booking already exists
            if (Booking::where('tour_date', $old['tour_date'])->where('time_slot_id', $slot->id)->whereHas('guests', function($q) use ($old) {
                $q->where('email', $old['guest']['email']);
            })->exists()) {
                $this->warn("  Duplicate, skipping");
                $skipped++;
                continue;
            }

            DB::beginTransaction();
            try {
                $adults = 0;
                $children = 0;
                foreach ($old['items'] as $item) {
                    if ($item['ticket_type'] === 'adult') $adults += $item['quantity'];
                    if ($item['ticket_type'] === 'child') $children += $item['quantity'];
                }

                $booking = Booking::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'tour_date' => $old['tour_date'],
                    'time_slot_id' => $slot->id,
                    'adult_count' => $adults,
                    'child_count' => $children,
                    'status' => $old['status'],
                    'total_price_cents' => (int) round(($old['subtotal'] ?? $old['grand_total'] ?? 0) * 100),
                    'fees_cents' => $old['fees_cents'] ?? 0,
                    'is_confirmed' => $old['is_confirmed'] ?? false,
                    'needs_confirmation' => $old['needs_confirmation'] ?? false,
                    'package_upgrade' => $old['package_upgrade'] ?? false,
                    'special_occasion' => $old['special_occasion'] ?? false,
                    'special_comment' => $old['special_comment'] ?? '',
                    'stripe_payment_intent_id' => null,
                ]);

                // Insert items
                foreach ($old['items'] as $item) {
                    BookingItem::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'booking_id' => $booking->id,
                        'ticket_type' => $item['ticket_type'],
                        'quantity' => $item['quantity'],
                        'unit_price_cents' => (int) round($item['unit_price'] * 100),
                    ]);
                }

                // Insert primary guest
                BookingGuest::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'booking_id' => $booking->id,
                    'first_name' => $old['guest']['first_name'],
                    'last_name' => $old['guest']['last_name'],
                    'email' => $old['guest']['email'],
                    'phone' => $old['guest']['phone'] ?? '',
                    'is_primary' => true,
                ]);

                $this->info("  Imported as {$booking->id}");
                $imported++;

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  Failed: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("\nDone! Imported: $imported, Skipped: $skipped");
        return 0;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\Boat;
use App\Models\Payment;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $slots = TimeSlot::with('boat')->get();
        if ($slots->isEmpty()) {
            $this->command->warn('No time slots found. Run BoatSeeder first.');
            return;
        }

        $firstNames = ['James', 'Maria', 'Robert', 'Sarah', 'Michael', 'Emily', 'David', 'Jessica', 'John', 'Ashley', 'William', 'Amanda', 'Richard', 'Stephanie', 'Thomas', 'Nicole', 'Daniel', 'Rachel', 'Christopher', 'Lauren'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com', 'aol.com'];
        $occasions = [null, null, null, null, 'birthday', 'anniversary', 'honeymoon', null, 'proposal', null];
        $comments = [null, null, 'First time visiting Nassau!', 'Celebrating our anniversary', 'Kids are very excited', null, 'Would love to see dolphins', null, null, 'Honeymoon trip!'];
        $statuses = ['pending', 'confirmed', 'confirmed', 'confirmed', 'confirmed', 'confirmed', 'cancelled', 'confirmed', 'confirmed', 'pending'];

        // Create 30 bookings spread across different dates and slots
        $bookingCount = 30;
        $usedEmails = [];
        $bookings = [];

        // Get available days from slots
        $availableDays = $slots->pluck('day')->unique()->values()->toArray();

        for ($i = 0; $i < $bookingCount; $i++) {
            // Pick a random day that has slots, then find the next occurrence
            $targetDay = $availableDays[array_rand($availableDays)];
            $daysOffset = 0;
            do {
                $daysOffset++;
                $tourDate = now()->addDays($daysOffset)->toDateString();
                $tourDay = strtolower(now()->addDays($daysOffset)->format('l'));
            } while ($tourDay !== $targetDay && $daysOffset < 60);

            // Pick a slot that matches the tour date's day-of-week
            $matchingSlots = $slots->where('day', $tourDay);
            $slot = $matchingSlots->isNotEmpty() ? $matchingSlots->random() : $slots->random();

            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . '@' . $domains[array_rand($domains)]);

            // Avoid duplicate emails
            $attempt = 0;
            while (in_array($email, $usedEmails) && $attempt < 10) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $email = strtolower($firstName . '.' . $lastName . '@' . $domains[array_rand($domains)]);
                $attempt++;
            }
            $usedEmails[] = $email;

            $status = $statuses[array_rand($statuses)];

            // Generate booking ref
            $ref = 'CBB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

            // Random tickets
            $adultQty = rand(1, 4);
            $childQty = rand(0, 2);
            $photoUpgrade = rand(0, min($adultQty + $childQty, 3));

            $adultPrice = 20000; // $200
            $childPrice = 15000; // $150
            $photoPrice = 2500;  // $25

            $totalCents = ($adultQty * $adultPrice) + ($childQty * $childPrice) + ($photoUpgrade * $photoPrice);

            $totalGuests = $adultQty + $childQty;
            $needsConfirmation = $totalGuests > 1;
            $isConfirmed = $status === 'confirmed' ? ($needsConfirmation ? (rand(0, 1) === 1) : true) : false;

            $booking = Booking::create([
                'id' => (string) Str::uuid(),
                'booking_ref' => $ref,
                'tour_date' => $tourDate,
                'time_slot_id' => $slot->id,
                'status' => $status,
                'photo_upgrade_count' => $photoUpgrade,
                'special_occasion' => $occasions[array_rand($occasions)],
                'special_comment' => $comments[array_rand($comments)],
                'total_price_cents' => $totalCents,
                'total_guests' => $totalGuests,
                'is_confirmed' => $isConfirmed,
                'needs_confirmation' => $needsConfirmation,
            ]);

            // Booking items
            BookingItem::create([
                'id' => (string) Str::uuid(),
                'booking_id' => $booking->id,
                'ticket_type' => 'adult',
                'quantity' => $adultQty,
                'unit_price_cents' => $adultPrice,
            ]);

            if ($childQty > 0) {
                BookingItem::create([
                    'id' => (string) Str::uuid(),
                    'booking_id' => $booking->id,
                    'ticket_type' => 'child',
                    'quantity' => $childQty,
                    'unit_price_cents' => $childPrice,
                ]);
            }

            // Primary guest
            $phone = '+1' . rand(242, 242) . rand(3000000, 9999999);
            BookingGuest::create([
                'id' => (string) Str::uuid(),
                'booking_id' => $booking->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'is_primary' => true,
            ]);

            // Additional guests (if confirmed and multi-guest)
            if ($isConfirmed && $totalGuests > 1) {
                for ($g = 1; $g < $totalGuests; $g++) {
                    $gFirst = $firstNames[array_rand($firstNames)];
                    $gLast = $lastNames[array_rand($lastNames)];
                    $gEmail = strtolower($gFirst . '.' . $gLast . rand(1, 99) . '@' . $domains[array_rand($domains)]);
                    $gPhone = '+1' . rand(242, 242) . rand(3000000, 9999999);

                    BookingGuest::create([
                        'id' => (string) Str::uuid(),
                        'booking_id' => $booking->id,
                        'first_name' => $gFirst,
                        'last_name' => $gLast,
                        'email' => $gEmail,
                        'phone' => $gPhone,
                        'is_primary' => false,
                    ]);
                }
            }

            // Payment (for non-pending)
            if ($status !== 'pending') {
                $paymentStatus = $status === 'cancelled' ? 'failed' : 'succeeded';
                Payment::create([
                    'id' => (string) Str::uuid(),
                    'booking_id' => $booking->id,
                    'stripe_intent_id' => 'pi_test_' . Str::random(14),
                    'amount_cents' => $totalCents,
                    'status' => $paymentStatus,
                    'metadata' => [],
                ]);
            }

            $bookings[] = $booking;
        }

        // Create some bookings specifically for today/tomorrow for manifest testing
        $todaySlot = $slots->where('day', strtolower(now()->format('l')))->first();
        $tomorrowDay = strtolower(now()->addDay()->format('l'));
        $tomorrowSlot = $slots->where('day', $tomorrowDay)->first();

        $manifestSlots = [];
        if ($todaySlot) $manifestSlots[] = ['slot' => $todaySlot, 'date' => now()->toDateString()];
        if ($tomorrowSlot) $manifestSlots[] = ['slot' => $tomorrowSlot, 'date' => now()->addDay()->toDateString()];

        foreach ($manifestSlots as $entry) {
            $mSlot = $entry['slot'];
            $mDate = $entry['date'];
            for ($b = 0; $b < 5; $b++) {
                $mFirst = $firstNames[array_rand($firstNames)];
                $mLast = $lastNames[array_rand($lastNames)];
                $mEmail = strtolower($mFirst . '.' . $mLast . rand(1, 999) . '@' . $domains[array_rand($domains)]);
                $mAdultQty = rand(1, 3);
                $mChildQty = rand(0, 1);
                $mPhoto = rand(0, 2);
                $mTotal = ($mAdultQty * 20000) + ($mChildQty * 15000) + ($mPhoto * 2500);
                $mGuests = $mAdultQty + $mChildQty;

                $mBooking = Booking::create([
                    'id' => (string) Str::uuid(),
                    'booking_ref' => 'CBB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'tour_date' => $mDate,
                    'time_slot_id' => $mSlot->id,
                    'status' => 'confirmed',
                    'photo_upgrade_count' => $mPhoto,
                    'special_occasion' => null,
                    'special_comment' => null,
                    'total_price_cents' => $mTotal,
                    'total_guests' => $mGuests,
                    'is_confirmed' => true,
                    'needs_confirmation' => $mGuests > 1,
                ]);

                BookingItem::create([
                    'id' => (string) Str::uuid(),
                    'booking_id' => $mBooking->id,
                    'ticket_type' => 'adult',
                    'quantity' => $mAdultQty,
                    'unit_price_cents' => 20000,
                ]);

                if ($mChildQty > 0) {
                    BookingItem::create([
                        'id' => (string) Str::uuid(),
                        'booking_id' => $mBooking->id,
                        'ticket_type' => 'child',
                        'quantity' => $mChildQty,
                        'unit_price_cents' => 15000,
                    ]);
                }

                BookingGuest::create([
                    'id' => (string) Str::uuid(),
                    'booking_id' => $mBooking->id,
                    'first_name' => $mFirst,
                    'last_name' => $mLast,
                    'email' => $mEmail,
                    'phone' => '+1242' . rand(3000000, 9999999),
                    'is_primary' => true,
                ]);

                // Add all guests for these manifest bookings
                for ($g = 1; $g < $mGuests; $g++) {
                    BookingGuest::create([
                        'id' => (string) Str::uuid(),
                        'booking_id' => $mBooking->id,
                        'first_name' => $firstNames[array_rand($firstNames)],
                        'last_name' => $lastNames[array_rand($lastNames)],
                        'email' => strtolower($firstNames[array_rand($firstNames)] . '.' . $lastNames[array_rand($lastNames)] . rand(1, 999) . '@' . $domains[array_rand($domains)]),
                        'phone' => '+1242' . rand(3000000, 9999999),
                        'is_primary' => false,
                    ]);
                }

                Payment::create([
                    'id' => (string) Str::uuid(),
                    'booking_id' => $mBooking->id,
                    'stripe_intent_id' => 'pi_test_' . Str::random(14),
                    'amount_cents' => $mTotal,
                    'status' => 'succeeded',
                    'metadata' => [],
                ]);
            }
        }

        $this->command->info('Seeded ' . ($bookingCount + 10) . ' bookings with guests, items, and payments.');
    }
}

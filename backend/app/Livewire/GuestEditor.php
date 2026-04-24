<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingGuest;
use Illuminate\Support\Str;
use Livewire\Component;

class GuestEditor extends Component
{
    public string $bookingId = '';
    public int $activeGuestIndex = 0;
    public int $expectedGuestCount = 0;
    public array $guests = [];
    public array $guestIds = [];

    public function mount(string $bookingId): void
    {
        $this->bookingId = $bookingId;
        $this->loadGuests();
    }

    public function loadGuests(): void
    {
        if (empty($this->bookingId)) return;

        $booking = Booking::with('guests', 'items')->find($this->bookingId);
        if (!$booking) return;

        $this->expectedGuestCount = (int) $booking->items->sum('quantity');
        $this->guestIds = [];
        $this->guests = [];

        foreach (range(0, max(0, $this->expectedGuestCount - 1)) as $i) {
            $existing = $booking->guests[$i] ?? null;
            if ($existing) {
                $this->guestIds[$i] = $existing->id;
                $this->guests[$i] = [
                    'first_name' => $existing->first_name ?? '',
                    'last_name' => $existing->last_name ?? '',
                    'email' => $existing->email ?? '',
                    'phone' => $existing->phone ?? '',
                ];
            } else {
                $this->guestIds[$i] = null;
                $this->guests[$i] = [
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                ];
            }
        }
    }

    public function selectGuest(int $index): void
    {
        $this->activeGuestIndex = $index;
    }

    public function saveGuest(): void
    {
        $i = $this->activeGuestIndex;
        $data = $this->guests[$i];

        if (empty(trim($data['first_name'] ?? '')) && empty(trim($data['last_name'] ?? ''))) {
            $this->js("window.Filament && Filament.notify({ type: 'warning', message: 'Please provide at least a name.' });");
            return;
        }

        $booking = Booking::findOrFail($this->bookingId);

        if ($this->guestIds[$i]) {
            BookingGuest::where('id', $this->guestIds[$i])->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
        } else {
            $guest = $booking->guests()->create([
                'id' => (string) Str::uuid(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_primary' => $i === 0,
            ]);
            $this->guestIds[$i] = $guest->id;
        }

        // Auto-complete when all guests collected
        $totalGuests = $booking->items()->sum('quantity');
        $collectedGuests = $booking->guests()->count();
        if ($collectedGuests >= $totalGuests) {
            $booking->update([
                'is_confirmed' => true,
                'needs_confirmation' => false,
                'status' => 'confirmed',
            ]);
        }

        $this->js("window.Filament && Filament.notify({ type: 'success', message: 'Guest " . ($i + 1) . " saved.' });");
    }

    public function saveAndNext(): void
    {
        $this->saveGuest();
        if ($this->activeGuestIndex < $this->expectedGuestCount - 1) {
            $this->activeGuestIndex++;
        }
    }

    public function saveAndFinish(): void
    {
        $this->saveGuest();
        $this->redirect(\App\Filament\Pages\IncompleteBookings::getUrl());
    }

    public function render()
    {
        return view('livewire.guest-editor');
    }
}

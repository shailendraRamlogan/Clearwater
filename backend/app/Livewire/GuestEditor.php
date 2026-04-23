<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingGuest;
use Illuminate\Support\Str;
use Livewire\Component;

class GuestEditor extends Component
{
    public Booking $booking;
    public int $activeGuestIndex = 0;
    public int $expectedGuestCount = 0;
    public array $guests = [];
    public array $guestIds = [];

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('guests', 'items');
        $this->expectedGuestCount = (int) $booking->items->sum('quantity');
        $this->loadGuests();
    }

    public function loadGuests(): void
    {
        $this->guestIds = [];
        $this->guests = [];

        foreach (range(0, $this->expectedGuestCount - 1) as $i) {
            $existing = $this->booking->guests[$i] ?? null;
            if ($existing) {
                $this->guestIds[$i] = $existing->id;
                $this->guests[$i] = [
                    'first_name' => $existing->first_name,
                    'last_name' => $existing->last_name,
                    'email' => $existing->email ?? '',
                    'phone' => $existing->phone ?? '',
                    'is_primary' => $existing->is_primary ?? ($i === 0),
                ];
            } else {
                $this->guestIds[$i] = null;
                $this->guests[$i] = [
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                    'is_primary' => $i === 0,
                ];
            }
        }
    }

    public function selectGuest(int $index): void
    {
        $this->activeGuestIndex = $index;
    }

    public function updatedGuests(): void
    {
        // Livewire auto-tracks
    }

    public function saveGuest(): void
    {
        $i = $this->activeGuestIndex;
        $data = $this->guests[$i];

        if (empty($data['first_name']) && empty($data['last_name'])) {
            $this->dispatch('notify', type: 'warning', message: 'Please provide at least a name.');
            return;
        }

        if ($this->guestIds[$i]) {
            $guest = BookingGuest::find($this->guestIds[$i]);
            $guest->update($data);
        } else {
            $guest = $this->booking->guests()->create([
                'id' => (string) Str::uuid(),
                ...$data,
            ]);
            $this->guestIds[$i] = $guest->id;
        }

        $this->booking->refresh();
        $this->dispatch('notify', type: 'success', message: "Guest {$i + 1} saved.");
    }

    public function saveAndNext(): void
    {
        $this->saveGuest();
        if ($this->activeGuestIndex < $this->expectedGuestCount - 1) {
            $this->activeGuestIndex++;
        }
    }

    public function render()
    {
        return view('livewire.guest-editor');
    }
}

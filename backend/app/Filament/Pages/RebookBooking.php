<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Services\RebookService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RebookBooking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Rebook Booking';
    protected static ?string $navigationGroup = 'Bookings';
    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.rebook-booking';

    public ?string $searchRef = null;
    public ?Booking $foundBooking = null;
    public ?string $newDate = null;
    public ?float $feeDollars = 0;
    public ?string $newTimeSlotId = null;
    public ?string $newStartTime = null;
    public ?string $newEndTime = null;
    public array $availableSlots = [];
    public ?string $step = 'search';
    public ?string $error = null;
    public bool $showConfirmModal = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'super_admin', 'agent']);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('searchRef')
                ->label('Booking Reference')
                ->placeholder('e.g. CBB-20260508-XXXX or AGT-20260508-XXXX')
                ->required()
                ->maxLength(50)
                ->live(onBlur: true)
                ->afterStateUpdated(function () {
                    $ref = trim($this->searchRef ?? '');
                    if (empty($ref)) {
                        $this->foundBooking = null;
                        $this->step = 'search';
                        $this->error = null;
                        return;
                    }

                    $booking = Booking::where('booking_ref', $ref)
                        ->with(['primaryGuest', 'timeSlot.boat', 'items', 'addons.addon', 'bookingAgent'])
                        ->first();

                    if (!$booking) {
                        $this->foundBooking = null;
                        $this->step = 'search';
                        $this->error = 'Booking not found.';
                        return;
                    }

                    if ($booking->status === 'cancelled') {
                        $this->foundBooking = null;
                        $this->step = 'search';
                        $this->error = 'This booking has already been cancelled.';
                        return;
                    }

                    $this->foundBooking = $booking;
                    $this->step = 'confirm';
                    $this->error = null;
                    $this->newDate = null;
                    $this->newTimeSlotId = null;
                    $this->newStartTime = null;
                    $this->newEndTime = null;
                    $this->feeDollars = 0;
                }),
        ];
    }

    public function updatedNewDate(): void
    {
        $this->newTimeSlotId = null;
        $this->newStartTime = null;
        $this->newEndTime = null;
        $this->availableSlots = [];

        if (empty($this->newDate)) {
            return;
        }

        // Only load regular time slots for non-private bookings
        if ($this->foundBooking?->time_slot_id) {
            $date = Carbon::parse($this->newDate);
            $dayName = strtolower($date->format('l'));

            $slots = \App\Models\TimeSlot::with('boat')
                ->where('day', $dayName)
                ->where('is_blocked', false)
                ->get();

            if ($slots->isEmpty()) {
                return;
            }

            $ticketCount = $this->foundBooking?->total_guests ?? 0;

            foreach ($slots as $slot) {
                $remaining = $slot->remainingCapacity($date->toDateString());
                $existingGuests = $slot->max_capacity - $remaining;

                $this->availableSlots[] = [
                    'id' => $slot->id,
                    'time' => \Carbon\Carbon::parse($slot->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($slot->end_time)->format('g:i A'),
                    'boat' => $slot->boat?->name ?? 'N/A',
                    'remaining' => $remaining,
                    'full' => $remaining < $ticketCount,
                    'capacity' => $slot->max_capacity,
                    'booked' => $existingGuests,
                ];
            }
        }
    }

    public function openConfirmModal(): void
    {
        $this->showConfirmModal = true;
    }

    public function submitRebook(): void
    {
        if (!$this->foundBooking) {
            Notification::make()->title('No booking selected.')->danger()->send();
            return;
        }

        if (empty($this->newDate)) {
            Notification::make()->title('Please select a new date.')->danger()->send();
            return;
        }

        // Regular bookings: require a time slot
        if ($this->foundBooking->time_slot_id && empty($this->newTimeSlotId)) {
            Notification::make()->title('Please select a time slot.')->danger()->send();
            return;
        }

        // Private tours: require start and end times
        if (!$this->foundBooking->time_slot_id && (empty($this->newStartTime) || empty($this->newEndTime))) {
            Notification::make()->title('Please select a start and end time for the private tour.')->danger()->send();
            return;
        }

        if (!$this->foundBooking->time_slot_id && $this->newStartTime >= $this->newEndTime) {
            Notification::make()->title('End time must be after start time.')->danger()->send();
            return;
        }

        $newDate = Carbon::parse($this->newDate);
        $feeCents = (int) round(($this->feeDollars ?? 0) * 100);

        // Check email if fee > 0
        if ($feeCents > 0) {
            $guest = $this->foundBooking->primaryGuest;
            if (!$guest || empty($guest->email)) {
                Notification::make()
                    ->title('Cannot apply fee')
                    ->body('No customer email on file. Set fee to $0 or add an email first.')
                    ->danger()
                    ->send();
                return;
            }
        }

        try {
            $service = app(RebookService::class);
            $result = $service->rebook(
                $this->foundBooking,
                $newDate,
                $feeCents,
                Auth::user(),
                $this->newTimeSlotId ?: null,
                $this->newStartTime ?: null,
                $this->newEndTime ?: null,
            );

            $msg = "Booking rebooked! New reference: {$result['booking']->booking_ref}";
            if ($result['payment_link']) {
                $msg .= ' Payment link sent to customer.';
            }
            foreach ($result['warnings'] as $warning) {
                $msg .= " ⚠️ {$warning}";
            }

            Notification::make()->title($msg)->success()->send();

            // Reset
            $this->showConfirmModal = false;
            $this->foundBooking = null;
            $this->step = 'search';
            $this->searchRef = null;
            $this->newDate = null;
            $this->newTimeSlotId = null;
            $this->newStartTime = null;
            $this->newEndTime = null;
            $this->feeDollars = 0;
            $this->availableSlots = [];
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Rebook failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function goBack(): void
    {
        $this->showConfirmModal = false;
        $this->foundBooking = null;
        $this->step = 'search';
        $this->searchRef = null;
        $this->newDate = null;
        $this->newTimeSlotId = null;
        $this->newStartTime = null;
        $this->newEndTime = null;
        $this->feeDollars = 0;
        $this->availableSlots = [];
        $this->error = null;
        $this->form->fill();
    }
}

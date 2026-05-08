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
    public ?string $step = 'search';
    public ?string $error = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isAdminOrSuper());
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
                    $this->feeDollars = 0;
                }),
        ];
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
            $result = $service->rebook($this->foundBooking, $newDate, $feeCents, Auth::user());

            $msg = "Booking rebooked! New reference: {$result['booking']->booking_ref}";
            if ($result['payment_link']) {
                $msg .= ' Payment link sent to customer.';
            }
            foreach ($result['warnings'] as $warning) {
                $msg .= " ⚠️ {$warning}";
            }

            Notification::make()->title($msg)->success()->send();

            // Reset
            $this->foundBooking = null;
            $this->step = 'search';
            $this->searchRef = null;
            $this->newDate = null;
            $this->feeDollars = 0;
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
        $this->foundBooking = null;
        $this->step = 'search';
        $this->searchRef = null;
        $this->newDate = null;
        $this->feeDollars = 0;
        $this->error = null;
        $this->form->fill();
    }
}

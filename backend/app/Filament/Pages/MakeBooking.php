<?php

namespace App\Filament\Pages;

use App\Models\Addon;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\Boat;
use App\Models\PrivateTourRequest;
use App\Models\TimeSlot;
use App\Services\EmailService;
use App\Services\FeeService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MakeBooking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static string $view = 'filament.pages.make-booking';
    protected static ?int $navigationSort = 5;

    public function getHeading(): string
    {
        return 'Make a Booking';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'agent';
    }

    public ?array $regularData = [];
    public ?array $privateData = [];

    public function mount(): void
    {
        $this->regularForm->fill();
        $this->privateForm->fill();
    }

    public function regularForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Guest Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('guest_first_name')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('guest_last_name')
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('guest_email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('guest_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Sailing Details')
                    ->schema([
                        Select::make('boat_id')
                            ->label('Boat')
                            ->required()
                            ->options(Boat::where('is_active', true)->pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('time_slot_id', null)),
                        DatePicker::make('tour_date')
                            ->label('Tour Date')
                            ->required()
                            ->minDate(now())
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('time_slot_id', null)),
                        Select::make('time_slot_id')
                            ->label('Time Slot')
                            ->required()
                            ->options(function (callable $get) {
                                $boatId = $get('boat_id');
                                $date = $get('tour_date');
                                if (!$boatId || !$date) return [];
                                $day = strtolower(\Carbon\Carbon::parse($date)->format('l'));
                                return TimeSlot::where('boat_id', $boatId)
                                    ->where('day', $day)
                                    ->where('is_blocked', false)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [
                                        $s->id => \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('g:i A')
                                            . ' – '
                                            . \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->format('g:i A')
                                            . ' (' . $s->remainingCapacity($date) . ' spots)',
                                    ]);
                            }),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('adult_count')
                                    ->label('Adults')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                                TextInput::make('child_count')
                                    ->label('Children (under 12)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Add-ons')
                    ->schema([
                        CheckboxList::make('addons')
                            ->label('')
                            ->options(function () {
                                return Addon::active()
                                    ->forRegularTours()
                                    ->orderBy('sort_order')
                                    ->pluck('title', 'id');
                            })
                            ->columns(2),
                    ]),

                Section::make('Additional Details')
                    ->schema([
                        Textarea::make('special_comment')
                            ->label('Special Requests / Notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ])
            ->statePath('regularData');
    }

    public function privateForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('contact_first_name')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('contact_last_name')
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('contact_email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Tour Details')
                    ->schema([
                        DatePicker::make('confirmed_tour_date')
                            ->label('Tour Date')
                            ->required()
                            ->minDate(now()),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('confirmed_start_time')
                                    ->label('Start Time')
                                    ->required()
                                    ->placeholder('e.g. 10:00 AM'),
                                TextInput::make('confirmed_end_time')
                                    ->label('End Time')
                                    ->placeholder('e.g. 1:00 PM'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('adult_count')
                                    ->label('Adults')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                                TextInput::make('child_count')
                                    ->label('Children')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                TextInput::make('infant_count')
                                    ->label('Infants')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),
                        TextInput::make('total_price_dollars')
                            ->label('Total Price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->placeholder('e.g. 500.00'),
                    ]),

                Section::make('Add-ons')
                    ->schema([
                        CheckboxList::make('addons')
                            ->label('')
                            ->options(function () {
                                return Addon::active()
                                    ->forPrivateTours()
                                    ->orderBy('sort_order')
                                    ->pluck('title', 'id');
                            })
                            ->columns(2),
                    ]),

                Section::make('Additional Notes')
                    ->schema([
                        Textarea::make('occasion_details')
                            ->label('Special Occasion Details')
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('admin_notes')
                            ->label('Internal Notes (not shown to guest)')
                            ->rows(2)
                            ->maxLength(1000),
                    ]),
            ])
            ->statePath('privateData');
    }

    public function createRegularBooking(): void
    {
        $data = $this->regularForm->getState();

        try {
            $result = DB::transaction(function () use ($data) {
                $timeSlot = TimeSlot::where('id', $data['time_slot_id'])->lockForUpdate()->first();
                if (!$timeSlot) {
                    throw new \Exception('Time slot not found.');
                }

                $totalGuests = $data['adult_count'] + $data['child_count'];
                $existingBooked = Booking::where('time_slot_id', $data['time_slot_id'])
                    ->where('tour_date', $data['tour_date'])
                    ->whereNotIn('status', ['cancelled'])
                    ->get()
                    ->sum(fn ($b) => $b->items->sum('quantity'));

                if ($existingBooked + $totalGuests > $timeSlot->max_capacity) {
                    throw new \Exception('This time slot is full.');
                }

                $adultPrice = 20000;
                $childPrice = 15000;
                $adultTotal = $data['adult_count'] * $adultPrice;
                $childTotal = $data['child_count'] * $childPrice;

                $addonsTotal = 0;
                $addonItems = [];
                if (!empty($data['addons'])) {
                    foreach ($data['addons'] as $addonId) {
                        $addon = Addon::where('id', $addonId)->where('is_active', true)->first();
                        if ($addon) {
                            $addonsTotal += $addon->price_cents * $totalGuests;
                            $addonItems[] = ['addon' => $addon, 'quantity' => $totalGuests];
                        }
                    }
                }

                $totalCents = $adultTotal + $childTotal + $addonsTotal;
                $feeService = app(FeeService::class);
                $feeResult = $feeService->calculateFees($totalCents);

                $booking = Booking::create([
                    'tour_date' => $data['tour_date'],
                    'time_slot_id' => $data['time_slot_id'],
                    'status' => 'confirmed',
                    'photo_upgrade_count' => 0,
                    'special_comment' => $data['special_comment'] ?? null,
                    'total_price_cents' => $totalCents,
                    'fees_cents' => $feeResult['total_fees_cents'],
                ]);

                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'first_name' => $data['guest_first_name'],
                    'last_name' => $data['guest_last_name'],
                    'email' => $data['guest_email'],
                    'phone' => $data['guest_phone'] ?? '',
                    'is_primary' => true,
                ]);

                if ($data['adult_count'] > 0) {
                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'ticket_type' => 'adult',
                        'quantity' => $data['adult_count'],
                        'unit_price_cents' => $adultPrice,
                    ]);
                }
                if ($data['child_count'] > 0) {
                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'ticket_type' => 'child',
                        'quantity' => $data['child_count'],
                        'unit_price_cents' => $childPrice,
                    ]);
                }

                foreach ($addonItems as $ai) {
                    BookingAddon::create([
                        'booking_id' => $booking->id,
                        'addon_id' => $ai['addon']->id,
                        'quantity' => $ai['quantity'],
                        'unit_price_cents' => $ai['addon']->price_cents,
                    ]);
                }

                return $booking;
            });

            try {
                app(EmailService::class)->sendConfirmation($result);
            } catch (\Exception $e) {
                \Log::warning('Agent booking email error: ' . $e->getMessage());
            }

            Notification::make()
                ->title('Booking Created')
                ->body("Regular sailing booked: {$result->booking_ref}")
                ->success()
                ->send();

            $this->regularForm->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createPrivateBooking(): void
    {
        $data = $this->privateForm->getState();

        try {
            $result = DB::transaction(function () use ($data) {
                $totalPriceCents = (int) round($data['total_price_dollars'] * 100);

                $feeService = app(FeeService::class);
                $feeResult = $feeService->calculateFees($totalPriceCents);

                $ptr = PrivateTourRequest::create([
                    'contact_first_name' => $data['contact_first_name'],
                    'contact_last_name' => $data['contact_last_name'],
                    'contact_email' => $data['contact_email'],
                    'contact_phone' => $data['contact_phone'] ?? '',
                    'adult_count' => $data['adult_count'],
                    'child_count' => $data['child_count'],
                    'infant_count' => $data['infant_count'] ?? 0,
                    'has_occasion' => !empty($data['occasion_details']),
                    'occasion_details' => $data['occasion_details'] ?? null,
                    'status' => PrivateTourRequest::STATUS_CONFIRMED,
                    'confirmed_tour_date' => $data['confirmed_tour_date'],
                    'confirmed_start_time' => $data['confirmed_start_time'],
                    'confirmed_end_time' => $data['confirmed_end_time'] ?? null,
                    'total_price_cents' => $totalPriceCents,
                    'fees_cents' => $feeResult['total_fees_cents'],
                    'admin_notes' => $data['admin_notes'] ?? null,
                ]);

                $ptr->guests()->create([
                    'first_name' => $data['contact_first_name'],
                    'last_name' => $data['contact_last_name'],
                    'email' => $data['contact_email'],
                    'phone' => $data['contact_phone'] ?? '',
                    'is_primary' => true,
                ]);

                if (!empty($data['addons'])) {
                    foreach ($data['addons'] as $addonId) {
                        $addon = Addon::where('id', $addonId)->where('is_active', true)->first();
                        if ($addon) {
                            $ptr->addons()->create([
                                'addon_id' => $addon->id,
                                'unit_price_cents' => $addon->private_price_cents ?? 0,
                            ]);
                        }
                    }
                }

                $timeSlotId = TimeSlot::first()?->id;
                $totalGuests = $data['adult_count'] + $data['child_count'];
                $timeDisplay = $data['confirmed_start_time'];
                if (!empty($data['confirmed_end_time'])) {
                    $timeDisplay .= ' – ' . $data['confirmed_end_time'];
                }

                $booking = Booking::create([
                    'tour_date' => $data['confirmed_tour_date'],
                    'time_slot_id' => $timeSlotId,
                    'status' => 'confirmed',
                    'source_type' => 'private',
                    'photo_upgrade_count' => 0,
                    'special_occasion' => !empty($data['occasion_details']) ? 'other' : null,
                    'special_comment' => "Private Tour ({$ptr->booking_ref}) — {$timeDisplay}",
                    'total_price_cents' => $totalPriceCents,
                    'fees_cents' => $feeResult['total_fees_cents'],
                ]);

                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'first_name' => $data['contact_first_name'],
                    'last_name' => $data['contact_last_name'],
                    'email' => $data['contact_email'],
                    'phone' => $data['contact_phone'] ?? '',
                    'is_primary' => true,
                ]);

                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'private_tour',
                    'quantity' => $totalGuests,
                    'unit_price_cents' => $totalPriceCents,
                ]);

                foreach ($ptr->addons as $pta) {
                    BookingAddon::create([
                        'booking_id' => $booking->id,
                        'addon_id' => $pta->addon_id,
                        'quantity' => $totalGuests,
                        'unit_price_cents' => $pta->unit_price_cents ?? 0,
                    ]);
                }

                $ptr->update([
                    'booking_id' => $booking->id,
                    'status' => PrivateTourRequest::STATUS_COMPLETED,
                ]);

                return ['ptr' => $ptr, 'booking' => $booking];
            });

            try {
                app(EmailService::class)->sendConfirmation($result['booking']);
            } catch (\Exception $e) {
                \Log::warning('Agent private booking email error: ' . $e->getMessage());
            }

            Notification::make()
                ->title('Private Tour Booked')
                ->body("Private tour booked: {$result['ptr']->booking_ref}")
                ->success()
                ->send();

            $this->privateForm->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

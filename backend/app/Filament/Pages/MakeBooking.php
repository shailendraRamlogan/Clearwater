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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MakeBooking extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static string $view = 'filament.pages.make-booking';
    protected static ?int $navigationSort = 5;

    public ?array $formData = [];

    public function getHeading(): string
    {
        return 'Make a Booking';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'agent';
    }

    public function mount(): void
    {
        $this->form->fill([
            'booking_type' => 'regular',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // ── Step 0: Choose Booking Type ──
                    Step::make('Booking Type')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->schema([
                            Radio::make('booking_type')
                                ->label('')
                                ->options([
                                    'regular' => 'Regular Sailing — Standard scheduled tour with individual tickets',
                                    'private' => 'Private Tour — Exclusive boat booking for your group',
                                ])
                                ->default('regular')
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    // ═══════════════════════════════════════════
                    // REGULAR SAILING WIZARD
                    // ═══════════════════════════════════════════

                    // Step 1: Pick Date
                    Step::make('Date')
                        ->icon('heroicon-o-calendar-days')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
                        ->schema([
                            Placeholder::make('date_hint')
                                ->label('')
                                ->content('Pick the date for the sailing.'),
                            DatePicker::make('tour_date')
                                ->label('Tour Date')
                                ->required()
                                ->minDate(now())
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set) => $set('time_slot_id', null))
                                ->columnSpanFull()
                                ->live()
                                ->afterStateUpdated(function (callable $set, callable $get) {
                                    // Pre-load available boats for this day
                                    $date = $get('tour_date');
                                    if ($date) {
                                        $day = strtolower(\Carbon\Carbon::parse($date)->format('l'));
                                        $boats = TimeSlot::where('day', $day)
                                            ->where('is_blocked', false)
                                            ->distinct()
                                            ->pluck('boat_id');
                                        $boatOptions = Boat::whereIn('id', $boats)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                        if ($boatOptions->count() === 1) {
                                            $set('boat_id', $boatOptions->keys()->first());
                                        }
                                    }
                                }),
                            Placeholder::make('date_display')
                                ->label('Selected')
                                ->visible(fn (callable $get) => $get('tour_date'))
                                ->content(fn (callable $get) => $get('tour_date')
                                    ? \Carbon\Carbon::parse($get('tour_date'))->format('l, F j, Y')
                                    : ''),
                        ]),

                    // Step 2: Pick Time Slot (card-style)
                    Step::make('Time Slot')
                        ->icon('heroicon-o-clock')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
                        ->schema([
                            Placeholder::make('time_slot_header')
                                ->label('')
                                ->visible(fn (callable $get) => $get('tour_date'))
                                ->content(fn (callable $get) => $get('tour_date')
                                    ? 'Available times for ' . \Carbon\Carbon::parse($get('tour_date'))->format('l, F j, Y')
                                    : 'Please select a date first.'),
                            Placeholder::make('no_date_warning')
                                ->label('')
                                ->visible(fn (callable $get) => !$get('tour_date'))
                                ->content('⚠️ Go back and select a date first.')
                                ->extraAttributes(['class' => 'text-warning']),
                            Radio::make('time_slot_id')
                                ->label('Available Departures')
                                ->required()
                                ->visible(fn (callable $get) => $get('tour_date'))
                                ->options(function (callable $get) {
                                    $date = $get('tour_date');
                                    if (!$date) return [];
                                    $day = strtolower(\Carbon\Carbon::parse($date)->format('l'));
                                    $slots = TimeSlot::where('day', $day)
                                        ->where('is_blocked', false)
                                        ->with('boat')
                                        ->get();
                                    return $slots->mapWithKeys(function ($s) use ($date) {
                                        $cap = $s->remainingCapacity($date);
                                        $boatName = $s->boat?->name ?? 'Boat';
                                        $time = \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('g:i A')
                                            . ' – '
                                            . \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->format('g:i A');
                                        $label = "🚢 {$boatName}  •  {$time}  •  {$cap} spots left";
                                        return [$s->id => $label];
                                    });
                                })
                                ->descriptions(function (callable $get) {
                                    $date = $get('tour_date');
                                    if (!$date) return [];
                                    $day = strtolower(\Carbon\Carbon::parse($date)->format('l'));
                                    $slots = TimeSlot::where('day', $day)
                                        ->where('is_blocked', false)
                                        ->get();
                                    return $slots->mapWithKeys(function ($s) use ($date) {
                                        $cap = $s->remainingCapacity($date);
                                        $total = $s->max_capacity;
                                        return [$s->id => "{$cap} of {$total} spots available"];
                                    });
                                })
                                ->gridDirection('row')
                                ->columns(1)
                                ->columnSpanFull()
                                ->live()
                                ->afterStateUpdated(function (callable $set, callable $get) {
                                    $slotId = $get('time_slot_id');
                                    if ($slotId) {
                                        $slot = TimeSlot::find($slotId);
                                        if ($slot) {
                                            $set('boat_id', $slot->boat_id);
                                        }
                                    }
                                }),
                            Hidden::make('boat_id')
                                ->default(null),
                        ]),

                    // Step 3: Tickets
                    Step::make('Tickets')
                        ->icon('heroicon-o-ticket')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
                        ->schema([
                            TextInput::make('adult_count')
                                ->label('Adult Tickets')
                                ->helperText('$200.00 per adult')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->reactive(),
                            TextInput::make('child_count')
                                ->label('Child Tickets (under 12)')
                                ->helperText('$150.00 per child')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->reactive(),
                            Placeholder::make('ticket_summary')
                                ->label('Total Guests')
                                ->content(function (callable $get) {
                                    $adults = $get('adult_count') ?? 0;
                                    $children = $get('child_count') ?? 0;
                                    $total = $adults + $children;
                                    return "{$total} guest" . ($total !== 1 ? 's' : '') . " ({$adults} adult" . ($adults !== 1 ? 's' : '') . ", {$children} child" . ($children !== 1 ? 'ren' : '') . ")";
                                }),
                            Placeholder::make('ticket_total')
                                ->label('Ticket Subtotal')
                                ->content(function (callable $get) {
                                    $adults = ($get('adult_count') ?? 0) * 200;
                                    $children = ($get('child_count') ?? 0) * 150;
                                    return '$' . number_format($adults + $children, 2);
                                }),
                        ]),

                    // Step 4: Guest Details
                    Step::make('Guest Details')
                        ->icon('heroicon-o-user')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
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
                                ->required()
                                ->maxLength(255),
                            Textarea::make('special_comment')
                                ->label('Special Requests (optional)')
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ]),

                    // Step 5: Add-ons
                    Step::make('Add-ons')
                        ->icon('heroicon-o-sparkles')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
                        ->schema([
                            CheckboxList::make('addons')
                                ->label('')
                                ->options(function () {
                                    return Addon::active()
                                        ->forRegularTours()
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(fn ($a) => [
                                            $a->id => $a->title . ' — $' . number_format($a->price_cents / 100, 2)
                                                . ($a->description ? ' (' . $a->description . ')' : ''),
                                        ]);
                                })
                                ->columns(1)
                                ->columnSpanFull(),
                        ]),

                    // Step 6: Review & Create
                    Step::make('Review & Create')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (callable $get) => $get('booking_type') === 'regular')
                        ->schema([
                            Placeholder::make('review_regular')
                                ->label('Booking Summary')
                                ->content(function (callable $get) {
                                    $adults = $get('adult_count') ?? 0;
                                    $children = $get('child_count') ?? 0;
                                    $adultTotal = $adults * 200;
                                    $childTotal = $children * 150;
                                    $subtotal = $adultTotal + $childTotal;
                                    $totalGuests = $adults + $children;

                                    // Get slot info
                                    $slotInfo = '';
                                    $slot = $get('time_slot_id') ? TimeSlot::find($get('time_slot_id')) : null;
                                    if ($slot) {
                                        $boatName = $slot->boat?->name ?? 'Boat';
                                        $timeStr = \Carbon\Carbon::createFromFormat('H:i:s', $slot->start_time)->format('g:i A')
                                            . ' – '
                                            . \Carbon\Carbon::createFromFormat('H:i:s', $slot->end_time)->format('g:i A');
                                        $slotInfo = "Boat: {$boatName}\nTime: {$timeStr}";
                                    }

                                    $addonTotal = 0;
                                    $addonLines = [];
                                    if (!empty($get('addons'))) {
                                        foreach ($get('addons') as $addonId) {
                                            $addon = Addon::find($addonId);
                                            if ($addon) {
                                                $addonLineTotal = $addon->price_cents / 100 * $totalGuests;
                                                $addonTotal += $addonLineTotal;
                                                $addonLines[] = "  • {$addon->title}: {$totalGuests}× $" . number_format($addon->price_cents / 100, 2) . " = $" . number_format($addonLineTotal, 2);
                                            }
                                        }
                                    }

                                    $lines = [
                                        "Type: Regular Sailing",
                                        "Date: " . ($get('tour_date') ? \Carbon\Carbon::parse($get('tour_date'))->format('F j, Y') : '—'),
                                    ];
                                    if ($slotInfo) $lines[] = $slotInfo;
                                    $lines[] = "Guests: {$totalGuests} ({$adults} adults, {$children} children)";
                                    $lines[] = "Tickets: \${$adultTotal}.00 + \${$childTotal}.00 = \${$subtotal}.00";
                                    if ($addonLines) {
                                        $lines[] = "\nAdd-ons:";
                                        $lines = array_merge($lines, $addonLines);
                                        $lines[] = "Add-on Total: \$" . number_format($addonTotal, 2);
                                    }
                                    $grandTotal = $subtotal + $addonTotal;
                                    $lines[] = "";
                                    $lines[] = "Primary Guest: " . ($get('guest_first_name') ?? '') . " " . ($get('guest_last_name') ?? '');
                                    $lines[] = "Email: " . ($get('guest_email') ?? '');
                                    $lines[] = "Phone: " . ($get('guest_phone') ?? '');
                                    if ($get('special_comment')) {
                                        $lines[] = "Notes: " . $get('special_comment');
                                    }
                                    $lines[] = "";
                                    $lines[] = "━━━ TOTAL: \$" . number_format($grandTotal, 2) . " ━━━";

                                    return implode("\n", $lines);
                                })
                                ->columnSpanFull(),
                        ])
                        ->afterValidation(function () {
                            $data = $this->form->getState();
                            $this->createRegularBooking($data);
                        }),

                    // ═══════════════════════════════════════════
                    // PRIVATE TOUR WIZARD
                    // ═══════════════════════════════════════════

                    Step::make('Party Size')
                        ->icon('heroicon-o-users')
                        ->visible(fn (callable $get) => $get('booking_type') === 'private')
                        ->schema([
                            TextInput::make('adult_count')
                                ->label('Adults')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->reactive(),
                            TextInput::make('child_count')
                                ->label('Children (under 12)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->reactive(),
                            TextInput::make('infant_count')
                                ->label('Infants (free)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->reactive(),
                            Placeholder::make('party_summary')
                                ->label('Total Party Size')
                                ->content(function (callable $get) {
                                    $total = ($get('adult_count') ?? 0) + ($get('child_count') ?? 0) + ($get('infant_count') ?? 0);
                                    return "{$total} guest" . ($total !== 1 ? 's' : '');
                                }),
                        ]),

                    Step::make('Tour Details')
                        ->icon('heroicon-o-map-pin')
                        ->visible(fn (callable $get) => $get('booking_type') === 'private')
                        ->schema([
                            DatePicker::make('confirmed_tour_date')
                                ->label('Tour Date')
                                ->required()
                                ->minDate(now()),
                            TextInput::make('confirmed_start_time')
                                ->label('Start Time')
                                ->required()
                                ->placeholder('e.g. 10:00 AM'),
                            TextInput::make('confirmed_end_time')
                                ->label('End Time')
                                ->placeholder('e.g. 1:00 PM'),
                            TextInput::make('total_price_dollars')
                                ->label('Total Price ($)')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->helperText('Set the total price for the private tour booking'),
                        ]),

                    Step::make('Contact Details')
                        ->icon('heroicon-o-user')
                        ->visible(fn (callable $get) => $get('booking_type') === 'private')
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
                                ->required()
                                ->maxLength(255),
                            Textarea::make('occasion_details')
                                ->label('Special Occasion (optional)')
                                ->rows(2)
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Add-ons')
                        ->icon('heroicon-o-sparkles')
                        ->visible(fn (callable $get) => $get('booking_type') === 'private')
                        ->schema([
                            CheckboxList::make('addons')
                                ->label('')
                                ->options(function () {
                                    return Addon::active()
                                        ->forPrivateTours()
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(fn ($a) => [
                                            $a->id => $a->title . ($a->private_price_cents ? ' — $' . number_format($a->private_price_cents / 100, 2) : '')
                                                . ($a->description ? ' (' . $a->description . ')' : ''),
                                        ]);
                                })
                                ->columns(1)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Review & Create')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (callable $get) => $get('booking_type') === 'private')
                        ->schema([
                            Placeholder::make('review_private')
                                ->label('Private Tour Summary')
                                ->content(function (callable $get) {
                                    $adults = $get('adult_count') ?? 0;
                                    $children = $get('child_count') ?? 0;
                                    $infants = $get('infant_count') ?? 0;
                                    $total = $adults + $children + $infants;

                                    $lines = [
                                        "Type: Private Tour",
                                        "Date: " . ($get('confirmed_tour_date') ? \Carbon\Carbon::parse($get('confirmed_tour_date'))->format('F j, Y') : '—'),
                                        "Time: " . ($get('confirmed_start_time') ?? '—') . ($get('confirmed_end_time') ? ' – ' . $get('confirmed_end_time') : ''),
                                        "Party: {$total} ({$adults} adults, {$children} children, {$infants} infants)",
                                        "Price: \$" . number_format($get('total_price_dollars') ?? 0, 2),
                                    ];

                                    if (!empty($get('addons'))) {
                                        $lines[] = "Add-ons included.";
                                    }

                                    $lines[] = "";
                                    $lines[] = "Contact: " . ($get('guest_first_name') ?? '') . " " . ($get('guest_last_name') ?? '');
                                    $lines[] = "Email: " . ($get('guest_email') ?? '');
                                    $lines[] = "Phone: " . ($get('guest_phone') ?? '');
                                    if ($get('occasion_details')) {
                                        $lines[] = "Occasion: " . $get('occasion_details');
                                    }

                                    $lines[] = "";
                                    $lines[] = "━━━ TOTAL: \$" . number_format($get('total_price_dollars') ?? 0, 2) . " ━━━";

                                    return implode("\n", $lines);
                                })
                                ->columnSpanFull(),
                            Textarea::make('admin_notes')
                                ->label('Internal Notes (not shown to guest)')
                                ->rows(2)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->afterValidation(function () {
                            $data = $this->form->getState();
                            $this->createPrivateBooking($data);
                        }),
                ])
                    ->startOnStep(fn (callable $get) => ($get('booking_type') === 'regular') ? 1 : 1)
                    ->submitAction('')
                    ->skippable(false)
                    ->columnSpanFull(),
            ])
            ->statePath('formData');
    }

    protected function createRegularBooking(array $data): void
    {
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
                    throw new \Exception('This time slot is full. ' . $timeSlot->remainingCapacity($data['tour_date']) . ' spots remaining.');
                }

                $adultPrice = 20000;
                $childPrice = 15000;
                $adultTotal = $data['adult_count'] * $adultPrice;
                $childTotal = $data['child_count'] * $childPrice;

                $addonItems = [];
                if (!empty($data['addons'])) {
                    foreach ($data['addons'] as $addonId) {
                        $addon = Addon::where('id', $addonId)->where('is_active', true)->first();
                        if ($addon) {
                            $addonItems[] = ['addon' => $addon, 'quantity' => $totalGuests];
                        }
                    }
                }

                $totalCents = $adultTotal + $childTotal;
                foreach ($addonItems as $ai) {
                    $totalCents += $ai['addon']->price_cents * $ai['quantity'];
                }

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
                ->title('Booking Created!')
                ->body("Regular sailing booked: **{$result->booking_ref}** — " . \Carbon\Carbon::parse($result->tour_date)->format('F j, Y'))
                ->success()
                ->persistent()
                ->send();

            $this->form->fill(['booking_type' => 'regular']);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Booking Failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function createPrivateBooking(array $data): void
    {
        try {
            $result = DB::transaction(function () use ($data) {
                $totalPriceCents = (int) round($data['total_price_dollars'] * 100);

                $feeService = app(FeeService::class);
                $feeResult = $feeService->calculateFees($totalPriceCents);

                $ptr = PrivateTourRequest::create([
                    'contact_first_name' => $data['guest_first_name'],
                    'contact_last_name' => $data['guest_last_name'],
                    'contact_email' => $data['guest_email'],
                    'contact_phone' => $data['guest_phone'] ?? '',
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
                    'first_name' => $data['guest_first_name'],
                    'last_name' => $data['guest_last_name'],
                    'email' => $data['guest_email'],
                    'phone' => $data['guest_phone'] ?? '',
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
                    'first_name' => $data['guest_first_name'],
                    'last_name' => $data['guest_last_name'],
                    'email' => $data['guest_email'],
                    'phone' => $data['guest_phone'] ?? '',
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
                ->title('Private Tour Booked!')
                ->body("Private tour booked: **{$result['ptr']->booking_ref}** — " . \Carbon\Carbon::parse($data['confirmed_tour_date'])->format('F j, Y'))
                ->success()
                ->persistent()
                ->send();

            $this->form->fill(['booking_type' => 'regular']);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Booking Failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}

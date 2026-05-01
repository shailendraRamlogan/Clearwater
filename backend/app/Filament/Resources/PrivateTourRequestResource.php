<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivateTourRequestResource\Pages;
use App\Models\PrivateTourRequest;
use App\Services\EmailService;
use App\Services\FeeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrivateTourRequestResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = PrivateTourRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?int $navigationSort = 25;

    public static function getNavigationLabel(): string
    {
        return 'Private Tours';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only: Contact Info
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_ref')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Name')
                            ->formatStateUsing(fn ($record) => $record ? "{$record->contact_first_name} {$record->contact_last_name}" : '')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Phone')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('created_at_display')
                            ->label('Submitted')
                            ->formatStateUsing(fn ($record) => $record ? $record->created_at->format('M j, Y g:i A') : '')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                // Read-only: Guest Counts
                Forms\Components\Section::make('Guest Counts')
                    ->schema([
                        Forms\Components\TextInput::make('adult_count')
                            ->label('Adults')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('child_count')
                            ->label('Children (3–17)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('infant_count')
                            ->label('Infants (≤2, free)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_guests')
                            ->label('Total Paying Guests')
                            ->formatStateUsing(fn ($record) => $record ? $record->totalGuests() . ' / 10 max' : '')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                // Read-only: Preferred Dates
                Forms\Components\Section::make('Preferred Dates')
                    ->schema([
                        Forms\Components\Placeholder::make('preferred_dates_display')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record) return '—';
                                $dates = $record->preferredDates;
                                if ($dates->isEmpty()) return '—';

                                $items = $dates->map(function ($d) {
                                    $formatted = \Illuminate\Support\Carbon::parse($d->date)->format('F j, Y');
                                    $pref = ucfirst($d->time_preference);
                                    return "<span style=\"display:inline-block; background:#f0fdfa; color:#0d9488; padding:4px 12px; border-radius:9999px; font-size:13px; margin:2px;\">{$formatted} — {$pref}</span>";
                                })->join(' ');

                                return new \Illuminate\Support\HtmlString($items);
                            }),
                    ]),

                // Read-only: Occasion
                Forms\Components\Section::make('Special Occasion')
                    ->schema([
                        Forms\Components\Toggle::make('has_occasion')
                            ->label('Special Occasion')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('occasion_details')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2)
                            ->visible(fn ($record) => $record?->has_occasion),
                    ])
                    ->collapsed(fn ($record) => !$record?->has_occasion),

                // Admin Actions
                Forms\Components\Section::make('Tour Details')
                    ->schema([
                        Forms\Components\DatePicker::make('confirmed_tour_date')
                            ->label('Confirmed Tour Date')
                            ->closeOnDateSelection()
                            ->minDate(today())
                            ->disabled(fn ($record) => !in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED]))
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('confirmed_start_time')
                                    ->label('Start Time')
                                    ->type('time')
                                    ->disabled(fn ($record) => !in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED]))
                                    ->required()
                                    ->afterStateHydrated(function ($component, $state) {
                                        if ($state) {
                                            $val = $state instanceof \DateTimeInterface
                                                ? $state->format('H:i')
                                                : substr((string) $state, 0, 5);
                                            $component->state($val);
                                        }
                                    })
                                    ->dehydrateStateUsing(fn ($state) => $state ? substr($state, 0, 5) . ':00' : null),
                                Forms\Components\TextInput::make('confirmed_end_time')
                                    ->label('End Time')
                                    ->type('time')
                                    ->disabled(fn ($record) => !in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED]))
                                    ->required()
                                    ->afterStateHydrated(function ($component, $state) {
                                        if ($state) {
                                            $val = $state instanceof \DateTimeInterface
                                                ? $state->format('H:i')
                                                : substr((string) $state, 0, 5);
                                            $component->state($val);
                                        }
                                    })
                                    ->dehydrateStateUsing(fn ($state) => $state ? $state . ':00' : null),
                            ]),
                        Forms\Components\TextInput::make('total_price_cents')
                            ->label('Total Price ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->disabled(fn ($record) => !in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED]))
                            ->required()
                            ->helperText('Set the flat total price for this private tour (includes addons). Fees will be calculated automatically.')
                            ->formatStateUsing(fn ($record) => $record?->total_price_cents ? $record->total_price_cents / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round((float) $state * 100) : null),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(1)
                            ->maxLength(1000),
                    ])
                    ->columns(1),

                // Selected Addons
                Forms\Components\Section::make('Add-ons')
                    ->schema([
                        Forms\Components\CheckboxList::make('selected_addon_ids')
                            ->label('Toggle add-ons on/off for this booking')
                            ->options(function ($record) {
                                return \App\Models\Addon::active()
                                    ->forPrivateTours()
                                    ->orderBy('sort_order')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->formatStateUsing(function ($record) {
                                if (!$record) return [];
                                return $record->addons->pluck('addon_id')->toArray();
                            })
                            ->columns(1)
                            ->bulkToggleable()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function () {
                                // Trigger reactive updates
                            }),
                        Forms\Components\Placeholder::make('addon_note')
                            ->label('')
                            ->content('Prices for each add-on are set when sending the quote.'),
                    ])
                    ->visible(fn ($record) => $record && in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED])),

                // Guest Information
                Forms\Components\Section::make('Guest Information')
                ->schema([
                    Forms\Components\Placeholder::make('guest_counter')
                        ->label('')
                        ->content(function ($get, $livewire, $record) {
                            if (!$record) return null;
                            $total = $record->adult_count + $record->child_count + $record->infant_count;
                            $guests = $get('guests') ?? [];
                            $completed = collect($guests)->filter(fn ($g) => !empty($g['first_name']) && !empty($g['last_name']))->count();
                            $color = $completed >= $total ? '#065f46'
                                : ($completed === 0 ? '#991b1b' : '#92400e');
                            $bg = $completed >= $total ? '#d1fae5'
                                : ($completed === 0 ? '#fee2e2' : '#fef3c7');
                            return new \Illuminate\Support\HtmlString(
                                "<span style=\"display:inline-block; padding:4px 12px; border-radius:9999px; font-size:13px; font-weight:600; background:{$bg}; color:{$color};\">{$completed} / {$total} guests completed</span>"
                            );
                        })
                        ->reactive(),
                    Forms\Components\Repeater::make('guests')
                        ->label('')
                        ->relationship('guests')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')
                                        ->required()
                                        ->maxLength(100),
                                    Forms\Components\TextInput::make('last_name')
                                        ->required()
                                        ->maxLength(100),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('phone')
                                        ->maxLength(30)
                                        ->label('Phone'),
                                ]),
                            Forms\Components\Checkbox::make('is_primary')
                                ->label('Primary Contact')
                                ->default(false),
                        ])
                        ->addActionLabel('Add Guest')
                        ->disabled(fn ($record) => !in_array($record?->status, [PrivateTourRequest::STATUS_REQUESTED]))
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),

                // Status display
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Placeholder::make('status_display')
                            ->label('Current Status')
                            ->content(fn ($record) => $record
                                ? new \Illuminate\Support\HtmlString(
                                    '<span style="display:inline-block; padding:4px 12px; border-radius:9999px; font-size:13px; font-weight:600; background:' .
                                    match ($record->status) {
                                        'requested' => '#fef3c7; color:#92400e',
                                        'confirmed' => '#d1fae5; color:#065f46',
                                        'rejected' => '#fee2e2; color:#991b1b',
                                        'awaiting_payment' => '#dbeafe; color:#1e40af',
                                        'completed' => '#e0e7ff; color:#3730a3',
                                        default => '#f3f4f6; color:#374151',
                                    } . ';">' . ucfirst(str_replace('_', ' ', $record->status)) . '</span>'
                                )
                                : '—'
                            ),
                        Forms\Components\Placeholder::make('payment_url')
                            ->label('Payment Link')
                            ->visible(fn ($record) => $record?->status === PrivateTourRequest::STATUS_CONFIRMED)
                            ->content(fn ($record) => $record
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . e($record->payment_url) . '" target="_blank" style="color:#0d9488; font-weight:600;">' . e($record->payment_url) . '</a>'
                                )
                                : '—'
                            ),
                        Forms\Components\Placeholder::make('booking_link')
                            ->label('Converted Booking')
                            ->visible(fn ($record) => $record?->status === PrivateTourRequest::STATUS_COMPLETED && $record?->booking)
                            ->content(fn ($record) => $record?->booking
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . route('filament.admin.resources.bookings.edit', $record->booking) . '" style="color:#0d9488; font-weight:600;">View Booking ' . e($record->booking->booking_ref) . '</a>'
                                )
                                : '—'
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['preferredDates', 'guests', 'addons.addon']))
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->state(fn ($record) => $record->contact_first_name . ' ' . $record->contact_last_name)
                    ->description(fn ($record) => trim(
                        $record->contact_email . ($record->contact_phone ? ' · ' . $record->contact_phone : '')
                    ))
                    ->searchable(query: fn ($query, $search) => $query->where('contact_first_name', 'like', "%{$search}%")->orWhere('contact_last_name', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_guests')
                    ->label('Guests')
                    ->state(fn ($record) => ($record->guests->filter(fn ($g) => !empty($g->first_name) && !empty($g->last_name))->count()) . ' / ' . ($record->adult_count + $record->child_count + $record->infant_count))
                    ->badge()
                    ->color(function ($record) {
                        $total = $record->adult_count + $record->child_count + $record->infant_count;
                        $completed = $record->guests->filter(fn ($g) => !empty($g->first_name) && !empty($g->last_name))->count();
                        if ($completed >= $total) return 'success';
                        if ($completed === 0) return 'danger';
                        return 'warning';
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'requested' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        'awaiting_payment' => 'info',
                        'completed' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('preferred_dates_summary')
                    ->label('Preferred Dates')
                    ->formatStateUsing(function ($record) {
                        $dates = $record->preferredDates;
                        if ($dates->isEmpty()) return '—';
                        return $dates->map(fn ($d) => \Illuminate\Support\Carbon::parse($d->date)->format('M j') . ' (' . ucfirst($d->time_preference) . ')')->join(', ');
                    })
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn ($record) => $record->total_price_cents > 0 ? '$' . number_format($record->total_price_cents / 100, 2) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                        'awaiting_payment' => 'Awaiting Payment',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, [PrivateTourRequest::STATUS_REQUESTED])),
                Tables\Actions\Action::make('confirm')
                    ->label('Send Quote')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === PrivateTourRequest::STATUS_REQUESTED)
                    ->disabled(function ($record) {
                        $total = $record->adult_count + $record->child_count + $record->infant_count;
                        $completed = $record->guests->filter(fn ($g) => !empty($g->first_name) && !empty($g->last_name))->count();
                        $tourReady = !empty($record->confirmed_tour_date) && !empty($record->confirmed_start_time) && !empty($record->confirmed_end_time) && !empty($record->total_price_cents);
                        return $completed < $total || !$tourReady;
                    })
                    ->form([
                        Forms\Components\DatePicker::make('confirmed_tour_date')
                            ->label('Confirmed Tour Date')
                            ->closeOnDateSelection()
                            ->minDate(today())
                            ->required()
                            ->default(fn ($record) => $record?->confirmed_tour_date),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('confirmed_start_time')
                                    ->label('Start Time')
                                    ->type('time')
                                    ->required()
                                    ->default(fn ($record) => $record?->confirmed_start_time ? substr($record->confirmed_start_time, 0, 5) : null),
                                Forms\Components\TextInput::make('confirmed_end_time')
                                    ->label('End Time')
                                    ->type('time')
                                    ->required()
                                    ->default(fn ($record) => $record?->confirmed_end_time ? substr($record->confirmed_end_time, 0, 5) : null),
                            ]),
                        Forms\Components\TextInput::make('total_price_dollars')
                            ->label('Total Price ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->required()
                            ->default(fn ($record) => $record?->total_price_cents ? $record->total_price_cents / 100 : null)
                            ->helperText('Flat total price including all add-ons. Fees calculated automatically.'),
                        Forms\Components\Repeater::make('addon_prices')
                            ->label('Add-on Prices (for itemized display)')
                            ->visible(fn ($record) => $record && $record->addons->isNotEmpty())
                            ->default(function ($record) {
                                if (!$record) return [];
                                return $record->addons->map(fn ($pta) => [
                                    'addon_id' => $pta->addon_id,
                                    'addon_title' => $pta->addon->title ?? 'Unknown',
                                    'unit_price_dollars' => $pta->unit_price_cents !== null
                                        ? $pta->unit_price_cents / 100
                                                : null,
                                ])->toArray();
                            })
                            ->schema([
                                Forms\Components\Hidden::make('addon_id'),
                                Forms\Components\TextInput::make('addon_title')
                                    ->label('Add-on')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('unit_price_dollars')
                                    ->label('Price ($)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->nullable()
                                    ->helperText('Price shown to customer for itemized breakdown.'),
                            ])
                            ->dehydrateStateUsing(function ($state) {
                                // Convert to keyed array: addon_id => price_cents
                                $prices = [];
                                foreach ($state as $item) {
                                    $prices[$item['addon_id']] = isset($item['unit_price_dollars'])
                                        ? (int) round((float) $item['unit_price_dollars'] * 100)
                                        : null;
                                }
                                return $prices;
                            })
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->addActionLabel(false)
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(1)
                            ->maxLength(1000),
                        Forms\Components\Repeater::make('guests')
                            ->label('Guest Information')
                            ->default(function ($record) {
                                if (!$record) return [];
                                if ($record->guests->isEmpty()) {
                                    return [[
                                        'first_name' => $record->contact_first_name,
                                        'last_name' => $record->contact_last_name,
                                        'email' => $record->contact_email,
                                        'phone' => $record->contact_phone,
                                        'is_primary' => true,
                                    ]];
                                }
                                return $record->guests->map(fn ($g) => [
                                    'first_name' => $g->first_name,
                                    'last_name' => $g->last_name,
                                    'email' => $g->email,
                                    'phone' => $g->phone,
                                    'is_primary' => $g->is_primary,
                                ])->toArray();
                            })
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')->required()->maxLength(100),
                                        Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')->email()->maxLength(255),
                                        Forms\Components\TextInput::make('phone')->maxLength(30)->label('Phone'),
                                    ]),
                                Forms\Components\Checkbox::make('is_primary')
                                    ->label('Primary Contact')
                                    ->default(false),
                            ])
                            ->columnSpanFull()
                            ->addActionLabel('Add Guest')
                            ->required()
                            ->minItems(1),
                    ])
                    ->action(function (PrivateTourRequest $record, array $data): void {
                        $totalPriceCents = (int) round(($data['total_price_dollars'] ?? 0) * 100);
                        $feeService = app(FeeService::class);
                        $feeResult = $feeService->calculateFees($totalPriceCents);

                        // Build addon_prices keyed array for the confirm endpoint
                        $addonPrices = $data['addon_prices'] ?? [];

                        $record->update([
                            'status' => PrivateTourRequest::STATUS_CONFIRMED,
                            'confirmed_tour_date' => $data['confirmed_tour_date'],
                            'confirmed_start_time' => ($data['confirmed_start_time'] ?? null) ? $data['confirmed_start_time'] . ':00' : null,
                            'confirmed_end_time' => ($data['confirmed_end_time'] ?? null) ? $data['confirmed_end_time'] . ':00' : null,
                            'total_price_cents' => $totalPriceCents,
                            'fees_cents' => $feeResult['total_fees_cents'],
                            'admin_notes' => $data['admin_notes'] ?? null,
                        ]);

                        $record->guests()->delete();
                        foreach ($data['guests'] as $guest) {
                            $record->guests()->create([
                                'first_name' => $guest['first_name'],
                                'last_name' => $guest['last_name'],
                                'email' => $guest['email'] ?? null,
                                'phone' => ($guest['is_primary'] ?? false) ? ($guest['phone'] ?? null) : null,
                                'is_primary' => $guest['is_primary'] ?? false,
                            ]);
                        }

                        // Save addon prices
                        foreach ($addonPrices as $addonId => $priceCents) {
                            $record->addons()->where('addon_id', $addonId)->update([
                                'unit_price_cents' => $priceCents,
                            ]);
                        }

                        try {
                            app(EmailService::class)->sendPrivateTourConfirmed($record->fresh()->load(['guests', 'addons.addon']));
                        } catch (\Exception $e) {
                            \Log::warning("Private tour confirm email error: " . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Quote sent!')
                            ->success()
                            ->body("Private tour {$record->booking_ref} confirmed. Email sent to customer.")
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === PrivateTourRequest::STATUS_REQUESTED)
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(1000)
                            ->helperText('This will be included in the rejection email to the customer.'),
                    ])
                    ->action(function (PrivateTourRequest $record, array $data): void {
                        $record->update([
                            'status' => PrivateTourRequest::STATUS_REJECTED,
                            'admin_notes' => $data['admin_notes'],
                        ]);

                        try {
                            app(EmailService::class)->sendPrivateTourRejected($record);
                        } catch (\Exception $e) {
                            \Log::warning("Private tour reject email error: " . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Request rejected')
                            ->warning()
                            ->body("Private tour {$record->booking_ref} has been rejected.")
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrivateTourRequests::route('/'),
            'edit' => Pages\EditPrivateTourRequest::route('/{record}/edit'),
        ];
    }
}

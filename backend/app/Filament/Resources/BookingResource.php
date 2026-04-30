<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
        public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_ref')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('tour_date_display')
                            ->label('Tour Date')
                            ->formatStateUsing(fn ($record) => $record?->tour_date?->format('F j, Y'))
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('time_slot_display')
                            ->label('Time Slot')
                            ->formatStateUsing(fn ($record) => $record && $record->timeSlot
                                ? $record->timeSlot->boat?->name . ' — ' . \Carbon\Carbon::createFromFormat('H:i:s', $record->timeSlot->start_time)->format('g:i A')
                                : '—')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Order Summary')
                    ->schema([
                        Forms\Components\TextInput::make('guests_expected')
                            ->label('Total Guests')
                            ->formatStateUsing(fn ($record) => $record ? $record->items->sum('quantity') : 0)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('guests_collected')
                            ->label('Guests Collected')
                            ->formatStateUsing(fn ($record) => $record ? $record->guests()->count() : 0)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_price_display')
                            ->label('Ticket Total')
                            ->formatStateUsing(fn ($record) => $record ? '$' . number_format($record->total_price_cents / 100, 2) : '$0.00')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('fees_display')
                            ->label('Fees')
                            ->formatStateUsing(function ($record) {
                                if (!$record || !$record->fees_cents) return '$0.00';
                                return '$' . number_format($record->fees_cents / 100, 2);
                            })
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('grand_total_display')
                            ->label('Grand Total')
                            ->formatStateUsing(function ($record) {
                                if (!$record) return '$0.00';
                                $grand = ($record->total_price_cents ?? 0) + ($record->fees_cents ?? 0);
                                return '$' . number_format($grand / 100, 2);
                            })
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('photo_upgrade_count')
                            ->label('Photo Upgrades')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Special Requests')
                    ->schema([
                        Forms\Components\TextInput::make('special_occasion')
                            ->label('Occasion')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('special_comment')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['guests', 'items']))
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')->searchable(),
                Tables\Columns\TextColumn::make('tour_date')->date(),
                Tables\Columns\TextColumn::make('timeSlot.boat.name')->label('Boat'),
                Tables\Columns\TextColumn::make('timeSlot.start_time')->label('Time'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('complete_guests_count')
                    ->label('Guests')
                    ->badge()
                    ->color(function ($record) {
                        $total = $record->guests_count;
                        $complete = $record->complete_guests_count;
                        return $complete >= $total ? 'success' : ($complete === 1 ? 'warning' : 'danger');
                    })
                    ->formatStateUsing(fn ($record) => $record->complete_guests_count . ' / ' . $record->guests_count),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->formatStateUsing(fn ($record) => '$' . number_format((($record->total_price_cents ?? 0) + ($record->fees_cents ?? 0)) / 100, 2)),
                Tables\Columns\TextColumn::make('fees_cents')
                    ->label('Fees')
                    ->formatStateUsing(fn ($record) => '$' . number_format(($record->fees_cents ?? 0) / 100, 2)),
                Tables\Columns\TextColumn::make('total_price_cents')
                    ->label('Payout')
                    ->formatStateUsing(fn ($record) => '$' . number_format(($record->total_price_cents ?? 0) / 100, 2)),
            ])
            ->filters([
                Tables\Filters\Filter::make("tour_date_range")
                    ->label("Tour Date")
                    ->form([
                        \Filament\Forms\Components\DatePicker::make("from")->label("From")->closeOnDateSelection(),
                        \Filament\Forms\Components\DatePicker::make("until")->label("Until")->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when($data["from"], fn ($q, $v) => $q->whereDate("tour_date", ">=", $v))
                            ->when($data["until"], fn ($q, $v) => $q->whereDate("tour_date", "<=", $v));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Invoice — ' . $record->booking_ref)
                    ->modalContent(fn ($record) => view('filament.modals.booking-invoice', ['booking' => $record->load(['guests', 'items', 'timeSlot.boat'])]))
                    ->modalSubmitAction(fn ($record) => \Filament\Actions\Action::make('download')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(route('invoices.download', $record))
                        ->openUrlInNewTab()
                    )
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentBookingResource\Pages;
use App\Models\Booking;
use App\Models\BookingAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Agent Bookings';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 15;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdminOrSuper() || $user->isAgent());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('source_type', 'agent')
            ->with(['bookingAgent', 'timeSlot.boat']);

        $user = auth()->user();
        if ($user && $user->isAgent() && $user->booking_agent_id) {
            $query->where('booking_agent_id', $user->booking_agent_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\Select::make('booking_agent_id')
                            ->label('Booking Agent')
                            ->relationship('bookingAgent', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('booking_ref')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->options([
                                'confirmed' => 'Confirmed',
                                'pending' => 'Pending',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('tour_date')
                            ->required(),
                        Forms\Components\TextInput::make('sales_rep_name')
                            ->label('Sales Rep'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Financials')
                    ->schema([
                        Forms\Components\TextInput::make('total_price_cents')
                            ->label('Total Price')
                            ->numeric()
                            ->prefix('$')
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state / 100),
                        Forms\Components\TextInput::make('commission_percent')
                            ->label('Commission %')
                            ->numeric()
                            ->suffix('%')
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('commission_cents')
                            ->label('Commission Amount')
                            ->numeric()
                            ->prefix('$')
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state / 100),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Guest Information')
                    ->schema([
                        Forms\Components\TextInput::make('primary_guest_name')
                            ->label('Primary Guest')
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $guest = $record->guests->where('is_primary', true)->first();
                                return $guest ? trim("{$guest->first_name} {$guest->last_name}") : null;
                            }),
                        Forms\Components\TextInput::make('primary_guest_email')
                            ->label('Email')
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $guest = $record->guests->where('is_primary', true)->first();
                                return $guest?->email;
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),
                Tables\Columns\TextColumn::make('bookingAgent.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('sales_rep_name')
                    ->label('Sales Rep')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('tour_date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('timeSlot.start_time')
                    ->label('Time')
                    ->formatStateUsing(function ($record) {
                        if (!$record->timeSlot) return '—';
                        $start = \Carbon\Carbon::createFromFormat('H:i:s', $record->timeSlot->start_time)->format('g:i A');
                        $end = \Carbon\Carbon::createFromFormat('H:i:s', $record->timeSlot->end_time)->format('g:i A');
                        return "$start – $end";
                    })
                    ->default('—'),
                Tables\Columns\TextColumn::make('total_price_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2))
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('commission_percent')
                    ->label('Comm %')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_cents')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2))
                    ->sortable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('net_total')
                    ->label('Net')
                    ->state(fn ($record) => ($record->total_price_cents - $record->commission_cents) / 100)
                    ->money('USD')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(total_price_cents - commission_cents) ' . $direction);
                    })
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('booking_agent_id')
                    ->label('Agent')
                    ->relationship('bookingAgent', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('tour_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('tour_date', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('tour_date', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_confirmed')
                        ->label('Mark Confirmed')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'confirmed']);
                            Notification::make()->title(count($records) . ' bookings confirmed')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_cancelled')
                        ->label('Mark Cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'cancelled']);
                            Notification::make()->title(count($records) . ' bookings cancelled')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('tour_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentBookings::route('/'),
            'view' => Pages\ViewAgentBooking::route('/{record}'),
            'edit' => Pages\EditAgentBooking::route('/{record}/edit'),
        ];
    }
}

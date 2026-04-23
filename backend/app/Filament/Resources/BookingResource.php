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
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_ref')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DatePicker::make('tour_date')
                    ->required(),
                Forms\Components\Select::make('time_slot_id')
                    ->relationship('timeSlot', 'id')
                    ->required()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->boat->name . ' ' . $record->start_time),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
                Forms\Components\TextInput::make('total_guests')
                    ->numeric(),
                Forms\Components\TextInput::make('total_price_cents')
                    ->numeric()
                    ->label('Total Price (cents)'),
                Forms\Components\TextInput::make('photo_upgrade_count')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('special_occasion')
                    ->maxLength(255),
                Forms\Components\Textarea::make('special_comment')
                    ->maxLength(65535),
                Forms\Components\Toggle::make('is_confirmed')
                    ->label('Confirmed'),
                Forms\Components\Toggle::make('needs_confirmation')
                    ->label('Needs Confirmation'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')->searchable(),
                Tables\Columns\TextColumn::make('tour_date')->date(),
                Tables\Columns\TextColumn::make('timeSlot.boat.name')->label('Boat'),
                Tables\Columns\TextColumn::make('timeSlot.start_time')->label('Time'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn($state) => match($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    'completed' => 'info',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('total_guests')->numeric(),
                Tables\Columns\TextColumn::make('total_price_cents')->money('usd', divideBy: 100)->label('Total'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\Filter::make('tour_date')->form([
                    Forms\Components\DatePicker::make('from'),
                    Forms\Components\DatePicker::make('until'),
                ])->query(fn($query, $data) => $query->when($data['from'], fn($q) => $q->whereDate('tour_date', '>=', $data['from']))
                    ->when($data['until'], fn($q) => $q->whereDate('tour_date', '<=', $data['until']))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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

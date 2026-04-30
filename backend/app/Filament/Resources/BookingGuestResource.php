<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingGuestResource\Pages;
use App\Models\BookingGuest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingGuestResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = BookingGuest::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('booking_id')
                                    ->relationship('booking', 'booking_ref')
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Placeholder::make('primary_badge')
                                    ->label('')
                                    ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;height:100%;">' .
                                        '<span style="font-size:14px;font-weight:500;color:#6b7280;">Primary Guest</span>' .
                                        '<span style="position:relative;display:inline-block;width:36px;height:20px;border-radius:9999px;background:' . ($record?->is_primary ? '#0d9488' : '#d1d5db') . ';">' .
                                        '<span style="position:absolute;top:2px;' . ($record?->is_primary ? 'left:18px' : 'left:2px') . ';width:16px;height:16px;border-radius:9999px;background:white;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>' .
                                        '</span>' .
                                        '</div>'
                                    )),
                                Forms\Components\TextInput::make('first_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('last_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('email')->email()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->maxLength(255),
                            ]),
                    ])->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Deduplicate guests by email, showing most recent record per email
                // with total bookings count
                return BookingGuest::query()
                    ->selectRaw('DISTINCT ON (email) *')
                    ->orderBy('email')
                    ->orderByDesc('created_at');
            })
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => \App\Models\BookingGuest::where('email', $record->email)->count()),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_primary')->label('Primary Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
            ->defaultSort('email');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingGuests::route('/'),
            'edit' => Pages\EditBookingGuest::route('/{record}/edit'),
        ];
    }
}

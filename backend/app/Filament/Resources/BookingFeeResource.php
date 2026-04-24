<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingFeeResource\Pages;
use App\Models\BookingFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingFeeResource extends Resource
{
    protected static ?string $model = BookingFee::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'flat' => 'Flat ($)',
                        'percent' => 'Percent (%)',
                        'both' => 'Combined (%) + $)',
                    ])
                    ->live(),
                Forms\Components\TextInput::make('value')
                    ->label(fn ($get) => $get('type') === 'flat' ? 'Amount ($)' : 'Percentage (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->visible(fn ($get) => $get('type') !== 'flat'),
                Forms\Components\TextInput::make('flat_value')
                    ->label('Flat Amount ($)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->default(0)
                    ->visible(fn ($get) => $get('type') === 'flat' || $get('type') === 'both'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'percent' => 'blue',
                        'both' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'percent' => 'Percent',
                        'both' => 'Combined',
                        default => 'Flat',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Fee')
                    ->formatStateUsing(fn ($record) => $record->displayValue()),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingFees::route('/'),
            'create' => Pages\CreateBookingFee::route('/create'),
            'edit' => Pages\EditBookingFee::route('/{record}/edit'),
        ];
    }
}

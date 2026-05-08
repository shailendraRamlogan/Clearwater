<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingAgentResource\Pages;
use App\Models\BookingAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingAgentResource extends Resource
{
    protected static ?string $model = BookingAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Booking Agents';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdminOrSuper());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Agent / Company Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->label('Phone Number')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Commission')
                    ->schema([
                        Forms\Components\TextInput::make('commission_percent')
                            ->label('Commission (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->helperText('Commission percentage earned on bookings from this agent'),
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive agents will not appear in the booking agent dropdown'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Agent / Company')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->color('gray')
                    ->default('—'),
                Tables\Columns\TextColumn::make('commission_percent')
                    ->label('Commission')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable()
                    ->color('success')
                    ->weight('medium'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingAgents::route('/'),
            'create' => Pages\CreateBookingAgent::route('/create'),
            'edit' => Pages\EditBookingAgent::route('/{record}/edit'),
        ];
    }
}

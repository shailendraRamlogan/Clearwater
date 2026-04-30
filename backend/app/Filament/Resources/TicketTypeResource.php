<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketTypeResource\Pages;
use App\Models\TicketType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;

class TicketTypeResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = TicketType::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('price_cents')
                                    ->label('Price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->live()
                                    ->dehydrateStateUsing(fn ($state) => (int) round(($state ?? 0) * 100))
                                    ->formatStateUsing(fn ($state) => $state ? round($state / 100, 2) : null),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Features')
                    ->description('What\'s included with this ticket type')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->relationship('features')
                            ->schema([
                                Forms\Components\TextInput::make('icon')
                                    ->label('Icon')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Lucide icon name (e.g. Camera, Beer, Grape)'),
                                Forms\Components\TextInput::make('label')
                                    ->label('Feature')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->collapsible()
                            ->addActionLabel('Add feature')
                            ->reorderableWithButtons()
                            ->defaultItems(0)
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Only')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketTypes::route('/'),
            'create' => Pages\CreateTicketType::route('/create'),
            'edit' => Pages\EditTicketType::route('/{record}/edit'),
        ];
    }
}

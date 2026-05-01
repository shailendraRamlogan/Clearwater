<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddonResource\Pages;
use App\Models\Addon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AddonResource extends Resource
{
        public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = Addon::class;
    protected static ?string $navigationIcon = "heroicon-o-sparkles";
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make("title")->required()->maxLength(255),
                Forms\Components\TextInput::make("price_cents")
                    ->label("Regular Price (cents)")
                    ->numeric()
                    ->required()
                    ->formatStateUsing(fn ($state) => $state ?? 0),
                Forms\Components\TextInput::make("private_price_cents")
                    ->label("Private Tour Price (cents)")
                    ->numeric()
                    ->nullable()
                    ->helperText("Price shown to customers on private tour payment page. Admin still sets the grand total.")
                    ->formatStateUsing(fn ($state) => $state ?? null),
                Forms\Components\Select::make("available_for")
                    ->label("Available For")
                    ->options([
                        'regular' => 'Regular Tours Only',
                        'private' => 'Private Tours Only',
                        'both' => 'Both Regular & Private',
                    ])
                    ->default('regular')
                    ->required(),
                Forms\Components\TextInput::make("icon_name")->label("Icon")->maxLength(100),
                Forms\Components\TextInput::make("sort_order")->numeric()->default(0),
                Forms\Components\TextInput::make("max_quantity")->numeric()->default(1),
                Forms\Components\Textarea::make("description")
                    ->rows(3),
                Forms\Components\Toggle::make("is_active")->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("title")->searchable()->sortable(),
                Tables\Columns\TextColumn::make("description")->limit(50)->wrap(),
                Tables\Columns\TextColumn::make("price_cents")
                    ->label("Regular Price")
                    ->formatStateUsing(fn ($state) => "$" . number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make("private_price_cents")
                    ->label("Private Price")
                    ->formatStateUsing(fn ($state) => $state !== null ? "$" . number_format($state / 100, 2) : "—")
                    ->sortable(),
                Tables\Columns\BadgeColumn::make("available_for")
                    ->label("Available For")
                    ->colors([
                        'primary' => 'regular',
                        'warning' => 'private',
                        'success' => 'both',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'regular' => 'Regular',
                        'private' => 'Private',
                        'both' => 'Both',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make("icon_name")->label("Icon"),
                Tables\Columns\ToggleColumn::make("is_active")->label("Active"),
                Tables\Columns\TextColumn::make("sort_order")->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("sort_order", "asc");
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListAddons::route("/"),
            "create" => Pages\CreateAddon::route("/create"),
            "edit" => Pages\EditAddon::route("/{record}/edit"),
        ];
    }
}

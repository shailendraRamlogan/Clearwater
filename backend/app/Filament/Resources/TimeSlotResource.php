<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeSlotResource\Pages;
use App\Models\TimeSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimeSlotResource extends Resource
{
        public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = TimeSlot::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Availability')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_from')
                            ->label('Start Date')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('effective_until')
                            ->label('End Date')
                            ->required()
                            ->afterOrEqual('effective_from')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->compact(),

                Forms\Components\Section::make('Vessel')
                    ->schema([
                        Forms\Components\Select::make('boat_id')
                            ->relationship('boat', 'name')
                            ->required()
                            ->searchable()
                            ->label('Boat')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('max_capacity')
                            ->required()
                            ->numeric()
                            ->label('Max Capacity')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->compact(),

                Forms\Components\Section::make('Sailing Time')
                    ->schema([
                        Forms\Components\Select::make('day')
                            ->label('Day')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('start_time')
                            ->label('Start Time')
                            ->type('time')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('end_time')
                            ->label('End Time')
                            ->type('time')
                            ->required()
                            ->after('start_time')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('boat.name')->searchable()->label('Boat'),
                Tables\Columns\TextColumn::make('start_label')->label('Start'),
                Tables\Columns\TextColumn::make('end_label')->label('End'),
                Tables\Columns\TextColumn::make('effective_from')->date()->label('Available From'),
                Tables\Columns\TextColumn::make('effective_until')->date()->label('Available Until'),
                Tables\Columns\TextColumn::make('max_capacity')->numeric()->label('Capacity'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('boat_id')
                    ->relationship('boat', 'name'),
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
            'index' => Pages\ListTimeSlots::route('/'),
            'create' => Pages\CreateTimeSlot::route('/create'),
            'edit' => Pages\EditTimeSlot::route('/{record}/edit'),
        ];
    }
}

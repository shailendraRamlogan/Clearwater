<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailLogResource\Pages;
use App\Models\EmailLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailLogResource extends Resource
{
        public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    protected static ?string $model = EmailLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('booking_id')
                    ->relationship('booking', 'booking_ref')
                    ->searchable(),
                Forms\Components\TextInput::make('recipient')->email()->required()->maxLength(255),
                Forms\Components\TextInput::make('subject')->required()->maxLength(255),
                Forms\Components\TextInput::make('template')->maxLength(255),
                Forms\Components\TextInput::make('resend_id')->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                    ]),
                Forms\Components\DateTimePicker::make('sent_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient')->searchable(),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('booking.booking_ref'),
                Tables\Columns\TextColumn::make('template'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn($state) => match($state) {
                    'sent' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }),
                Tables\Columns\TextColumn::make('sent_at')->dateTime(),
            ])
            ->filters([
                Tables\Filters\Filter::make("created_at_range")
                    ->label("Created Date")
                    ->form([
                        \Filament\Forms\Components\DatePicker::make("from")->label("From")->closeOnDateSelection(),
                        \Filament\Forms\Components\DatePicker::make("until")->label("Until")->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when($data["from"], fn ($q, $v) => $q->whereDate("created_at", ">=", $v))
                            ->when($data["until"], fn ($q, $v) => $q->whereDate("created_at", "<=", $v));
                    }),
                                Tables\Filters\SelectFilter::make('status')
                    ->options(['sent' => 'Sent', 'failed' => 'Failed', 'pending' => 'Pending']),
                Tables\Filters\SelectFilter::make('template'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sent_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailLogs::route('/'),
            'create' => Pages\CreateEmailLog::route('/create'),
            'edit' => Pages\EditEmailLog::route('/{record}/edit'),
        ];
    }
}

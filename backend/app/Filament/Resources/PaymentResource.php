<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 50;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.primaryGuest.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->booking->primaryGuest
                        ? $record->booking->primaryGuest->first_name . ' ' . $record->booking->primaryGuest->last_name
                        : '—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking.booking_ref')
                    ->searchable()
                    ->label('Booking'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->money('usd', divideBy: 100)
                    ->label('Amount'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'succeeded' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Payment — ' . $record->booking->booking_ref)
                    ->modalContent(fn ($record) => view('filament.modals.payment-detail', ['payment' => $record->load('booking')]))
                    ->modalCancelActionLabel('Close')
                    ->modalSubmitAction(false),
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
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}

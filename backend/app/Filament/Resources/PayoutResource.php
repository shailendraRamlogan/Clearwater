<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 55;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'super_admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount_dollars')
                    ->label('Amount ($)')
                    ->numeric()
                    ->required()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreatePayout),
                Forms\Components\TextInput::make('transfer_name')
                    ->label('Transfer Name')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreatePayout),
                Forms\Components\TextInput::make('initiated_by_name')
                    ->label('Initiated By')
                    ->disabled()
                    ->default(fn () => auth()->user()?->name)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreatePayout),
                Forms\Components\FileUpload::make('receipt_image')
                    ->label('Transfer Receipt Image')
                    ->image()
                    ->maxSize(5120)
                    ->directory('payout-receipts')
                    ->visibility('public')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreatePayout),
                Forms\Components\Hidden::make('amount_cents'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('initiator', 'confirmer', 'rejecter'))
            ->columns([
                Tables\Columns\TextColumn::make('transfer_name')
                    ->label('Transfer Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Initiated By'),
                Tables\Columns\TextColumn::make('confirmer.name')
                    ->label('Confirmed By'),
                Tables\Columns\TextColumn::make('rejecter.name')
                    ->label('Rejected By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created'),
                Tables\Columns\ImageColumn::make('receipt_image')
                    ->label('Receipt')
                    ->disk('public')
                    ->square()
                    ->size(60),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('created_at_range')
                    ->label('Created Date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From')->closeOnDateSelection(),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until')->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm Received')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->role === 'admin')
                    ->action(function (Payout $record) {
                        $record->update([
                            'status' => 'confirmed',
                            'confirmed_by' => auth()->id(),
                            'confirmed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (Payout $record) => $record->status !== 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => auth()->user()?->role === 'admin')
                    ->action(function (Payout $record) {
                        $record->update([
                            'status' => 'rejected',
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (Payout $record) => $record->status !== 'pending'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
        ];
    }
}

<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use App\Models\BookingGuest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\StaticAction;

class ListBookingGuests extends ListRecords
{
    protected static string $resource = BookingGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $guests = BookingGuest::query()
                        ->selectRaw('DISTINCT ON (email) *')
                        ->orderBy('email')
                        ->orderByDesc('created_at')
                        ->get();

                    $csv = fopen('php://temp', 'r+');
                    fputcsv($csv, ['First Name', 'Last Name', 'Email', 'Phone', 'Total Bookings']);

                    foreach ($guests as $guest) {
                        fputcsv($csv, [
                            $guest->first_name,
                            $guest->last_name,
                            $guest->email,
                            $guest->phone,
                            BookingGuest::where('email', $guest->email)->count(),
                        ]);
                    }

                    rewind($csv);
                    $csvContent = stream_get_contents($csv);
                    fclose($csv);

                    return response()->streamDownload(function () use ($csvContent) {
                        echo $csvContent;
                    }, 'guests-export-' . now()->format('Y-m-d') . '.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
        ];
    }
}

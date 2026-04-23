<?php

namespace App\Filament\Pages;

use App\Models\BookingGuest;
use App\Models\TimeSlot;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PassengerManifest extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Passenger Manifests';
    protected static ?int $navigationSort = 25;

    protected static string $view = 'filament.pages.passenger-manifest';

    public ?string $filter_date = null;
    public ?string $filter_boat_id = null;
    public ?string $filter_time_slot_id = null;

    // Applied state — source of truth for table queries only
    public ?string $applied_date = null;
    public ?string $applied_boat_id = null;
    public ?string $applied_time_slot_id = null;

    public array $vessels = [];
    public array $timeSlots = [];

    public function mount(): void
    {
        $this->filter_date = now()->format('Y-m-d');
        $this->applied_date = $this->filter_date;
        $this->applied_boat_id = $this->filter_boat_id;
        $this->applied_time_slot_id = $this->filter_time_slot_id;
        $this->refreshOptions();
    }

    public function onDateChanged(string $date): void
    {
        $this->refreshTimeSlots($date, $this->filter_boat_id);
    }

    public function onBoatChanged(?string $boatId): void
    {
        $this->refreshTimeSlots($this->filter_date, $boatId);
    }

    public function applyFilters(): void
    {
        $this->applied_date = $this->filter_date;
        $this->applied_boat_id = $this->filter_boat_id;
        $this->applied_time_slot_id = $this->filter_time_slot_id;
        $this->resetTable();
    }

    private function refreshOptions(): void
    {
        $this->vessels = $this->getVesselOptions();
        $this->timeSlots = $this->getTimeSlotOptionsForDropdown($this->filter_date, $this->filter_boat_id);
    }

    private function refreshTimeSlots(?string $date = null, ?string $boatId = null): void
    {
        $date = $date ?? $this->filter_date;
        $boatId = $boatId ?? $this->filter_boat_id;
        $this->timeSlots = $this->getTimeSlotOptionsForDropdown($date, $boatId);
    }

    public function getTimeSlotAvailability(): bool
    {
        return !empty($this->timeSlots);
    }

    private function getVesselOptions(): array
    {
        if (!$this->filter_date) return [];
        $slots = TimeSlot::where('effective_from', '<=', $this->filter_date)
            ->where('effective_until', '>=', $this->filter_date)
            ->whereRaw('EXISTS (SELECT 1 FROM boats WHERE boats.id = time_slots.boat_id)')
            ->with('boat')
            ->get()
            ->unique('boat_id');
        $result = [];
        foreach ($slots as $slot) {
            if ($slot->boat) {
                $result[$slot->boat->id] = $slot->boat->name;
            }
        }
        return $result;
    }

    private function getTimeSlotOptionsForDropdown(?string $date, ?string $boatId): array
    {
        if (!$date) return [];

        $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l'));

        $query = TimeSlot::where('day', $dayName)
            ->where('effective_from', '<=', $date)
            ->where('effective_until', '>=', $date);

        if ($boatId) {
            $query->where('boat_id', $boatId);
        }

        return $query->with('boat')->get()->mapWithKeys(function ($s) use ($boatId) {
            $label = $s->start_label . ' — ' . $s->end_label;
            if (!$boatId && $s->boat) {
                $label = $s->boat->name . ' — ' . $label;
            }
            return [$s->id => $label];
        })->toArray();
    }

    public function getTableQuery(): Builder
    {
        return BookingGuest::query()
            ->whereHas('booking', function ($q) {
                $q->where('status', 'confirmed');
                if ($this->applied_date) {
                    $q->where('tour_date', $this->applied_date);
                }
                if ($this->applied_time_slot_id) {
                    $q->where('time_slot_id', $this->applied_time_slot_id);
                } elseif ($this->applied_boat_id) {
                    $q->whereRaw('EXISTS (SELECT 1 FROM time_slots WHERE time_slots.id = bookings.time_slot_id AND time_slots.boat_id = ?)', [$this->applied_boat_id]);
                }
            })
            ->with(['booking.items']);
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('booking.booking_ref')
                ->label('Booking Ref')
                ->searchable(),
            TextColumn::make('full_name')
                ->label('Guest Name')
                ->searchable()
                ->formatStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name),
            TextColumn::make('email')
                ->label('Email')
                ->searchable(),
            TextColumn::make('phone')
                ->label('Phone')
                ->searchable(),
            TextColumn::make('booking.items_sum_quantity')
                ->label('Tickets')
                ->formatStateUsing(fn($record) => $record->booking->items->sum('quantity')),
            TextColumn::make('is_primary')
                ->label('Booker')
                ->badge()
                ->formatStateUsing(fn($state) => $state ? 'Primary' : 'Guest')
                ->color(fn($state) => $state ? 'success' : 'gray'),
        ];
    }

    public function getTableActions(): array { return []; }
    public function getTableBulkActions(): array { return []; }
    public function getTableHeaderActions(): array { return []; }

    public static function shouldRegisterNavigation(): bool { return true; }

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }
}

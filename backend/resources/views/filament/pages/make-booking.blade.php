<x-filament-panels::page>
{{ $this->form }}

<div class="mt-4">
    <x-filament::button type="submit" wire:click="createBooking" icon="heroicon-o-check">
        Create Booking
    </x-filament::button>
</div>
</x-filament-panels::page>

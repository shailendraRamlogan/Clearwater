<x-filament-panels::page>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Regular Sailing --}}
    <div class="fi-section">
        <div class="fi-section-header">
            <h3 class="fi-section-header-heading text-lg font-semibold flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" />
                Regular Sailing
            </h3>
        </div>
        <div class="fi-section-content p-6">
            <form wire:submit="createRegularBooking">
                {{ $this->regularForm }}

                <div class="mt-4 flex items-center gap-3">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Create Regular Booking
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>

    {{-- Private Tour --}}
    <div class="fi-section">
        <div class="fi-section-header">
            <h3 class="fi-section-header-heading text-lg font-semibold flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5" />
                Private Tour
            </h3>
        </div>
        <div class="fi-section-content p-6">
            <form wire:submit="createPrivateBooking">
                {{ $this->privateForm }}

                <div class="mt-4 flex items-center gap-3">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Create Private Tour Booking
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>

</div>

</x-filament-panels::page>

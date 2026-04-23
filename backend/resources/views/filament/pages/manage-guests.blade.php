<x-filament-panels::page>
    @if(!$booking)
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">Booking not found.</div>
    @else
        {{-- Read-only booking summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Reference</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $booking->booking_ref }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ ucfirst($booking->status) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($booking->total_price_cents / 100, 2) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Guests</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $booking->guests->count() }} / {{ $booking->items->sum('quantity') }}</p>
            </div>
        </div>

        @if($booking->special_occasion)
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-8">
                <p class="text-xs font-medium text-amber-700 dark:text-amber-400 uppercase tracking-wide">Special Occasion</p>
                <p class="text-sm text-amber-900 dark:text-amber-200 mt-1">{{ $booking->special_occasion }}</p>
                @if($booking->special_comment)
                    <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">{{ $booking->special_comment }}</p>
                @endif
            </div>
        @endif

        {{-- Guest Editor --}}
        <livewire:guest-editor :booking-id="$booking->id" :key="'editor-' . $booking->id" />
    @endif
</x-filament-panels::page>

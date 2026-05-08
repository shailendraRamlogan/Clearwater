<x-filament-panels::page>
    {{ $this->form }}

    @if($error)
        <x-filament::alert type="danger" dismissible>
            {{ $error }}
        </x-filament::alert>
    @endif

    @if($foundBooking)
        <div class="space-y-6 mt-6">
            {{-- Booking Summary --}}
            <div class="fi-section rounded-xl bg-white shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Booking Found</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Reference</span>
                        <p class="font-medium">{{ $foundBooking->booking_ref }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Status</span>
                        <p>
                            @php
                                $statusColor = match($foundBooking->status) {
                                    'confirmed' => 'text-green-600',
                                    'pending' => 'text-yellow-600',
                                    default => 'text-gray-600',
                                }
                            @endphp
                            <span class="font-medium capitalize {{ $statusColor }}">{{ $foundBooking->status }}</span>
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500">Primary Guest</span>
                        <p class="font-medium">{{ $foundBooking->primaryGuest?->first_name }} {{ $foundBooking->primaryGuest?->last_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Guest Email</span>
                        <p class="font-medium">{{ $foundBooking->primaryGuest?->email ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Current Date</span>
                        <p class="font-medium">{{ $foundBooking->tour_date->format('l, F j, Y') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Time</span>
                        <p class="font-medium">
                            @if($foundBooking->timeSlot)
                                {{ \Carbon\Carbon::parse($foundBooking->timeSlot->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($foundBooking->timeSlot->end_time)->format('g:i A') }}
                            @else
                                TBD (Private Tour)
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500">Boat</span>
                        <p class="font-medium">{{ $foundBooking->timeSlot?->boat?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Guests</span>
                        <p class="font-medium">{{ $foundBooking->items->sum('quantity') }} passengers</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Total</span>
                        <p class="font-medium">${{ number_format($foundBooking->total_price_cents / 100, 2) }}</p>
                    </div>
                    @if($foundBooking->booking_agent_id)
                        <div>
                            <span class="text-gray-500">Agent</span>
                            <p class="font-medium">{{ $foundBooking->bookingAgent?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Commission</span>
                            <p class="font-medium">${{ number_format($foundBooking->commission_cents / 100, 2) }} ({{ $foundBooking->commission_percent }}%)</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rebook Form --}}
            <div class="fi-section rounded-xl bg-white shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Rebook Details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Tour Date *</label>
                        <input
                            type="date"
                            wire:model.live="newDate"
                            min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rebooking Fee ($)</label>
                        <input
                            type="number"
                            wire:model.live="feeDollars"
                            min="0"
                            step="0.01"
                            value="0"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">Leave at 0 for free rebooking. Charged directly to the customer.</p>
                    </div>
                </div>

                @if($feeDollars > 0 && (!$foundBooking->primaryGuest || empty($foundBooking->primaryGuest->email)))
                    <x-filament::alert type="danger">
                        Customer email is required to apply a rebooking fee. Set fee to $0 or add an email first.
                    </x-filament::alert>
                @endif

                {{-- Financial Summary --}}
                <div class="mt-4 p-4 bg-gray-50 rounded-lg text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Original booking total</span>
                        <span class="font-medium">${{ number_format($foundBooking->total_price_cents / 100, 2) }}</span>
                    </div>
                    @if($foundBooking->commission_cents > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Agent commission</span>
                            <span class="font-medium">${{ number_format($foundBooking->commission_cents / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-200 pt-1 mt-1">
                        <span class="text-gray-600">Rebooking fee</span>
                        <span class="font-medium">${{ number_format(($feeDollars ?? 0), 2) }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-2">
                    <button
                        wire:click="goBack"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        &larr; Back to Search
                    </button>
                    <button
                        wire:click="submitRebook"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                        onclick="return confirm('Booking {{ $foundBooking->booking_ref }} will be CANCELLED and a new booking will be created.{{ $feeDollars > 0 ? " A rescheduling fee of $" . number_format($feeDollars, 2) . " will be charged to " . ($foundBooking->primaryGuest?->email ?? 'the customer') . "." : "" }} This cannot be undone.')"
                    >
                        <span wire:loading.remove wire:target="submitRebook">Confirm Rebook</span>
                        <span wire:loading wire:target="submitRebook">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

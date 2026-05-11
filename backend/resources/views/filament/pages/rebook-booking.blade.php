<x-filament-panels::page>
    {{ $this->form }}

    @if($error)
        <div class="fi-alert fi-alert-danger rounded-lg p-4 mt-4">
            <div class="flex">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                <p class="ml-3 text-sm font-medium">{{ $error }}</p>
            </div>
        </div>
    @endif

    @if($foundBooking)
        @php
            $statusStyles = match($foundBooking->status) {
                'confirmed' => 'bg-green-500 text-white dark:bg-green-500 dark:text-white shadow-sm shadow-green-200 dark:shadow-green-900/30',
                'pending'   => 'bg-amber-500 text-white dark:bg-amber-500 dark:text-white shadow-sm shadow-amber-200 dark:shadow-amber-900/30',
                default     => 'bg-gray-500 text-white dark:bg-gray-500 dark:text-white shadow-sm shadow-gray-200 dark:shadow-gray-900/30',
            };
            $isAgentBooking = !empty($foundBooking->booking_agent_id);
            $tourDate = $foundBooking->tour_date->format('l, F j, Y');
            $tourTime = $foundBooking->timeSlot
                ? \Carbon\Carbon::parse($foundBooking->timeSlot->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($foundBooking->timeSlot->end_time)->format('g:i A')
                : 'TBD (Private Tour)';
            $boatName = $foundBooking->timeSlot?->boat?->name ?? 'N/A';
            $guestCount = $foundBooking->items->sum('quantity') . ' passengers';
            $totalDisplay = '$' . number_format($foundBooking->total_price_cents / 100, 2);
            $commDisplay = $foundBooking->commission_cents > 0 ? '$' . number_format($foundBooking->commission_cents / 100, 2) : null;
            $commPercent = $foundBooking->commission_percent ?? 0;
            $feeNote = $feeDollars > 0 ? ' A rescheduling fee of $' . number_format($feeDollars, 2) . ' will be charged to ' . ($foundBooking->primaryGuest?->email ?? 'the customer') . '.' : '';
        @endphp

        {{-- Top Bar: Back (left) | Ref + Badges (center) | Confirm (right) --}}
        <div class="flex flex-wrap items-center justify-between mt-6 mb-6 px-1 gap-3">
            <div class="order-1">
                <button wire:click="goBack" class="fi-button fi-button-outline text-sm">
                    <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    Back to Search
                </button>
            </div>
            <div class="order-2 lg:order-2 flex flex-wrap items-center gap-3">
                <span class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">{{ $foundBooking->booking_ref }}</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize {{ $statusStyles }}">{{ $foundBooking->status }}</span>
                @if($isAgentBooking)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-teal-500 text-white dark:bg-teal-500 dark:text-white shadow-sm shadow-teal-200 dark:shadow-teal-900/30">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        Agent Booking
                    </span>
                @endif
            </div>
            <div class="order-3 ml-auto">
                <button
                    wire:click="openConfirmModal"
                    wire:loading.attr="disabled"
                    class="fi-button fi-button-danger text-sm"
                >
                    <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span wire:loading.remove wire:target="submitRebook">Confirm Rebook</span>
                    <span wire:loading wire:target="submitRebook">Processing…</span>
                </button>
            </div>
        </div>

        {{-- Main 2-Column Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- LEFT COLUMN --}}
            <div class="space-y-6">

                {{-- Booking Details --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <h3 class="text-base font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            Booking Details
                        </h3>
                    </x-slot>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div class="space-y-0.5">
                            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $tourDate }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $tourTime }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Boat</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $boatName }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Guests</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $guestCount }}</p>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Customer & Agency --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <h3 class="text-base font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            Customer &amp; Agency
                        </h3>
                    </x-slot>
                    <div class="space-y-4 text-sm">
                        @if($isAgentBooking)
                            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Agency</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $foundBooking->bookingAgent?->name ?? '—' }}</p>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Commission</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $commDisplay }} ({{ $commPercent }}%)</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 grid grid-cols-2 gap-x-6 gap-y-4">
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer Name</span>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $foundBooking->primaryGuest?->first_name }} {{ $foundBooking->primaryGuest?->last_name }}</p>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer Email</span>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $foundBooking->primaryGuest?->email ?? '—' }}</p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer Name</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $foundBooking->primaryGuest?->first_name }} {{ $foundBooking->primaryGuest?->last_name }}</p>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer Email</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $foundBooking->primaryGuest?->email ?? '—' }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-6">

                {{-- Rebook Details (Form) --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <h3 class="text-base font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                            Rebook Details
                        </h3>
                    </x-slot>
                    <div class="space-y-4">
                        <div>
                            <label class="fi-form-label text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">New Tour Date *</label>
                            <input
                                type="date"
                                wire:model.live="newDate"
                                min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 mt-1.5 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                                required
                            />
                        </div>

                        {{-- Time Slot Cards (regular bookings) --}}
                        @if($newDate && $foundBooking->time_slot_id)
                            <div>
                                <label class="fi-form-label text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Time Slot *</label>
                                @if(empty($availableSlots))
                                    <p class="mt-1.5 text-sm text-amber-600 dark:text-amber-400">No available time slots for this date.</p>
                                @else
                                    <div class="rebook-card-grid mt-3">
                                        @foreach($availableSlots as $slot)
                                            @php
                                                $isSelected = $newTimeSlotId === $slot['id'];
                                                $isFull = $slot['full'];
                                                $isLow = !$isFull && $slot['remaining'] <= 3;
                                            @endphp
                                            <div
                                                wire:click="{{ $isSelected ? "\$set('newTimeSlotId', '')" : "\$set('newTimeSlotId', '" . $slot['id'] . "')" }}"
                                                class="rebook-slot-card {{ $isSelected ? 'rebook-slot-selected' : '' }} {{ $isFull ? 'rebook-slot-disabled' : '' }}"
                                            >
                                                <div class="rebook-slot-check">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <div class="rebook-slot-icon">⛵</div>
                                                <div class="rebook-slot-time">{{ $slot['time'] }}</div>
                                                <div class="rebook-slot-boat">{{ $slot['boat'] }}</div>
                                                <span class="rebook-slot-badge {{ $isFull ? 'rebook-slot-full' : ($isLow ? 'rebook-slot-low' : '') }}">
                                                    @if($isFull)
                                                        Full
                                                    @else
                                                        {{ $slot['remaining'] }} of {{ $slot['capacity'] }} spots
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Private Tour Time Pickers --}}
                        @if($newDate && !$foundBooking->time_slot_id)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="fi-form-label text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Start Time *</label>
                                    <input
                                        type="time"
                                        wire:model.live="newStartTime"
                                        required
                                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 mt-1.5 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                                    />
                                </div>
                                <div>
                                    <label class="fi-form-label text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">End Time *</label>
                                    <input
                                        type="time"
                                        wire:model.live="newEndTime"
                                        required
                                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 mt-1.5 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                                    />
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="fi-form-label text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rebooking Fee ($)</label>
                            <input
                                type="number"
                                wire:model.live="feeDollars"
                                min="0"
                                step="0.01"
                                value="0"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 mt-1.5 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave at $0 for free rebooking. Charged to the customer.</p>
                        </div>

                        @if($feeDollars > 0 && (!$foundBooking->primaryGuest || empty($foundBooking->primaryGuest->email)))
                            <div class="fi-alert fi-alert-danger rounded-lg p-3">
                                <p class="text-sm font-medium">Customer email is required to apply a fee. Set $0 or add an email first.</p>
                            </div>
                        @endif
                    </div>
                </x-filament::section>

                {{-- Financial Summary --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <h3 class="text-base font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                            Financial Summary
                        </h3>
                    </x-slot>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-600 dark:text-gray-400">Booking Total</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $totalDisplay }}</span>
                        </div>
                        @if($commDisplay)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-400">Agent Commission</span>
                                <span class="font-medium text-teal-700 dark:text-teal-300">{{ $commDisplay }} ({{ $commPercent }}%)</span>
                            </div>
                        @endif
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2 flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Rebooking Fee</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format(($feeDollars ?? 0), 2) }}</span>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Bottom Actions (right-justified) --}}
                <div class="flex items-center justify-end gap-3 pt-1">
                    <button
                        wire:click="openConfirmModal"
                        wire:loading.attr="disabled"
                        class="fi-button fi-button-danger text-sm"
                    >
                        <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span wire:loading.remove wire:target="submitRebook">Confirm Rebook</span>
                        <span wire:loading wire:target="submitRebook">Processing…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Confirmation Modal --}}
        <div x-data="{ show: @entangle('showConfirmModal') }" x-show="show" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center"
             style="display: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="show = false"></div>

            {{-- Modal --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 border border-gray-200 dark:border-gray-700"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
            >
                {{-- Warning icon --}}
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>

                <h3 class="text-lg font-bold text-center text-gray-900 dark:text-gray-100 mb-2">Confirm Rebook</h3>
                <p class="text-sm text-center text-gray-600 dark:text-gray-400 mb-4">
                    Booking <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $foundBooking->booking_ref }}</span> will be <span class="font-semibold text-red-600 dark:text-red-400">permanently cancelled</span> and a new booking will be created. This action cannot be undone.
                </p>
                @if($feeDollars > 0)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 mb-5 text-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            <span class="text-amber-800 dark:text-amber-300">A rescheduling fee of <strong>${{ number_format($feeDollars, 2) }}</strong> will be charged to {{ $foundBooking->primaryGuest?->email ?? 'the customer' }}.</span>
                        </div>
                    </div>
                @endif

                <div class="flex gap-3 mt-2">
                    <button @click="show = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="submitRebook" class="flex-1 px-4 py-2.5 text-sm font-bold text-white rounded-lg shadow-md hover:shadow-lg transition-all disabled:opacity-50" style="background-color:#dc2626;border:2px solid #991b1b" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitRebook">Yes, Rebook</span>
                        <span wire:loading wire:target="submitRebook">Processing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Scoped styles for rebook time slot cards --}}
    <style>
        [x-cloak]{display:none!important}
        .rebook-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
        .rebook-slot-card{position:relative;border:2px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;transition:all .2s ease;background:#fff;user-select:none}
        .rebook-slot-card:hover{border-color:#5eead4;box-shadow:0 2px 8px rgba(0,48,56,.08)}
        .rebook-slot-selected{border-color:#0f766e;background:#f0fdfa;box-shadow:0 0 0 3px rgba(15,118,110,.15)}
        .rebook-slot-disabled{opacity:.5;cursor:not-allowed;pointer-events:none}
        .rebook-slot-check{position:absolute;top:8px;right:8px;width:22px;height:22px;border-radius:50%;background:#0f766e;color:#fff;display:none;align-items:center;justify-content:center;font-size:12px;line-height:1}
        .rebook-slot-selected .rebook-slot-check{display:flex}
        .rebook-slot-icon{width:40px;height:40px;border-radius:10px;background:#f0fdfa;color:#0f766e;display:flex;align-items:center;justify-content:center;margin-bottom:10px;font-size:18px}
        .rebook-slot-selected .rebook-slot-icon{background:#0f766e;color:#fff}
        .rebook-slot-selected .rebook-slot-time{color:#0f766e}
        .rebook-slot-time{font-size:15px;font-weight:600;color:#003038;margin-bottom:2px}
        .rebook-slot-boat{font-size:13px;color:#64748b;line-height:1.4}
        .rebook-slot-badge{display:inline-block;margin-top:8px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;background:#f0fdfa;color:#0f766e}
        .rebook-slot-selected .rebook-slot-badge{background:#0f766e;color:#fff}
        .rebook-slot-full{background:#fef3c7;color:#92400e}
        .rebook-slot-selected .rebook-slot-full{background:#0f766e;color:#fff}
        .rebook-slot-low{background:#fef3c7;color:#92400e}
        .rebook-slot-selected .rebook-slot-low{background:#0f766e;color:#fff}
        @media (prefers-color-scheme: dark) {
            .rebook-slot-card{border-color:#374151;background:#1f2937}
            .rebook-slot-card:hover{border-color:#5eead4;box-shadow:0 2px 8px rgba(0,0,0,.3)}
            .rebook-slot-selected{border-color:#14b8a6;background:rgba(20,184,166,.08);box-shadow:0 0 0 3px rgba(20,184,166,.15)}
            .rebook-slot-icon{background:rgba(20,184,166,.1);color:#5eead4}
            .rebook-slot-selected .rebook-slot-icon{background:#0f766e;color:#fff}
        .rebook-slot-selected .rebook-slot-time{color:#0f766e}
            .rebook-slot-time{color:#f3f4f6}
            .rebook-slot-boat{color:#9ca3af}
            .rebook-slot-badge{background:rgba(20,184,166,.1);color:#5eead4}
            .rebook-slot-badge.rebook-slot-low,.rebook-slot-badge.rebook-slot-full{background:rgba(245,158,11,.1);color:#fbbf24}
            .rebook-slot-selected .rebook-slot-badge{background:#0f766e;color:#fff}
            .rebook-slot-check{background:#0f766e}
        }
    </style>
</x-filament-panels::page>

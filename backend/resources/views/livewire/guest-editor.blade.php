<div>
    <div class="fi-section">
        <div class="fi-section-header">
            <h3 class="fi-section-header-heading">Guest Details</h3>
            <p class="text-sm text-gray-500">
                {{ $expectedGuestCount }} guest{{ $expectedGuestCount !== 1 ? 's' : '' }} expected —
                {{ count(array_filter($guestIds)) }} collected
            </p>
        </div>

        {{-- Pill Tabs --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach(range(0, $expectedGuestCount - 1) as $i)
                @php
                    $hasData = !empty($guests[$i]['first_name']) || !empty($guests[$i]['last_name']);
                    $isActive = $activeGuestIndex === $i;
                @endphp
                <button
                    wire:click="selectGuest({{ $i }})"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                    {{ $isActive ? 'bg-teal-600 text-white' : ($hasData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-500 border border-dashed border-gray-300 hover:border-gray-400') }}"
                >
                    <span class="w-2 h-2 rounded-full {{ $hasData ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    Guest {{ $i + 1 }}
                    {{ $i === 0 ? '(Primary)' : '' }}
                </button>
            @endforeach
        </div>

        {{-- Guest Form --}}
        @isset($guests[$activeGuestIndex])
            <div class="space-y-3">
                <p class="text-sm font-medium text-gray-700">
                    {{ $activeGuestIndex === 0 ? 'Primary Guest (Purchaser)' : 'Guest ' . ($activeGuestIndex + 1) . (empty($guestIds[$activeGuestIndex]) ? ' — Not yet collected' : '') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input
                            type="text"
                            wire:model.live="guests.{{ $activeGuestIndex }}.first_name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-2 focus:ring-teal-600 focus:outline-none"
                            placeholder="First name"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input
                            type="text"
                            wire:model.live="guests.{{ $activeGuestIndex }}.last_name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-2 focus:ring-teal-600 focus:outline-none"
                            placeholder="Last name"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            wire:model.live="guests.{{ $activeGuestIndex }}.email"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-2 focus:ring-teal-600 focus:outline-none"
                            placeholder="email@example.com"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input
                            type="text"
                            wire:model.live="guests.{{ $activeGuestIndex }}.phone"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-2 focus:ring-teal-600 focus:outline-none"
                            placeholder="+1 242 555-0000"
                        />
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button
                        wire:click="saveGuest"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors"
                    >
                        Save Guest
                    </button>
                    @if($activeGuestIndex < $expectedGuestCount - 1)
                        <button
                            wire:click="saveAndNext"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
                        >
                            Save & Next
                        </button>
                    @endif
                </div>
            </div>
        @endisset
    </div>

    @script
        <script>
            document.addEventListener('livewire:notify', (event) => {
                const { type, message } = event.detail;
                // Use Filament's built-in notification if available
                if (window.Filament && window.Filament.notify) {
                    window.Filament.notify({ type, message });
                }
            });
        </script>
    @endscript
</div>

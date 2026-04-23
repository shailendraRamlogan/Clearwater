<div class="space-y-4">
    @if($expectedGuestCount === 0)
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No tickets found for this booking.</p>
    @else
        {{-- Pill Tabs --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach(range(0, $expectedGuestCount - 1) as $i)
                @php
                    $hasName = !empty(trim($guests[$i]['first_name'] ?? '')) || !empty(trim($guests[$i]['last_name'] ?? ''));
                    $isActive = $activeGuestIndex === $i;
                @endphp
                <button
                    wire:click="selectGuest({{ $i }})"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer
                        {{ $isActive
                            ? 'bg-teal-600 text-white dark:bg-teal-500'
                            : ($hasName
                                ? 'bg-teal-100 text-teal-800 border border-teal-200 hover:bg-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:border-teal-800 dark:hover:bg-teal-900/60'
                                : 'bg-gray-100 border-2 border-dashed border-gray-300 text-gray-500 hover:border-teal-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:border-teal-500')
                        }}"
                >
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $isActive ? 'bg-white text-teal-600 dark:bg-gray-900 dark:text-teal-400' : ($hasName ? 'bg-teal-600 text-white dark:bg-teal-700' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }}">
                        {{ $i + 1 }}
                    </span>
                    @if($hasName)
                        {{ trim(($guests[$i]['first_name'] ?? '') . ' ' . ($guests[$i]['last_name'] ?? '')) }}
                    @else
                        {{ $i === 0 ? 'Purchaser' : 'Guest ' . ($i + 1) }}
                    @endif
                    @if($i === 0)
                        <span class="text-xs opacity-75">★</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Active Guest Form --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                {{ $activeGuestIndex === 0 ? 'Primary Purchaser' : 'Guest ' . ($activeGuestIndex + 1) }}
            </h4>
            @if($activeGuestIndex === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The primary contact for this booking.</p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Optional guest details. Leave blank if not yet collected.</p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name</label>
                    <input
                        type="text"
                        wire:model.live="guests.{{ $activeGuestIndex }}.first_name"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="First name"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                    <input
                        type="text"
                        wire:model.live="guests.{{ $activeGuestIndex }}.last_name"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="Last name"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input
                        type="email"
                        wire:model.live="guests.{{ $activeGuestIndex }}.email"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="email@example.com"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                    <input
                        type="text"
                        wire:model.live="guests.{{ $activeGuestIndex }}.phone"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="+1 242 555-0000"
                    />
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                @if($activeGuestIndex < $expectedGuestCount - 1)
                    <button
                        wire:click="saveGuest"
                        class="px-4 py-2 bg-teal-600 dark:bg-teal-500 text-white rounded-lg text-sm font-medium hover:bg-teal-700 dark:hover:bg-teal-600 transition-colors cursor-pointer"
                    >
                        Save Guest
                    </button>
                    <button
                        wire:click="saveAndNext"
                        class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors cursor-pointer"
                    >
                        Save & Next
                    </button>
                @else
                    <button
                        wire:click="saveAndFinish"
                        class="px-4 py-2 bg-teal-600 dark:bg-teal-500 text-white rounded-lg text-sm font-medium hover:bg-teal-700 dark:hover:bg-teal-600 transition-colors cursor-pointer"
                    >
                        Save & Finish
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>

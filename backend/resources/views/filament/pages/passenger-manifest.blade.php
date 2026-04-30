<x-filament-panels::page>
    @push('styles')
    <style>
        .manifest-btn-primary {
            background-color: rgba(var(--primary-600), 1) !important;
            color: rgb(255 255 255) !important;
        }
        .manifest-btn-primary:hover {
            background-color: rgba(var(--primary-700), 1) !important;
        }
        .manifest-btn-secondary {
            background-color: #fff !important;
            color: #374151 !important;
            --tw-ring-color: rgba(0,0,0,0.1);
        }
        .manifest-btn-secondary:hover {
            background-color: #f9fafb !important;
        }
        :root.dark .manifest-btn-secondary {
            background-color: rgba(var(--gray-900), 1) !important;
            color: rgba(var(--gray-200), 1) !important;
            --tw-ring-color: rgba(255,255,255,0.15);
        }
        :root.dark .manifest-btn-secondary:hover {
            background-color: rgba(var(--gray-700), 1) !important;
        }
        :root.dark .manifest-btn-primary:hover {
            background-color: rgba(var(--primary-700), 1) !important;
        }
    </style>
    @endpush
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 mb-1">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Sailing Date</span>
            </label>
            <input type="date" wire:model.defer="filter_date" wire:change="onDateChanged($event.target.value)"
                class="fi-input block w-full border-none py-1.5 text-base text-gray-950 dark:text-white bg-white dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10 ps-3 pe-3"
                id="date-select">
        </div>
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 mb-1">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Vessel</span>
            </label>
            <select wire:model.defer="filter_boat_id" wire:change="onBoatChanged($event.target.value)"
                class="fi-input block w-full border-none py-1.5 text-base text-gray-950 dark:text-white bg-white dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10 ps-3 pe-3"
                id="vessel-select">
                <option value="">All Vessels</option>
                @foreach($vessels as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 mb-1">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Time Slot</span>
            </label>
            <select wire:model.defer="filter_time_slot_id"
                @if(!$filter_date) disabled @endif
                class="fi-input block w-full border-none py-1.5 text-base text-gray-950 dark:text-white bg-white dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10 ps-3 pe-3 disabled:opacity-50 disabled:cursor-not-allowed"
                id="slot-select">
                <option value="">All Slots</option>
                @if($filter_date && empty($timeSlots))
                    <option value="" disabled>No available time slots</option>
                @endif
                @foreach($timeSlots as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex gap-2 mb-4">
        <button wire:click="applyFilters" wire:loading.attr="disabled"
            class="manifest-btn-primary inline-flex items-center gap-x-2 px-3 py-1.5 rounded-lg text-sm font-medium shadow-sm">
            Apply Filters
        </button>
        <button onclick="downloadManifest('pdf')"
            class="manifest-btn-secondary inline-flex items-center gap-x-2 px-3 py-1.5 rounded-lg text-sm font-medium shadow-sm ring-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            Export PDF
        </button>
    </div>

    <div x-data x-cloak>
        {{ $this->table }}
    </div>

    @push('scripts')
    <script>
        function downloadManifest(format) {
            const params = new URLSearchParams();
            params.append('date', document.getElementById('date-select').value);
            const boatEl = document.getElementById('vessel-select');
            if (boatEl && boatEl.value) params.append('boat_id', boatEl.value);
            const slotEl = document.getElementById('slot-select');
            if (slotEl && slotEl.value) params.append('time_slot_id', slotEl.value);
            params.append('format', format);

            fetch('/downloadPassengerManifest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: params.toString(),
            })
            .then(r => r.json())
            .then(data => {
                console.log('Manifest export:', data.guests_count, 'guests');
                const bytes = atob(data.content);
                const arr = new Uint8Array(bytes.length);
                for (let i = 0; i < bytes.length; i++) arr[i] = bytes.charCodeAt(i);
                const blob = new Blob([arr], { type: data.mime });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }
    </script>
    @endpush
</x-filament-panels::page>

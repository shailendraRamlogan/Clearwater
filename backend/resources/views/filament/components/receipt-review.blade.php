@props([
    'type' => 'regular',
    'bookingType' => 'Regular Sailing',
    'tourDate' => '',
    'timeSlot' => '',
    'guests' => '',
    'adultCount' => 0,
    'childCount' => 0,
    'infantCount' => 0,
    'adultPrice' => 0,
    'childPrice' => 0,
    'adultTotal' => 0,
    'childTotal' => 0,
    'addons' => [],
    'addonTotal' => 0,
    'subtotal' => 0,
    'fees' => 0,
    'commissionPct' => 0,
    'grandTotal' => 0,
    'contact' => '',
])

<div style="border: 1px solid #1f2937; border-radius: 0.75rem; overflow: hidden;">
    {{-- Header --}}
    <div style="background: rgba(15,118,110,0.1); border-bottom: 1px solid #1f2937; padding: 0.875rem 1.25rem;">
        <div style="font-size: 0.6875rem; font-weight: 600; color: #5eead4; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.125rem;">Booking Type</div>
        <div style="font-size: 0.9375rem; font-weight: 700; color: #f3f4f6;">{{ $bookingType }}</div>
    </div>

    {{-- Details --}}
    <div style="padding: 0.875rem 1.25rem;">
        <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid rgba(31,39,55,0.5);">
            <span style="color: #9ca3af; font-size: 0.8125rem;">Tour Date</span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">{{ $tourDate }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid rgba(31,39,55,0.5);">
            <span style="color: #9ca3af; font-size: 0.8125rem;">{{ $type === 'regular' ? 'Time Slot' : 'Time' }}</span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">{{ $timeSlot }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.375rem 0;">
            <span style="color: #9ca3af; font-size: 0.8125rem;">Guests</span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">{{ $guests }}</span>
        </div>
    </div>

    {{-- Tickets --}}
    <div style="border-top: 1px dashed #374151; padding: 0.875rem 1.25rem;">
        <div style="font-size: 0.6875rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Tickets</div>

        @if($adultCount > 0)
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #d1d5db; font-size: 0.8125rem;">Adults x{{ $adultCount }} <span style="color: #6b7280;">@ ${{ number_format($adultPrice, 2) }}</span></span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">${{ number_format($adultTotal, 2) }}</span>
        </div>
        @endif

        @if($childCount > 0)
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #d1d5db; font-size: 0.8125rem;">Children x{{ $childCount }} <span style="color: #6b7280;">@ ${{ number_format($childPrice, 2) }}</span></span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">${{ number_format($childTotal, 2) }}</span>
        </div>
        @endif

        @if($type === 'private' && $infantCount > 0)
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #d1d5db; font-size: 0.8125rem;">Infants x{{ $infantCount }} <span style="color: #6b7280;">(Free)</span></span>
            <span style="color: #6b7280; font-size: 0.8125rem;">$0.00</span>
        </div>
        @endif
    </div>

    {{-- Add-ons --}}
    <div style="border-top: 1px dashed #374151; padding: 0.875rem 1.25rem;">
        <div style="font-size: 0.6875rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Add-ons</div>

        @if(count($addons) > 0)
        @foreach($addons as $addon)
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #d1d5db; font-size: 0.8125rem;">{{ $addon['name'] }}</span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">${{ number_format($addon['price'], 2) }}</span>
        </div>
        @endforeach
        @else
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #4b5563; font-size: 0.8125rem; font-style: italic;">None</span>
            <span style="color: #4b5563; font-size: 0.8125rem;">$0.00</span>
        </div>
        @endif
    </div>

    {{-- Totals --}}
    <div style="border-top: 1px solid #374151; padding: 0.875rem 1.25rem;">
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #9ca3af; font-size: 0.8125rem;">Subtotal</span>
            <span style="color: #e5e7eb; font-size: 0.8125rem; font-weight: 600;">${{ number_format($subtotal, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
            <span style="color: #9ca3af; font-size: 0.8125rem;">Agent Commission{{ $commissionPct > 0 ? ' (' . number_format($commissionPct, 1) . '%)' : '' }}</span>
            <span style="color: #fb7185; font-size: 0.8125rem; font-weight: 600;">{{ $fees > 0 ? '-' : '' }}${{ number_format(abs($fees), 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0 0 0; margin-top: 0.375rem; border-top: 2px solid #5eead4;">
            <span style="color: #5eead4; font-size: 1rem; font-weight: 700;">Total</span>
            <span style="color: #5eead4; font-size: 1rem; font-weight: 700;">${{ number_format($grandTotal, 2) }}</span>
        </div>
    </div>

    {{-- Contact --}}
    @if($contact)
    <div style="border-top: 1px solid #1f2937; padding: 0.875rem 1.25rem;">
        <div style="font-size: 0.6875rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Primary Guest</div>
        <div style="color: #d1d5db; font-size: 0.8125rem; white-space: pre-wrap;">{{ $contact }}</div>
    </div>
    @endif
</div>

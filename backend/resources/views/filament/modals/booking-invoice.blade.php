@php
    $subtotalCents = $booking->items->sum(fn($i) => $i->quantity * $i->unit_price_cents);
    $photoUpgradeCents = $booking->photo_upgrade_count * 2500;
    $ticketTotalCents = $subtotalCents + $photoUpgradeCents;
    $fees = \App\Models\BookingFee::active()->orderBy('sort_order')->get();
    $feesCents = $fees->sum(fn($f) => $f->calculateFee($ticketTotalCents));
    $totalCents = $ticketTotalCents + $feesCents;
    $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->timeSlot->start_time)->format('g:i A');
    $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->timeSlot->end_time)->format('g:i A');
    $formattedDate = $booking->tour_date->format('F j, Y');
    $boatName = $booking->timeSlot?->boat?->name ?? 'N/A';
    $notes = $booking->special_comment ?: ($booking->special_occasion ?: 'None');
@endphp

<style>
    .invoice { width: 100%; max-width: 100%; margin: 0 auto; background-color: #141414; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); overflow: hidden; border: 1px solid #27272a; }
    .inv-header { background-color: #1e1e1e; padding: 2rem 1.5rem; color: #fff; }
    .inv-header-top { display: flex; align-items: flex-start; justify-content: space-between; }
    .inv-brand { display: flex; align-items: center; gap: .75rem; }
    .inv-brand-icon { width: 56px; height: 56px; background: rgba(255,255,255,.1); border-radius: .75rem; display: flex; align-items: center; justify-content: center; }
    .inv-brand-icon svg { width: 32px; height: 32px; }
    .inv-brand-name { font-size: 1.5rem; font-weight: 700; letter-spacing: -.025em; }
    .inv-brand-tagline { font-size: .875rem; opacity: .7; margin-top: .125rem; }
    .inv-badge { display: inline-block; padding: .25rem .75rem; background: rgba(255,255,255,.2); border-radius: 9999px; font-size: .75rem; font-weight: 500; }
    .inv-ref-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,.2); }
    .inv-ref-label { font-size: .875rem; opacity: .7; }
    .inv-ref-value { font-family: 'SF Mono','Monaco','Inconsolata',monospace; font-size: 1.125rem; font-weight: 600; letter-spacing: .05em; }
    .inv-details { padding: 1.5rem; border-bottom: 1px solid #27272a; }
    .inv-details-row { display: grid; grid-template-columns: repeat(2,1fr); gap: 1rem; }
    .inv-details-row + .inv-details-row { margin-top: 1rem; }
    .inv-detail { display: flex; align-items: flex-start; gap: .75rem; }
    .inv-detail-icon { width: 32px; height: 32px; background: #1e1e1e; border-radius: .5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .inv-detail-icon svg { width: 16px; height: 16px; }
    .inv-detail-icon.accent { background: rgba(245,158,11,.2); color: #f59e0b; }
    .inv-detail-label { font-size: .75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: .05em; font-weight: 500; }
    .inv-detail-value { font-weight: 600; margin-top: .125rem; color: #fafafa; }
    .inv-section { padding: 1.5rem; }
    .inv-section-title { font-size: .875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 1rem; color: #fafafa; }
    .inv-table { width: 100%; background: #1e1e1e; border-radius: .75rem; overflow: hidden; border-collapse: collapse; }
    .inv-table th { text-align: left; padding: .75rem 1rem; font-size: .75rem; font-weight: 600; color: #a1a1aa; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #27272a; }
    .inv-table th:nth-child(2) { text-align: center; }
    .inv-table th:nth-child(3), .inv-table th:nth-child(4) { text-align: right; }
    .inv-table td { padding: 1rem; border-bottom: 1px solid #27272a; color: #fafafa; }
    .inv-table tr:last-child td { border-bottom: none; }
    .inv-qty-badge { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #141414; border-radius: 9999px; font-size: .875rem; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
    .inv-item-price { text-align: right; color: #a1a1aa; }
    .inv-item-total { text-align: right; font-weight: 600; }
    .inv-total-section { display: flex; justify-content: flex-end; margin-top: 1rem; }
    .inv-total-box { background: #1e1e1e; border-radius: .75rem; padding: 1rem 1.5rem; color: #fff; display: flex; align-items: center; gap: 2rem; }
    .inv-total-label { font-weight: 500; opacity: .7; }
    .inv-total-amount { font-size: 1.5rem; font-weight: 700; }
    .inv-guests { padding: 1.5rem; background: #1e1e1e; border-top: 1px solid #27272a; }
    .inv-guest-card { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; background: #141414; border-radius: .75rem; padding: 1rem; border: 1px solid #27272a; }
    .inv-guest-card + .inv-guest-card { margin-top: .5rem; }
    .inv-guest-avatar-wrap { position: relative; }
    .inv-guest-avatar { width: 40px; height: 40px; background: #1e1e1e; border-radius: 9999px; display: flex; align-items: center; justify-content: center; color: #a1a1aa; }
    .inv-guest-avatar svg { width: 20px; height: 20px; }
    .inv-primary-badge { position: absolute; top: -4px; right: -4px; width: 16px; height: 16px; background: #f59e0b; border-radius: 9999px; display: flex; align-items: center; justify-content: center; }
    .inv-primary-badge svg { width: 10px; height: 10px; fill: white; }
    .inv-guest-name { font-weight: 600; color: #fafafa; }
    .inv-guest-contact { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; font-size: .875rem; color: #a1a1aa; }
    .inv-contact-item { display: flex; align-items: center; gap: .375rem; }
    .inv-contact-item svg { width: 16px; height: 16px; }
    .inv-footer { padding: 1rem 1.5rem; border-top: 1px solid #27272a; background: #141414; text-align: center; font-size: .875rem; color: #a1a1aa; }
    @media(max-width:480px){ .inv-details-row{grid-template-columns:1fr;} .inv-guest-contact{flex-direction:column;align-items:flex-start;gap:.5rem;} }
</style>

<div class="invoice">
    <!-- Header -->
    <div class="inv-header">
        <div class="inv-header-top">
            <div class="inv-brand">
                <div class="inv-brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><path d="M6 7V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v3"/></svg>
                </div>
                <div>
                    <div class="inv-brand-name">Clear Boat Bahamas</div>
                    <div class="inv-brand-tagline">Transparent Boat Water Adventures</div>
                </div>
            </div>
            <span class="inv-badge">INVOICE</span>
        </div>
        <div class="inv-ref-section">
            <div class="inv-ref-label">Invoice Number</div>
            <div class="inv-ref-value">{{ $booking->booking_ref }}</div>
        </div>
    </div>

    <!-- Booking Details -->
    <div class="inv-details">
        <div class="inv-details-row">
            <div class="inv-detail">
                <div class="inv-detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="inv-detail-label">Date</div>
                    <div class="inv-detail-value">{{ $formattedDate }}</div>
                </div>
            </div>
            <div class="inv-detail">
                <div class="inv-detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="inv-detail-label">Time</div>
                    <div class="inv-detail-value">{{ $startTime }} - {{ $endTime }}</div>
                </div>
            </div>
        </div>
        <div class="inv-details-row">
            <div class="inv-detail">
                <div class="inv-detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"/><path d="M12 10v4"/></svg>
                </div>
                <div>
                    <div class="inv-detail-label">Vessel</div>
                    <div class="inv-detail-value">{{ $boatName }}</div>
                </div>
            </div>
            <div class="inv-detail">
                <div class="inv-detail-icon accent">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <div class="inv-detail-label">Notes</div>
                    <div class="inv-detail-value">{{ $notes }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="inv-section">
        <h2 class="inv-section-title">Booking Summary</h2>
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->items as $item)
                    <tr>
                        <td class="item-name" style="font-weight:500">{{ ucfirst(str_replace('-', ' ', $item->ticket_type)) }}</td>
                        <td style="text-align:center"><span class="inv-qty-badge">{{ $item->quantity }}</span></td>
                        <td class="inv-item-price">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                        <td class="inv-item-total">${{ number_format(($item->quantity * $item->unit_price_cents) / 100, 2) }}</td>
                    </tr>
                @endforeach
                @if($booking->photo_upgrade_count > 0)
                    <tr>
                        <td style="font-weight:500">Photo Upgrade</td>
                        <td style="text-align:center"><span class="inv-qty-badge">{{ $booking->photo_upgrade_count }}</span></td>
                        <td class="inv-item-price">$25.00</td>
                        <td class="inv-item-total">${{ number_format($photoUpgradeCents / 100, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        @if($fees->count() > 0)
        <div class="inv-total-section">
            <div class="inv-total-box" style="flex-direction:column; align-items:flex-end; gap:0.35rem; width:50%;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span class="inv-total-label">Subtotal</span>
                    <span style="font-weight:500;">${{ number_format($ticketTotalCents / 100, 2) }}</span>
                </div>
                @foreach($fees as $fee)
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span class="inv-total-label">{{ $fee->name }}</span>
                    <span style="font-weight:500;">${{ number_format($fee->calculateFee($ticketTotalCents) / 100, 2) }}</span>
                </div>
                @endforeach
                <div style="display:flex; justify-content:space-between; width:100%; border-top:1px solid rgba(255,255,255,0.2); padding-top:0.35rem; margin-top:0.15rem;">
                    <span class="inv-total-label">Total Amount</span>
                    <span class="inv-total-amount">${{ number_format($totalCents / 100, 2) }}</span>
                </div>
            </div>
        </div>
        @else
        <div class="inv-total-section">
            <div class="inv-total-box">
                <span class="inv-total-label">Total Amount</span>
                <span class="inv-total-amount">${{ number_format($totalCents / 100, 2) }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Guests -->
    <div class="inv-guests">
        <h2 class="inv-section-title">Guest Information</h2>
        @if($booking->guests->count() > 0)
            @foreach($booking->guests as $guest)
                <div class="inv-guest-card">
                    <div class="inv-guest-avatar-wrap">
                        <div class="inv-guest-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        @if($guest->is_primary)
                            <div class="inv-primary-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                        @endif
                    </div>
                    <span class="inv-guest-name">{{ $guest->first_name }} {{ $guest->last_name }}</span>
                    <div class="inv-guest-contact">
                        @if($guest->email)
                            <div class="inv-contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>{{ $guest->email }}</span>
                            </div>
                        @endif
                        @if($guest->is_primary && $guest->phone)
                            <div class="inv-contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>{{ $guest->phone }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <p style="color:#a1a1aa;font-size:.875rem;font-style:italic">No guest details collected.</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="inv-footer">
        Thank you for choosing Clear Boat Bahamas. We look forward to welcoming you aboard!
    </div>
</div>

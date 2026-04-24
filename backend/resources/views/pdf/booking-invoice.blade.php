@php
    $subtotalCents = $booking->items->sum(fn($i) => $i->quantity * $i->unit_price_cents);
    $photoUpgradeCents = $booking->photo_upgrade_count * 2500;
    $ticketTotalCents = $subtotalCents + $photoUpgradeCents;
    $fees = \App\Models\BookingFee::active()->orderBy('sort_order')->get();
    $feesCents = $fees->sum(fn($f) => $f->calculateFee($ticketTotalCents));
    $totalCents = $ticketTotalCents + $feesCents;
    $startTime = \Carbon\Carbon::parse($booking->timeSlot->start_time)->format('g:i A');
    $endTime = \Carbon\Carbon::parse($booking->timeSlot->end_time)->format('g:i A');
    $formattedDate = $booking->tour_date->format('F j, Y');
    $boatName = $booking->timeSlot?->boat?->name ?? 'N/A';
    $notes = $booking->special_comment ?: ($booking->special_occasion ?: 'None');

    // SVGs as base64 for DomPDF compatibility
    $iconAnchor = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><path d="M6 7V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v3"/></svg>');
    $iconCalendar = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>');
    $iconClock = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>');
    $iconBoat = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"/><path d="M12 10v4"/></svg>');
    $iconNotes = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>');
    $iconPerson = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>');
    $iconEmail = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>');
    $iconPhone = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>');
@endphp

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 20px 0 0 0; size: A4; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; background-color: #0a0a0a; color: #fafafa; padding-top: 30px; }
    .invoice { width: 100%; max-width: 640px; margin: 0 auto; background-color: #141414; border: 1px solid #27272a; overflow: hidden; }
    .header { background-color: #1e1e1e; padding: 28px 24px; color: #ffffff; }
    .header-top { display: table; width: 100%; }
    .brand { display: table-cell; vertical-align: top; }
    .brand-icon { width: 44px; height: 44px; background-color: rgba(255,255,255,0.1); border-radius: 10px; display: inline-block; vertical-align: middle; text-align: center; line-height: 44px; margin-right: 10px; }
    .brand-icon img { width: 28px; height: 28px; vertical-align: middle; }
    .brand-text { display: inline-block; vertical-align: middle; }
    .brand-name { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; color: #ffffff; }
    .brand-tagline { font-size: 13px; opacity: 0.7; color: #ffffff; }
    .invoice-badge { display: inline-block; padding: 4px 14px; background-color: rgba(255,255,255,0.15); border-radius: 999px; font-size: 11px; font-weight: 600; color: #ffffff; letter-spacing: 0.5px; vertical-align: top; }
    .ref-section { margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.15); }
    .ref-label { font-size: 13px; opacity: 0.7; color: #ffffff; }
    .ref-value { font-family: 'Courier New', Courier, monospace; font-size: 16px; font-weight: 600; letter-spacing: 1px; color: #ffffff; }

    .details { padding: 18px 24px; border-bottom: 1px solid #27272a; }
    .details-row { display: table; width: 100%; margin-bottom: 14px; }
    .details-row:last-child { margin-bottom: 0; }
    .detail-item { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .detail-item:last-child { padding-right: 0; }
    .detail-icon { width: 30px; height: 30px; background-color: #1e1e1e; border-radius: 6px; display: inline-block; vertical-align: middle; text-align: center; margin-right: 10px; padding-top: 7px; }
    .detail-icon.accent { background-color: rgba(245,158,11,0.2); }
    .detail-icon img { width: 16px; height: 16px; }
    .detail-text { display: inline-block; vertical-align: middle; }
    .detail-label { font-size: 10px; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .detail-value { font-size: 14px; font-weight: 600; color: #fafafa; margin-top: 1px; }

    .line-items { padding: 18px 24px; }
    .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; color: #a1a1aa; }
    .items-table { width: 100%; background-color: #1e1e1e; border-radius: 10px; overflow: hidden; border-collapse: separate; border-spacing: 0; }
    .items-table th { text-align: left; padding: 10px 16px; font-size: 10px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #27272a; background-color: #1e1e1e; }
    .items-table th:nth-child(2) { text-align: center; }
    .items-table th:nth-child(3), .items-table th:nth-child(4) { text-align: right; }
    .items-table td { padding: 12px 16px; border-bottom: 1px solid #27272a; background-color: #1e1e1e; color: #fafafa; font-size: 13px; }
    .items-table tr:last-child td { border-bottom: none; }
    .qty-badge { display: inline-block; width: 28px; height: 28px; line-height: 28px; background-color: #141414; border-radius: 999px; font-size: 13px; font-weight: 600; text-align: center; color: #fafafa; vertical-align: middle; }
    .price-col { text-align: right; color: #a1a1aa; }
    .total-col { text-align: right; font-weight: 600; color: #fafafa; }
    .total-section { text-align: right; margin-top: 14px; }
    .total-box { display: inline-block; background-color: #1e1e1e; border-radius: 10px; padding: 10px 24px; border: 1px solid #27272a; }
    .total-label { font-size: 13px; font-weight: 500; color: #a1a1aa; margin-right: 16px; }
    .total-amount { font-size: 20px; font-weight: 700; color: #fafafa; }

    .guests-section { padding: 18px 24px; background-color: #1e1e1e; border-top: 1px solid #27272a; }
    .guest-card { background-color: #141414; border-radius: 10px; padding: 12px 14px; border: 1px solid #27272a; margin-bottom: 6px; }
    .guest-card:last-child { margin-bottom: 0; }
    .guest-row { display: table; width: 100%; }
    .guest-avatar-wrap { display: table-cell; vertical-align: middle; width: 44px; position: relative; }
    .guest-avatar { width: 36px; height: 36px; background-color: #1e1e1e; border-radius: 999px; display: inline-block; text-align: center; line-height: 36px; }
    .guest-avatar img { width: 18px; height: 18px; vertical-align: middle; }
    .guest-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
    .guest-name { font-size: 14px; font-weight: 600; color: #fafafa; }
    .guest-contact { font-size: 12px; color: #a1a1aa; margin-top: 2px; }
    .contact-sep { margin: 0 8px; }
    .star-badge { display: inline-block; width: 14px; height: 14px; background-color: #f59e0b; border-radius: 999px; text-align: center; line-height: 14px; font-size: 8px; color: white; margin-left: -8px; margin-top: -4px; vertical-align: top; }

    .footer { padding: 14px 24px; border-top: 1px solid #27272a; background-color: #141414; text-align: center; font-size: 12px; color: #a1a1aa; }
</style>
</head>
<body>
<div class="invoice">
    <!-- Header -->
    <div class="header">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td valign="top">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <td valign="middle" style="padding-right:10px;">
                            <div class="brand-icon"><img src="{{ $iconAnchor }}"></div>
                        </td>
                        <td valign="middle">
                            <div class="brand-name">Clear Boat Bahamas</div>
                            <div class="brand-tagline">Transparent Boat Water Adventures</div>
                        </td>
                    </tr></table>
                </td>
                <td valign="top" align="right">
                    <span class="invoice-badge">INVOICE</span>
                </td>
            </tr>
        </table>
        <div class="ref-section">
            <div class="ref-label">Invoice Number</div>
            <div class="ref-value">{{ $booking->booking_ref }}</div>
        </div>
    </div>

    <!-- Booking Details -->
    <div class="details">
        <table width="100%" cellpadding="0" cellspacing="0" class="details-row">
            <tr>
                <td class="detail-item">
                    <div class="detail-icon"><img src="{{ $iconCalendar }}"></div>
                    <div class="detail-text">
                        <div class="detail-label">Date</div>
                        <div class="detail-value">{{ $formattedDate }}</div>
                    </div>
                </td>
                <td class="detail-item">
                    <div class="detail-icon"><img src="{{ $iconClock }}"></div>
                    <div class="detail-text">
                        <div class="detail-label">Time</div>
                        <div class="detail-value">{{ $startTime }} - {{ $endTime }}</div>
                    </div>
                </td>
            </tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" class="details-row">
            <tr>
                <td class="detail-item">
                    <div class="detail-icon"><img src="{{ $iconBoat }}"></div>
                    <div class="detail-text">
                        <div class="detail-label">Vessel</div>
                        <div class="detail-value">{{ $boatName }}</div>
                    </div>
                </td>
                <td class="detail-item">
                    <div class="detail-icon accent"><img src="{{ $iconNotes }}"></div>
                    <div class="detail-text">
                        <div class="detail-label">Notes</div>
                        <div class="detail-value">{{ $notes }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Line Items -->
    <div class="line-items">
        <div class="section-title">Booking Summary</div>
        <table class="items-table">
            <thead>
                <tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($booking->items as $item)
                    <tr>
                        <td style="font-weight:500">{{ ucfirst(str_replace('-', ' ', $item->ticket_type)) }}</td>
                        <td style="text-align:center"><span class="qty-badge">{{ $item->quantity }}</span></td>
                        <td class="price-col">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                        <td class="total-col">${{ number_format(($item->quantity * $item->unit_price_cents) / 100, 2) }}</td>
                    </tr>
                @endforeach
                @if($booking->photo_upgrade_count > 0)
                    <tr>
                        <td style="font-weight:500">Photo Upgrade</td>
                        <td style="text-align:center"><span class="qty-badge">{{ $booking->photo_upgrade_count }}</span></td>
                        <td class="price-col">$25.00</td>
                        <td class="total-col">${{ number_format($photoUpgradeCents / 100, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        @if($fees->count() > 0)
        <div class="total-section">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td align="right">
                    <div class="total-box" style="min-width:260px;">
                        <table width="100%" cellpadding="4" cellspacing="0">
                            <tr>
                                <td class="total-label">Subtotal</td>
                                <td style="text-align:right;font-weight:500;color:#fafafa;">${{ number_format($ticketTotalCents / 100, 2) }}</td>
                            </tr>
                            @foreach($fees as $fee)
                            <tr>
                                <td class="total-label">{{ $fee->name }}</td>
                                <td style="text-align:right;font-weight:500;color:#fafafa;">${{ number_format($fee->calculateFee($ticketTotalCents) / 100, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr style="border-top:1px solid #27272a;">
                                <td class="total-label" style="padding-top:8px;">Total Amount</td>
                                <td style="text-align:right;padding-top:8px;"><span class="total-amount">${{ number_format($totalCents / 100, 2) }}</span></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr></table>
        </div>
        @else
        <div class="total-section">
            <div class="total-box">
                <span class="total-label">Total Amount</span>
                <span class="total-amount">${{ number_format($totalCents / 100, 2) }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Guests -->
    <div class="guests-section">
        <div class="section-title">Guest Information</div>
        @if($booking->guests->count() > 0)
            @foreach($booking->guests as $guest)
                <div class="guest-card">
                    <table width="100%" cellpadding="0" cellspacing="0"><tr>
                        <td valign="middle" width="44" style="position:relative;">
                            <div class="guest-avatar"><img src="{{ $iconPerson }}"></div>
                            @if($guest->is_primary)
                                <div class="star-badge">★</div>
                            @endif
                        </td>
                        <td valign="middle" style="padding-left:12px;">
                            <div class="guest-name">{{ $guest->first_name }} {{ $guest->last_name }}</div>
                            <div class="guest-contact">
                                @if($guest->email)
                                    <img src="{{ $iconEmail }}" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;">{{ $guest->email }}
                                @endif
                                @if($guest->email && $guest->phone)<span class="contact-sep">·</span>@endif
                                @if($guest->phone)
                                    <img src="{{ $iconPhone }}" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;">{{ $guest->phone }}
                                @endif
                            </div>
                        </td>
                    </tr></table>
                </div>
            @endforeach
        @else
            <p style="color:#a1a1aa;font-size:12px;font-style:italic;">No guest details collected.</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Thank you for choosing Clear Boat Bahamas. We look forward to welcoming you aboard!
    </div>
</div>
</body>
</html>

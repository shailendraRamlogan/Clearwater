<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: system-ui, sans-serif; margin: 40px; color: #1a1a1a; }
        h1 { font-size: 24px; margin-bottom: 5px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f5f5f5; text-align: left; padding: 10px 12px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #ddd; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Clear Boat Bahamas</h1>
    <p class="subtitle">Daily Schedule — {{ $date }}</p>

    <table>
        <thead>
            <tr>
                <th>Ref</th>
                <th>Guest</th>
                <th>Boat</th>
                <th>Time</th>
                <th>Adults</th>
                <th>Children</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
            <tr>
                <td>{{ $booking->booking_ref }}</td>
                <td>{{ $booking->primaryGuest?->first_name }} {{ $booking->primaryGuest?->last_name }}</td>
                <td>{{ $booking->timeSlot->boat->name ?? '-' }}</td>
                <td>{{ $booking->timeSlot->start_time->format('g:i A') }} - {{ $booking->timeSlot->end_time->format('g:i A') }}</td>
                <td>{{ $booking->items->where('ticket_type', 'adult')->sum('quantity') }}</td>
                <td>{{ $booking->items->where('ticket_type', 'child')->sum('quantity') }}</td>
                <td>${{ number_format($booking->total_price_cents / 100, 2) }}</td>
                <td>{{ ucfirst($booking->status) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#999;">No bookings for this date</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ now()->format('F j, Y g:i A') }} · Clear Boat Bahamas</p>
    </div>
</body>
</html>

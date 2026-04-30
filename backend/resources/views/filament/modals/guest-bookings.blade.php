<div style="max-height: 400px; overflow-y: auto;">
    @if($bookings->isEmpty())
        <p style="color: #6b7280; text-align: center; padding: 24px 0;">No bookings found.</p>
    @else
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="text-align: left; padding: 8px 12px;">Ref</th>
                    <th style="text-align: left; padding: 8px 12px;">Date</th>
                    <th style="text-align: left; padding: 8px 12px;">Time</th>
                    <th style="text-align: left; padding: 8px 12px;">Boat</th>
                    <th style="text-align: left; padding: 8px 12px;">Status</th>
                    <th style="text-align: right; padding: 8px 12px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 8px 12px; font-weight: 500;">{{ $booking->booking_ref }}</td>
                        <td style="padding: 8px 12px;">{{ $booking->tour_date?->format('M j, Y') }}</td>
                        <td style="padding: 8px 12px;">
                            @if($booking->timeSlot)
                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $booking->timeSlot->start_time)->format('g:i A') }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding: 8px 12px;">{{ $booking->timeSlot?->boat?->name ?? '—' }}</td>
                        <td style="padding: 8px 12px;">
                            <span style="
                                display: inline-block;
                                padding: 2px 8px;
                                border-radius: 9999px;
                                font-size: 12px;
                                font-weight: 500;
                                background: {{ match($booking->status) {
                                    'confirmed' => '#dcfce7; color: #166534',
                                    'pending' => '#fef9c3; color: #854d0e',
                                    'cancelled' => '#fee2e2; color: #991b1b',
                                    'completed' => '#dbeafe; color: #1e40af',
                                    default => '#f3f4f6; color: #374151',
                                }};
                            ">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        <td style="padding: 8px 12px; text-align: right; font-weight: 500;">
                            ${{ number_format((($booking->total_price_cents ?? 0) + ($booking->fees_cents ?? 0)) / 100, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 8px; font-size: 12px; color: #9ca3af;">{{ $bookings->count() }} booking(s)</p>
    @endif
</div>

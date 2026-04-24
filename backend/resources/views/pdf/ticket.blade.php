@php
    $startTime = \Carbon\Carbon::parse($booking->timeSlot->start_time)->format('g:i A');
    $endTime = \Carbon\Carbon::parse($booking->timeSlot->end_time)->format('g:i A');
    $formattedDate = $booking->tour_date->format('F j, Y');
    $boatName = $booking->timeSlot?->boat?->name ?? 'N/A';
    $totalTickets = count($tickets);
    $pages = array_chunk($tickets, 3);
@endphp

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; size: A4; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito', Helvetica, Arial, sans-serif; color: #0c4a6e; }

    .page {
        width: 100%;
        height: 297mm;
        padding: 0;
        position: relative;
        background: #ffffff;
    }

    /* Top accent bar */
    .accent-bar {
        width: 100%;
        height: 5px;
        background: linear-gradient(to right, #0ea5e9, #0369a1);
    }

    /* Header */
    .ticket-header {
        padding: 20px 36px 14px 36px;
        display: table;
        width: 100%;
    }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; }
    .brand-name { font-size: 20px; font-weight: 700; color: #0369a1; letter-spacing: -0.3px; }
    .brand-tagline { font-size: 11px; color: #0ea5e9; font-weight: 500; margin-top: 1px; }
    .ref-badge {
        display: inline-block;
        background: #f0f9ff;
        border: 1px solid #e0f2fe;
        border-radius: 999px;
        padding: 4px 14px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        font-weight: 700;
        color: #0369a1;
        letter-spacing: 1px;
    }

    /* Ticket blocks */
    .ticket-block { padding: 0 36px; }
    .ticket-block + .ticket-block {
        border-top: 1px dashed #bae6fd;
        margin-top: 10px;
        padding-top: 10px;
    }

    .ticket-top {
        display: table;
        width: 100%;
        margin-bottom: 6px;
    }
    .ticket-top-left { display: table-cell; vertical-align: middle; }
    .ticket-top-right { display: table-cell; vertical-align: middle; text-align: right; }

    .guest-name { font-size: 24px; font-weight: 700; color: #0369a1; line-height: 1.1; }
    .guest-role {
        display: inline-block;
        margin-top: 4px;
        background: #fef9c3;
        color: #0c4a6e;
        font-size: 9px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ticket-counter { font-size: 10px; color: #0ea5e9; font-weight: 500; }

    /* Trip Details */
    .trip-details { margin-top: 6px; }
    .details-grid {
        display: table;
        width: 100%;
        border: 1px solid #e0f2fe;
        border-radius: 8px;
        overflow: hidden;
    }
    .detail-cell {
        display: table-cell;
        width: 33.33%;
        vertical-align: top;
        padding: 8px 14px;
    }
    .detail-cell + .detail-cell { border-left: 1px solid #e0f2fe; }
    .detail-label {
        font-size: 9px;
        font-weight: 600;
        color: #0ea5e9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .detail-value { font-size: 13px; font-weight: 600; color: #0c4a6e; }

    /* Divider */
    .divider {
        margin: 8px 0;
        border: none;
        border-top: 1px dashed #bae6fd;
    }

    /* QR Section */
    .qr-section { text-align: center; }
    .qr-wrapper {
        display: inline-block;
        border: 2px solid #e0f2fe;
        border-radius: 10px;
        padding: 12px;
        background: #f0f9ff;
    }
    .qr-wrapper img { width: 120px; height: 120px; display: block; }
    .qr-hint { font-size: 10px; color: #0ea5e9; margin-top: 8px; font-weight: 500; }

    /* Footer */
    .ticket-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 12px 36px;
        background: #f0f9ff;
        border-top: 1px solid #e0f2fe;
        display: table;
        width: 100%;
    }
    .footer-left { display: table-cell; vertical-align: middle; }
    .footer-text { font-size: 10px; color: #0369a1; font-weight: 500; }
    .footer-right { display: table-cell; vertical-align: middle; text-align: right; }
    .footer-url { font-size: 10px; color: #0ea5e9; font-weight: 600; }
</style>
</head>
<body>

@foreach($pages as $pageIdx => $pageTickets)
<div class="page">
    <div class="accent-bar"></div>

    <div class="ticket-header">
        <div class="header-left">
            <div class="brand-name">Clear Boat Bahamas</div>
            <div class="brand-tagline">Transparent Boat Water Adventures</div>
        </div>
        <div class="header-right">
            <div class="ref-badge">{{ $booking->booking_ref }}</div>
        </div>
    </div>

    @foreach($pageTickets as $localIdx => $ticket)
    <?php $globalIdx = ($pageIdx * 3) + $localIdx + 1; ?>
    <div class="ticket-block">
        <div class="ticket-top">
            <div class="ticket-top-left">
                <div class="guest-name">{{ $ticket['guest']->first_name }} {{ $ticket['guest']->last_name }}</div>
                <span class="guest-role">{{ $ticket['guest']->is_primary ? 'Primary Booker' : 'Guest' }}</span>
            </div>
            <div class="ticket-top-right">
                <div class="ticket-counter">Ticket {{ $globalIdx }} of {{ $totalTickets }}</div>
            </div>
        </div>

        <div class="trip-details">
            <div class="details-grid">
                <div class="detail-cell">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">{{ $ticket['formatted_date'] }}</div>
                </div>
                <div class="detail-cell">
                    <div class="detail-label">Time</div>
                    <div class="detail-value">{{ $ticket['start_time'] }} - {{ $endTime }}</div>
                </div>
                <div class="detail-cell">
                    <div class="detail-label">Vessel</div>
                    <div class="detail-value">{{ $ticket['boat_name'] }}</div>
                </div>
            </div>
        </div>

        <hr class="divider">

        <div class="qr-section">
            <div class="qr-wrapper">
                <img src="{{ $ticket['qr_img'] }}" />
            </div>
            <div class="qr-hint">Present this QR code at check-in</div>
        </div>
    </div>
    @endforeach

    <div class="ticket-footer">
        <div class="footer-left">
            <div class="footer-text">Clear Boat Bahamas — Nassau, Bahamas</div>
        </div>
        <div class="footer-right">
            <div class="footer-url">clearwater.ourea.tech</div>
        </div>
    </div>
</div>
@endforeach

</body>
</html>

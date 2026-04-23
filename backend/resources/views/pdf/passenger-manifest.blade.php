<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 15mm; size: A4; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; background: #fff; color: #18181b; font-size: 11px; }

    .header { border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
    .header-title { font-size: 18px; font-weight: 700; color: #0d9488; margin-bottom: 8px; }
    .header-meta { display: table; width: 100%; }
    .meta-item { display: table-cell; width: 25%; vertical-align: top; padding-right: 8px; }
    .meta-label { font-size: 9px; color: #71717a; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .meta-value { font-size: 12px; font-weight: 600; color: #18181b; margin-top: 2px; }

    .manifest-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .manifest-table th { background-color: #0d9488; color: #fff; padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .manifest-table th:nth-child(4),
    .manifest-table th:nth-child(5) { text-align: center; }
    .manifest-table td { padding: 7px 10px; border-bottom: 1px solid #e4e4e7; font-size: 11px; }
    .manifest-table tr:nth-child(even) td { background-color: #f4f4f5; }
    .manifest-table td:nth-child(4),
    .manifest-table td:nth-child(5) { text-align: center; }

    .primary-row td { background-color: #f0fdfa !important; font-weight: 600; }
    .primary-badge { display: inline-block; background-color: #0d9488; color: #fff; padding: 1px 8px; border-radius: 999px; font-size: 8px; font-weight: 600; text-transform: uppercase; }

    .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e4e4e7; text-align: right; font-size: 10px; color: #71717a; }
    .footer strong { color: #18181b; }

    .disclaimer { margin-top: 30px; padding: 10px; background-color: #fefce8; border: 1px solid #fde68a; border-radius: 4px; font-size: 9px; color: #92400e; text-align: center; }
</style>
</head>
<body>
    <div class="header">
        <div class="header-title">Passenger Manifest</div>
        <div class="header-meta">
            <div class="meta-item">
                <div class="meta-label">Date</div>
                <div class="meta-value">{{ $date }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Vessel</div>
                <div class="meta-value">{{ $boat }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Time Slot</div>
                <div class="meta-value">{{ $slot }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Total Passengers</div>
                <div class="meta-value">{{ $total }}</div>
            </div>
        </div>
    </div>

    @if($guests->count() > 0)
    <table class="manifest-table">
        <thead>
            <tr>
                <th>Booking Ref</th>
                <th>Guest Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Tickets</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            @foreach($guests as $g)
            <tr class="{{ $g->is_primary ? 'primary-row' : '' }}">
                <td>{{ $g->booking->booking_ref }}</td>
                <td>{{ $g->first_name }} {{ $g->last_name }}</td>
                <td>{{ $g->email ?? '—' }}</td>
                <td>{{ $g->phone ?? '—' }}</td>
                <td>{{ $g->booking->items->sum('quantity') }}</td>
                <td>{{ $g->is_primary ? '<span class="primary-badge">Booker</span>' : 'Guest' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Total: <strong>{{ $total }} passengers</strong>
    </div>
    @else
    <p style="text-align: center; color: #71717a; padding: 40px 0; font-style: italic;">No passengers found for the selected filters.</p>
    @endif

    <div class="disclaimer">
        This manifest is confidential and intended for operational use only. Clear Boat Bahamas.
    </div>
</body>
</html>

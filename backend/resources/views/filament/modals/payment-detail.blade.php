<div style="max-width:100%;">
    <div style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
            <div>
                <p style="font-size:0.7rem;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:0.375rem;">Booking</p>
                <p style="font-size:0.875rem;color:#fafafa;">{{ $payment->booking->booking_ref }}</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:0.7rem;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:0.375rem;">Processed At</p>
                <p style="font-size:0.875rem;color:#fafafa;">{{ $payment->created_at->format('F j, Y g:i A') }}</p>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
            <div>
                <p style="font-size:0.7rem;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:0.375rem;">Amount</p>
                <p style="font-size:1.25rem;font-weight:700;color:#fafafa;">${{ number_format($payment->amount_cents / 100, 2) }}</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:0.7rem;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:0.375rem;">Status</p>
                <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:600;{{ $payment->status === 'succeeded' ? 'background-color:rgba(16,185,129,0.15);color:#10b981;' : ($payment->status === 'failed' ? 'background-color:rgba(239,68,68,0.15);color:#ef4444;' : ($payment->status === 'refunded' ? 'background-color:rgba(107,114,128,0.15);color:#9ca3af;' : 'background-color:rgba(245,158,11,0.15);color:#f59e0b;')) }}">
                    <span style="width:6px;height:6px;border-radius:9999px;background-color:currentColor;"></span>
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
        </div>

        <div style="background-color:#1e1e1e;border:1px solid #27272a;border-radius:0.75rem;padding:1rem;">
            <p style="font-size:0.7rem;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:0.5rem;">Stripe Payment Intent</p>
            <p style="font-size:0.8rem;font-family:'Courier New',Courier,monospace;color:#fafafa;background-color:#141414;padding:0.5rem 0.75rem;border-radius:0.375rem;border:1px solid #27272a;word-break:break-all;">{{ $payment->stripe_intent_id }}</p>
        </div>
    </div>
</div>

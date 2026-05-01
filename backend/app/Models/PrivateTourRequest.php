<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrivateTourRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_COMPLETED = 'completed';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'booking_ref', 'status',
        'contact_first_name', 'contact_last_name', 'contact_email', 'contact_phone',
        'adult_count', 'child_count', 'infant_count',
        'has_occasion', 'occasion_details', 'admin_notes',
        'confirmed_tour_date', 'confirmed_start_time', 'confirmed_end_time',
        'total_price_cents', 'fees_cents', 'stripe_intent_id',
    ];

    protected $appends = ['grand_total'];

    protected $casts = [
        'has_occasion' => 'boolean',
        'confirmed_tour_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivateTourRequest $request) {
            if (empty($request->id)) {
                $request->id = (string) Str::uuid();
            }
            if (empty($request->booking_ref)) {
                $request->booking_ref = self::generateRef();
            }
        });
    }

    public static function generateRef(): string
    {
        $date = now()->format('Ymd');
        $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        return "PTR-{$date}-{$token}";
    }

    public function preferredDates()
    {
        return $this->hasMany(PrivateTourPreferredDate::class, 'private_tour_request_id')
            ->orderBy('sort_order');
    }

    public function guests()
    {
        return $this->hasMany(PrivateTourGuest::class, 'private_tour_request_id');
    }

    public function addons()
    {
        return $this->hasMany(PrivateTourAddon::class, 'private_tour_request_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function totalGuests(): int
    {
        return $this->adult_count + $this->child_count;
    }

    public function getGrandTotalAttribute(): int
    {
        return ($this->total_price_cents ?? 0) + ($this->fees_cents ?? 0);
    }

    public function getFormattedTimeAttribute(): ?string
    {
        if (!$this->confirmed_start_time) return null;
        $start = $this->formatTimeField($this->confirmed_start_time);
        if ($this->confirmed_end_time) {
            $end = $this->formatTimeField($this->confirmed_end_time);
            return "{$start} – {$end}";
        }
        return $start;
    }

    private function formatTimeField($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('g:i A');
        }
        // Handle both 'H:i' and 'H:i:s' string formats
        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $value)->format('g:i A');
        } catch (\Exception $e) {
            return \Carbon\Carbon::createFromFormat('H:i', $value)->format('g:i A');
        }
    }

    public function getPaymentUrlAttribute(): ?string
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            return null;
        }
        return "https://bookings.clearboatbahamas.com/book/private-tour/pay?ref={$this->booking_ref}";
    }
}

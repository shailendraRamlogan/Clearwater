<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    private const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    private const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_CANCELLED],
        self::STATUS_CANCELLED => [],
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'booking_ref', 'tour_date', 'time_slot_id', 'status',
        'photo_upgrade_count', 'special_occasion', 'special_comment',
        'total_price_cents', 'fees_cents', 'is_confirmed', 'needs_confirmation',
    ];

    protected $appends = ['guests_count', 'complete_guests_count', 'grand_total'];

    protected $casts = [
        'tour_date' => 'date',
        'is_confirmed' => 'boolean',
        'needs_confirmation' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->id)) {
                $booking->id = (string) Str::uuid();
            }
            if (empty($booking->booking_ref)) {
                $booking->booking_ref = self::generateRef();
            }
        });
    }

    public static function generateRef(): string
    {
        $date = now()->format('Ymd');
        $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        return "CBB-{$date}-{$token}";
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function guests()
    {
        return $this->hasMany(BookingGuest::class);
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

        public function addons()
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function primaryGuest()
    {
        return $this->hasOne(BookingGuest::class)->where('is_primary', true);
    }

    public function isComplete(): bool
    {
        $expectedGuests = $this->items()->sum('quantity');
        return $this->guests()->count() >= $expectedGuests;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
            return false;
        }
        $allowed = self::TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    public function scopeIncomplete($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM booking_guests WHERE booking_guests.booking_id = bookings.id) < (SELECT COALESCE(SUM(quantity), 0) FROM booking_items WHERE booking_items.booking_id = bookings.id)');
    }

    public function scopeNeedsConfirmation($query)
    {
        return $query->where('needs_confirmation', true)->where('is_confirmed', false);
    }

    public function getGuestsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getCompleteGuestsCountAttribute(): int
    {
        if (!$this->relationLoaded('guests')) {
            return 0;
        }
        return 1 + $this->guests
            ->where('is_primary', false)
            ->where('last_name', '!=', '')
            ->where('email', '!=', '')
            ->count();
    }

    public function getGrandTotalAttribute(): int
    {
        return ($this->total_price_cents ?? 0) + ($this->fees_cents ?? 0);
    }
}

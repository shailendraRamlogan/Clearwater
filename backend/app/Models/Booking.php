<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'booking_ref', 'tour_date', 'time_slot_id', 'status',
        'photo_upgrade_count', 'special_occasion', 'special_comment',
        'total_price_cents',
    ];

    protected $casts = [
        'tour_date' => 'date',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function primaryGuest()
    {
        return $this->hasOne(BookingGuest::class)->where('is_primary', true);
    }
}

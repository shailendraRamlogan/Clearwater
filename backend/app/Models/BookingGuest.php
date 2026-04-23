<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BookingGuest extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'booking_id', 'first_name', 'last_name', 'email', 'phone', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (BookingGuest $guest) {
            if (empty($guest->id)) {
                $guest->id = (string) Str::uuid();
            }
        });

        static::created(function (BookingGuest $guest) {
            if (empty($guest->first_name) || empty($guest->last_name) || empty($guest->email)) {
                return;
            }

            $duplicate = $guest->booking->guests()
                ->where('id', '!=', $guest->id)
                ->where('first_name', $guest->first_name)
                ->where('last_name', $guest->last_name)
                ->where('email', $guest->email)
                ->exists();

            if ($duplicate) {
                $guest->booking->needs_confirmation = true;
                $guest->booking->saveQuietly();
            }
        });
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

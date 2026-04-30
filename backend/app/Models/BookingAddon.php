<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingAddon extends Model
{
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "booking_id", "addon_id", "quantity", "unit_price_cents",
    ];

    protected $casts = [
        "quantity" => "integer",
        "unit_price_cents" => "integer",
    ];

    protected static function booted(): void
    {
        static::creating(function (BookingAddon $ba) {
            if (empty($ba->id)) {
                $ba->id = (string) Str::uuid();
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}

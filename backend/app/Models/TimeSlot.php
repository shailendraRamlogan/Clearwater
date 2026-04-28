<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (TimeSlot $slot) {
            if (empty($slot->id)) {
                $slot->id = (string) \Str::uuid();
            }
        });
    }

    protected $fillable = ['id', 'boat_id', 'day', 'start_time', 'end_time', 'max_capacity', 'is_blocked', 'effective_from', 'effective_until'];

    protected $casts = [
        'is_blocked' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function remainingCapacity(string $date): int
    {
        $used = Booking::where('time_slot_id', $this->id)
            ->where('tour_date', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_guests');

        return max(0, $this->max_capacity - $used);
    }

    public function getStartLabelAttribute(): string
    {
        $day = $this->day ? ucfirst($this->day) : '';
        $time = $this->start_time ? \Carbon\Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A') : '';
        return trim("{$day} — {$time}");
    }

    public function getEndLabelAttribute(): string
    {
        $day = $this->day ? ucfirst($this->day) : '';
        $time = $this->end_time ? \Carbon\Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:i A') : '';
        return trim("{$day} — {$time}");
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'boat_id', 'start_time', 'end_time', 'max_capacity', 'is_blocked'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_blocked' => 'boolean',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAgent extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'commission_percent',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'booking_agent_id');
    }
}

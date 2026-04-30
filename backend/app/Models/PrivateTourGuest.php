<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrivateTourGuest extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'private_tour_request_id', 'first_name', 'last_name', 'email', 'phone', 'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $attributes = [
        'is_primary' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivateTourGuest $guest) {
            if (empty($guest->id)) {
                $guest->id = (string) Str::uuid();
            }
        });
    }

    public function privateTourRequest()
    {
        return $this->belongsTo(PrivateTourRequest::class, 'private_tour_request_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

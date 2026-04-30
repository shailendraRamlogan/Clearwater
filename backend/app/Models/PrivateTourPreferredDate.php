<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrivateTourPreferredDate extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'private_tour_request_id', 'date', 'time_preference', 'sort_order',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivateTourPreferredDate $date) {
            if (empty($date->id)) {
                $date->id = (string) Str::uuid();
            }
        });
    }

    public function privateTourRequest()
    {
        return $this->belongsTo(PrivateTourRequest::class, 'private_tour_request_id');
    }
}

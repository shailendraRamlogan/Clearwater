<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrivateTourAddon extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'private_tour_request_id', 'addon_id', 'unit_price_cents',
    ];

    protected $casts = [
        'unit_price_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivateTourAddon $pta) {
            if (empty($pta->id)) {
                $pta->id = (string) Str::uuid();
            }
        });
    }

    public function privateTourRequest(): BelongsTo
    {
        return $this->belongsTo(PrivateTourRequest::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}

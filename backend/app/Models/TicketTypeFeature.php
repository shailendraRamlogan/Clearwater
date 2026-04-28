<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TicketTypeFeature extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ticket_type_id',
        'icon',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketTypeFeature $feature) {
            if (empty($feature->id)) {
                $feature->id = (string) Str::uuid();
            }
        });
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }
}

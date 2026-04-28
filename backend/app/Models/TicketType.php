<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketType extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'description', 'price_cents', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_cents' => 'integer',
    ];

    protected $appends = ['price_dollars'];

    protected static function booted(): void
    {
        static::creating(function (TicketType $type) {
            if (empty($type->id)) {
                $type->id = (string) Str::uuid();
            }
        });
    }

    public function features(): HasMany
    {
        return $this->hasMany(TicketTypeFeature::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->with('features');
    }

    public function getPriceDollarsAttribute(): float
    {
        return $this->price_cents / 100;
    }
}

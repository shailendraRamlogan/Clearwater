<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Addon extends Model
{
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "title", "description", "price_cents", "private_price_cents", "available_for", "is_active", "sort_order",
        "max_quantity", "icon_name",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "price_cents" => "integer",
        "private_price_cents" => "integer",
    ];

    protected $appends = ["price_dollars"];

    protected static function booted(): void
    {
        static::creating(function (Addon $addon) {
            if (empty($addon->id)) {
                $addon->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    public function scopeForPrivateTours($query)
    {
        return $query->whereIn('available_for', ['private', 'both']);
    }

    public function getPriceDollarsAttribute(): float
    {
        return $this->price_cents / 100;
    }
}

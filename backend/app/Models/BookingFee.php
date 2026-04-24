<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BookingFee extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'type', 'value', 'flat_value', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'flat_value' => 'decimal:2',
    ];

    public const TYPE_FLAT = 'flat';
    public const TYPE_PERCENT = 'percent';
    public const TYPE_BOTH = 'both';

    protected static function booted(): void
    {
        static::creating(function (BookingFee $fee) {
            if (empty($fee->id)) {
                $fee->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateFee(int $subtotalCents): int
    {
        $fee = 0;

        if ($this->type === self::TYPE_FLAT) {
            $fee = (int) round($this->flat_value * 100);
        } elseif ($this->type === self::TYPE_PERCENT) {
            $fee = (int) round($subtotalCents * $this->value / 100);
        } elseif ($this->type === self::TYPE_BOTH) {
            $fee = (int) round($subtotalCents * $this->value / 100) + (int) round($this->flat_value * 100);
        }

        return $fee;
    }

    public function displayValue(): string
    {
        if ($this->type === self::TYPE_FLAT) {
            return '$' . number_format((float) $this->flat_value, 2);
        }

        if ($this->type === self::TYPE_PERCENT) {
            return number_format((float) $this->value, 2) . '%';
        }

        // both
        return number_format((float) $this->value, 2) . '% + $' . number_format((float) $this->flat_value, 2);
    }
}

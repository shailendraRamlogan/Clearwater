<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'amount_cents',
        'status',
        'initiated_by',
        'confirmed_by',
        'confirmed_at',
        'notes',
        'transfer_name',
        'receipt_image',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'status' => 'string',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $payout) {
            if (empty($payout->id)) {
                $payout->id = (string) Str::uuid();
            }
        });
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}

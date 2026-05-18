<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Refund extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AGENT_HANDLED = 'agent_handled';

    public const TYPE_FULL = 'full';
    public const TYPE_PARTIAL = 'partial';

    protected $fillable = [
        'id',
        'booking_id',
        'payment_id',
        'amount_cents',
        'fees_deducted_cents',
        'type',
        'status',
        'reason',
        'rejection_reason',
        'stripe_refund_id',
        'initiated_by',
        'approved_by',
        'approved_at',
        'processed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Refund $refund) {
            if (empty($refund->id)) {
                $refund->id = (string) Str::uuid();
            }
        });
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isAgentHandled(): bool
    {
        return $this->status === self::STATUS_AGENT_HANDLED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_AGENT_HANDLED,
        ]);
    }
}

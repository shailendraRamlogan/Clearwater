<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailLog extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'booking_id', 'recipient', 'subject', 'template', 'resend_id', 'status', 'sent_at'];

    protected static function booted(): void
    {
        static::creating(function (EmailLog $log) {
            if (empty($log->id)) {
                $log->id = (string) Str::uuid();
            }
            $log->sent_at = $log->sent_at ?? now();
        });
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

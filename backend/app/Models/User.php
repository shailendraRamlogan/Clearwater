<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Models\BookingAgent;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'email', 'name', 'password', 'role', 'booking_agent_id'];

    protected $hidden = ['password'];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff', 'super_admin', 'agent']);
    }

    public function bookingAgent()
    {
        return $this->belongsTo(BookingAgent::class);
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isAdminOrSuper(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }
}

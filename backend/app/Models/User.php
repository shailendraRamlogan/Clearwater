<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'email', 'name', 'password', 'role'];

    protected $hidden = ['password'];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff', 'super_admin']);
    }
}

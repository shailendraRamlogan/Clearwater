<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boat extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'slug', 'capacity', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }
}

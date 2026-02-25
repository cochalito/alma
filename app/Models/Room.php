<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['number', 'type', 'capacity', 'price_per_night', 'description'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}

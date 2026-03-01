<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'description', 'category', 'stock', 'price', 'status'];

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->withPivot('quantity', 'unit_price', 'subtotal')->withTimestamps();
    }
}

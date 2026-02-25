<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'description', 'category', 'price', 'stock', 'is_active', 'image_url'];

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->withPivot('quantity', 'unit_price', 'subtotal')->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'description', 'category', 'price', 'is_active'];

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->withPivot('quantity', 'unit_price', 'subtotal')->withTimestamps();
    }

    public function locations()
    {
        return $this->hasMany(ProductLocation::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Helper method to get stock for a specific location
    public function stockAt($location)
    {
        $locationRecord = $this->locations()->where('location', $location)->first();
        return $locationRecord ? $locationRecord->stock : 0;
    }

    // Helper to get total stock
    public function totalStock()
    {
        return $this->locations()->sum('stock');
    }
}

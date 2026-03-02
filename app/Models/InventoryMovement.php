<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'location',
        'type',
        'quantity',
        'user_id',
        'reservation_id',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'product_id' => 'integer',
        'user_id' => 'integer',
        'reservation_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
